<?php
/**
 * CRON Job - Sync Air Quality Data from AQICN
 * 
 * Script ini akan:
 * 1. Mengambil semua stasiun monitoring yang aktif
 * 2. Sync data kualitas udara dari AQICN API
 * 3. Simpan readings ke database
 * 4. Kirim notifikasi email jika AQI berbahaya
 * 
 * Usage: 
 *   Manual: php cron_sync_data.php
 *   Cron:   0 * * * * /usr/bin/php /path/to/cron_sync_data.php
 *   Windows Task Scheduler: C:\xampp\php\php.exe C:\xampp\htdocs\siapkak\cron_sync_data.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use App\Config\Env;
use App\Config\EmailNotification;

// Load environment
Env::load();

// Configuration
$apiKey = $_ENV['AQICN_API_KEY'] ?? '';
$enableEmailNotification = filter_var($_ENV['ENABLE_EMAIL_NOTIFICATIONS'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

if (empty($apiKey)) {
    logMessage("❌ Error: AQICN_API_KEY not found in .env file");
    die("API Key not configured\n");
}

// Database connection
$db = Database::getInstance()->getConnection();

// Email notification
$emailNotification = $enableEmailNotification ? new EmailNotification() : null;

// Log file
$logFile = __DIR__ . '/storage/logs/cron_sync.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// Start sync
logMessage("\n" . str_repeat("═", 80));
logMessage("🔄 AQICN DATA SYNC - Started at " . date('Y-m-d H:i:s'));
logMessage(str_repeat("═", 80));

$stats = [
    'total_stations' => 0,
    'synced' => 0,
    'failed' => 0,
    'skipped' => 0,
    'notifications_sent' => 0
];

try {
    // Get all stations with external_id
    $stmt = $db->query("
        SELECT id, name, location, latitude, longitude, external_id 
        FROM monitoring_stations 
        WHERE external_id IS NOT NULL
        ORDER BY location, name
    ");
    
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stats['total_stations'] = count($stations);
    
    logMessage("📍 Found {$stats['total_stations']} stations to sync");
    logMessage("");
    
    if ($stats['total_stations'] === 0) {
        logMessage("⚠️  No stations to sync. Run sync_indonesia_stations.php first.");
        exit(0);
    }
    
    foreach ($stations as $index => $station) {
        $stationNumber = $index + 1;
        $stationId = $station['id'];
        $stationName = $station['name'];
        $location = $station['location'];
        $latitude = $station['latitude'];
        $longitude = $station['longitude'];
        $externalId = $station['external_id'];
        
        logMessage("[{$stationNumber}/{$stats['total_stations']}] {$location} - {$stationName}");
        
        // Call AQICN API by station UID
        $apiUrl = "https://api.waqi.info/feed/@{$externalId}/?token={$apiKey}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            logMessage("   ❌ API Error (HTTP {$httpCode})");
            $stats['failed']++;
            sleep(1);
            continue;
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['status']) || $data['status'] !== 'ok') {
            logMessage("   ⚠️  No data available");
            $stats['skipped']++;
            sleep(1);
            continue;
        }
        
        $aqiData = $data['data'] ?? [];
        $aqi = $aqiData['aqi'] ?? null;
        
        if (!$aqi || !is_numeric($aqi)) {
            logMessage("   ⚠️  Invalid AQI data");
            $stats['skipped']++;
            sleep(1);
            continue;
        }
        
        // Extract pollutants
        $iaqi = $aqiData['iaqi'] ?? [];
        $pm25 = $iaqi['pm25']['v'] ?? null;
        $pm10 = $iaqi['pm10']['v'] ?? null;
        $o3 = $iaqi['o3']['v'] ?? null;
        $no2 = $iaqi['no2']['v'] ?? null;
        $so2 = $iaqi['so2']['v'] ?? null;
        $co = $iaqi['co']['v'] ?? null;
        
        // Determine AQI status
        $status = getAqiStatus($aqi);
        
        // Get timestamp
        $measuredAt = $aqiData['time']['iso'] ?? date('Y-m-d H:i:s');
        
        // Check if reading already exists for this timestamp
        $checkStmt = $db->prepare("
            SELECT id FROM air_quality_readings 
            WHERE station_id = ? 
            AND DATE_FORMAT(measured_at, '%Y-%m-%d %H') = DATE_FORMAT(?, '%Y-%m-%d %H')
            LIMIT 1
        ");
        $checkStmt->execute([$stationId, $measuredAt]);
        
        if ($checkStmt->fetch()) {
            logMessage("   ⏭️  Already synced for this hour");
            $stats['skipped']++;
            sleep(1);
            continue;
        }
        
        // Insert new reading
        $insertStmt = $db->prepare("
            INSERT INTO air_quality_readings 
            (station_id, aqi_index, pm25, pm10, o3, no2, so2, co, status, source_api, measured_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'aqicn', ?, NOW())
        ");
        
        $success = $insertStmt->execute([
            $stationId,
            $aqi,
            $pm25,
            $pm10,
            $o3,
            $no2,
            $so2,
            $co,
            $status,
            $measuredAt
        ]);
        
        if ($success) {
            $stats['synced']++;
            logMessage("   ✅ Synced: AQI {$aqi} ({$status}) - PM2.5: " . ($pm25 ?? 'N/A'));
            
            // Update station's updated_at timestamp
            $updateStmt = $db->prepare("
                UPDATE monitoring_stations 
                SET updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$stationId]);
            
            // TODO: Send email notification if AQI is unhealthy (>=150)
            // Requires EmailNotification::sendHighAqiAlert() method implementation
            /*
            if ($aqi >= 150 && $emailNotification) {
                try {
                    $emailSent = $emailNotification->sendHighAqiAlert(
                        $stationName,
                        $location,
                        $aqi,
                        $status,
                        $pm25,
                        $measuredAt
                    );
                    
                    if ($emailSent) {
                        $stats['notifications_sent']++;
                        logMessage("   📧 Email alert sent");
                    }
                } catch (\Exception $e) {
                    logMessage("   ⚠️  Email failed: " . $e->getMessage());
                }
            }
            */
        } else {
            logMessage("   ❌ Database insert failed");
            $stats['failed']++;
        }
        
        // Rate limiting: 1 detik per request
        sleep(1);
    }
    
} catch (\Exception $e) {
    logMessage("❌ Fatal Error: " . $e->getMessage());
    logMessage($e->getTraceAsString());
}

