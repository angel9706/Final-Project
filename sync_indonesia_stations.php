<?php
/**
 * AQICN Station Discovery - Indonesia Cities
 * 
 * Auto-discover dan register stasiun monitoring kualitas udara
 * dari seluruh kota di Indonesia via AQICN API
 * 
 * Usage: php sync_indonesia_stations.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use App\Config\Env;

// Load environment
Env::load();

// AQICN API Configuration
$apiKey = $_ENV['AQICN_API_KEY'] ?? '';
if (empty($apiKey)) {
    die("❌ Error: AQICN_API_KEY not found in .env file\n");
}

// Database connection
$db = Database::getInstance()->getConnection();

// Daftar kota-kota di Indonesia untuk discovery
$indonesiaCities = [
    // Pulau Jawa
    'Jakarta', 'Bekasi', 'Depok', 'Tangerang', 'Bogor', 'Bandung', 
    'Cirebon', 'Sukabumi', 'Tasikmalaya', 'Karawang', 'Purwakarta',
    'Semarang', 'Surakarta', 'Yogyakarta', 'Magelang', 'Salatiga',
    'Surabaya', 'Malang', 'Sidoarjo', 'Gresik', 'Mojokerto',
    
    // Pulau Sumatra
    'Medan', 'Binjai', 'Pematangsiantar', 'Tanjungbalai',
    'Padang', 'Bukittinggi', 'Pariaman', 'Payakumbuh',
    'Palembang', 'Lubuklinggau', 'Prabumulih',
    'Jambi', 'Pekanbaru', 'Dumai',
    'Bandar Lampung', 'Metro',
    'Bengkulu',
    
    // Pulau Kalimantan
    'Pontianak', 'Singkawang',
    'Palangkaraya',
    'Banjarmasin', 'Banjarbaru',
    'Samarinda', 'Balikpapan', 'Bontang',
    'Tarakan',
    
    // Pulau Sulawesi
    'Makassar', 'Pare-Pare', 'Palopo',
    'Manado', 'Bitung', 'Tomohon', 'Kotamobagu',
    'Palu', 'Gorontalo', 'Kendari', 'Baubau',
    
    // Pulau Bali & Nusa Tenggara
    'Denpasar', 'Singaraja',
    'Mataram', 'Bima',
    'Kupang',
    
    // Pulau Maluku & Papua
    'Ambon', 'Tual',
    'Ternate', 'Tidore',
    'Jayapura', 'Sorong', 'Manokwari'
];

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  🇮🇩  AQICN STATION DISCOVERY - INDONESIA CITIES              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "📍 Total cities to scan: " . count($indonesiaCities) . "\n";
echo "🔑 API Key: " . substr($apiKey, 0, 20) . "...\n";
echo "⏱️  Estimated time: " . ceil(count($indonesiaCities) * 2 / 60) . " minutes\n";
echo "\n";
echo "Starting discovery...\n";
echo str_repeat("─", 70) . "\n\n";

$stats = [
    'total_scanned' => 0,
    'stations_found' => 0,
    'stations_added' => 0,
    'stations_updated' => 0,
    'stations_skipped' => 0,
    'api_errors' => 0
];

foreach ($indonesiaCities as $index => $city) {
    $cityNumber = $index + 1;
    $stats['total_scanned']++;
    
    echo sprintf("[%02d/%02d] 🔍 Scanning: %-20s ", $cityNumber, count($indonesiaCities), $city);
    
    // Query AQICN API untuk mencari stasiun di kota ini
    $searchUrl = "https://api.waqi.info/search/?token={$apiKey}&keyword=" . urlencode($city . " Indonesia");
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $searchUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        echo "❌ API Error\n";
        $stats['api_errors']++;
        sleep(2); // Rate limiting
        continue;
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['status']) || $data['status'] !== 'ok') {
        echo "⚠️  No data\n";
        sleep(2);
        continue;
    }
    
    $stations = $data['data'] ?? [];
    $stationsCount = count($stations);
    
    if ($stationsCount === 0) {
        echo "⚠️  No stations\n";
        sleep(2);
        continue;
    }
    
    echo "✓ Found {$stationsCount} station(s)\n";
    $stats['stations_found'] += $stationsCount;
    
    // Process each station
    foreach ($stations as $station) {
        $stationUid = $station['uid'] ?? null;
        $stationName = $station['station']['name'] ?? 'Unknown';
        $latitude = $station['station']['geo'][0] ?? null;
        $longitude = $station['station']['geo'][1] ?? null;
        
        if (!$stationUid || !$latitude || !$longitude) {
            $stats['stations_skipped']++;
            continue;
        }
        
        // Check if station already exists
        $stmt = $db->prepare("SELECT id FROM monitoring_stations WHERE external_id = ?");
        $stmt->execute([$stationUid]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing station
            $updateStmt = $db->prepare("
                UPDATE monitoring_stations 
                SET name = ?, 
                    location = ?, 
                    latitude = ?, 
                    longitude = ?,
                    updated_at = NOW()
                WHERE external_id = ?
            ");
            $updateStmt->execute([
                $stationName,
                $city,
                $latitude,
                $longitude,
                $stationUid
            ]);
            $stats['stations_updated']++;
            echo "   ├─ 🔄 Updated: {$stationName}\n";
        } else {
            // Insert new station
            $insertStmt = $db->prepare("
                INSERT INTO monitoring_stations 
                (name, location, latitude, longitude, external_id, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())
            ");
            $insertStmt->execute([
                $stationName,
                $city,
                $latitude,
                $longitude,
                $stationUid
            ]);
            $stats['stations_added']++;
            echo "   ├─ ✨ Added: {$stationName}\n";
        }
    }
    
    echo "\n";
    
    // Rate limiting: 2 detik per request untuk menghormati API limits
    sleep(2);
}

// Display summary
echo str_repeat("═", 70) . "\n";
echo "📊 DISCOVERY SUMMARY\n";
echo str_repeat("═", 70) . "\n\n";

echo "Cities Scanned:      {$stats['total_scanned']}\n";
echo "Stations Found:      {$stats['stations_found']}\n";
echo "Stations Added:      ✨ {$stats['stations_added']}\n";
echo "Stations Updated:    🔄 {$stats['stations_updated']}\n";
echo "Stations Skipped:    ⚠️  {$stats['stations_skipped']}\n";
echo "API Errors:          ❌ {$stats['api_errors']}\n";
echo "\n";

$totalProcessed = $stats['stations_added'] + $stats['stations_updated'];
echo "✅ Total Processed:   {$totalProcessed} stations\n";
echo "\n";

// Display top cities by station count
echo "🏆 TOP CITIES BY STATION COUNT:\n";
$topCitiesStmt = $db->query("
    SELECT location, COUNT(*) as station_count 
    FROM monitoring_stations 
    WHERE external_id IS NOT NULL
    GROUP BY location 
    ORDER BY station_count DESC 
    LIMIT 10
");
$topCities = $topCitiesStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($topCities as $idx => $cityData) {
    $rank = $idx + 1;
    echo sprintf("   %2d. %-25s %3d stations\n", $rank, $cityData['location'], $cityData['station_count']);
}

echo "\n";
echo "🎉 Discovery completed successfully!\n";
echo "\n";
echo "Next steps:\n";
echo "  1. Run: php cron_sync_data.php  (to sync AQI data)\n";
echo "  2. View stations at: http://localhost/siapkak/dashboard\n";
echo "\n";
