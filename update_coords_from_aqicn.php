<?php
/**
 * Update koordinat dari AQICN untuk stasiun yang sudah memiliki external_id
 * Nama stasiun dan lokasi TIDAK diubah.
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use App\Config\Env;

Env::load();

$apiKey = $_ENV['AQICN_API_KEY'] ?? '';
if (empty($apiKey)) {
    die("❌ Error: AQICN_API_KEY not found in .env file\n");
}

$db = Database::getInstance()->getConnection();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  📍 UPDATE KOORDINAT DARI AQICN (Nama & Lokasi Tetap)         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$stmt = $db->query("SELECT id, name, location, external_id FROM monitoring_stations WHERE external_id IS NOT NULL ORDER BY id");
$stations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "📍 Found " . count($stations) . " stations with external_id\n\n";

$stats = ['updated' => 0, 'no_geo' => 0, 'api_error' => 0];

foreach ($stations as $index => $station) {
    $num = $index + 1;
    $name = $station['name'];
    $location = $station['location'];
    $externalId = $station['external_id'];
    
    echo "[{$num}/" . count($stations) . "] 🔍 {$name} ({$location}) - uid={$externalId}\n";
    
    $feedUrl = "https://api.waqi.info/feed/@{$externalId}/?token={$apiKey}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $feedUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        echo "   ❌ API Error (HTTP {$httpCode})\n\n";
        $stats['api_error']++;
        continue;
    }
    
    $data = json_decode($response, true);
    if (!isset($data['status']) || $data['status'] !== 'ok') {
        echo "   ⚠️  Feed status not ok\n\n";
        $stats['api_error']++;
        continue;
    }
    
    // Prefer geo from city if available
    $geo = $data['data']['city']['geo'] ?? null;
    if (!$geo || count($geo) < 2) {
        echo "   ⚠️  No geo coordinates in feed\n\n";
        $stats['no_geo']++;
        continue;
    }
    
    $lat = (float)$geo[0];
    $lon = (float)$geo[1];
    
    $updateStmt = $db->prepare("UPDATE monitoring_stations SET latitude = ?, longitude = ?, updated_at = NOW() WHERE id = ?");
    $ok = $updateStmt->execute([$lat, $lon, $station['id']]);
    
    if ($ok) {
        echo "   ✅ Updated coordinates: {$lat}, {$lon}\n\n";
        $stats['updated']++;
    } else {
        echo "   ❌ DB update failed\n\n";
    }
}

echo str_repeat("═", 70) . "\n";
echo "📊 UPDATE SUMMARY\n";
echo str_repeat("═", 70) . "\n\n";
echo "✅ Updated:      {$stats['updated']}\n";
echo "⚠️  No Geo:       {$stats['no_geo']}\n";
echo "❌ API Errors:    {$stats['api_error']}\n";
echo "\n";

if ($stats['updated'] > 0) {
    echo "🎉 Success! Coordinates now reflect AQICN stations.\n";
    echo "   Names and campus locations remain unchanged.\n";
}
echo "\n";