// Display summary
logMessage("");
logMessage(str_repeat("─", 80));
logMessage("📊 SYNC SUMMARY");
logMessage(str_repeat("─", 80));
logMessage(sprintf("Total Stations:       %d", $stats['total_stations']));
logMessage(sprintf("✅ Successfully Synced: %d", $stats['synced']));
logMessage(sprintf("❌ Failed:              %d", $stats['failed']));
logMessage(sprintf("⏭️  Skipped:             %d", $stats['skipped']));
logMessage(sprintf("📧 Notifications Sent:  %d", $stats['notifications_sent']));
logMessage("");

$successRate = $stats['total_stations'] > 0 
    ? round(($stats['synced'] / $stats['total_stations']) * 100, 2) 
    : 0;

logMessage("Success Rate: {$successRate}%");
logMessage(str_repeat("═", 80));
logMessage("✅ Sync completed at " . date('Y-m-d H:i:s'));
logMessage(str_repeat("═", 80) . "\n");

/**
 * Get AQI status based on index value
 */
function getAqiStatus($aqi) {
    if ($aqi <= 50) return 'Baik';
    if ($aqi <= 100) return 'Sedang';
    if ($aqi <= 150) return 'Tidak Sehat untuk Kelompok Sensitif';
    if ($aqi <= 200) return 'Tidak Sehat';
    if ($aqi <= 300) return 'Sangat Tidak Sehat';
    return 'Berbahaya';
}

/**
 * Log message to console and file
 */
function logMessage($message) {
    global $logFile;
    
    $timestamp = date('[Y-m-d H:i:s]');
    $logLine = $timestamp . ' ' . $message . "\n";
    
    // Output to console
    echo $message . "\n";
    
    // Write to log file
    file_put_contents($logFile, $logLine, FILE_APPEND);
}
