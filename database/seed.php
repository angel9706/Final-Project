<?php

require_once __DIR__ . '/../src/config/Env.php';
require_once __DIR__ . '/../src/config/Database.php';

use App\Config\Env;
use App\Config\Database;

Env::load();

$db = Database::getInstance()->getConnection();

// Seed users
$users = [
    ['name' => 'Admin User', 'email' => 'admin@siapkak.local', 'password' => 'password123', 'role' => 'admin'],
    ['name' => 'John Doe', 'email' => 'john@siapkak.local', 'password' => 'password123', 'role' => 'user'],
    ['name' => 'Jane Smith', 'email' => 'jane@siapkak.local', 'password' => 'password123', 'role' => 'user'],
];

foreach ($users as $user) {
    try {
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $hashedPassword = password_hash($user['password'], PASSWORD_BCRYPT);
        $stmt->execute([$user['name'], $user['email'], $hashedPassword, $user['role']]);
        echo "✓ Inserted user: {$user['email']}\n";
    } catch (Exception $e) {
        echo "✗ Failed to insert user {$user['email']}: " . $e->getMessage() . "\n";
    }
}

// Seed monitoring stations - Current data from website
$stations = [
    [
        'name' => 'Universitas Indonesia (UI)',
        'location' => 'Depok, Jawa Barat',
        'latitude' => -6.36228100,
        'longitude' => 106.82667400,
        'description' => ''
    ],
    [
        'name' => 'Universitas Gadjah Mada (UGM)',
        'location' => 'Yogyakarta',
        'latitude' => -7.77071700,
        'longitude' => 110.37772400,
        'description' => ''
    ],
    [
        'name' => 'Institut Teknologi Bandung (ITB)',
        'location' => 'Bandung',
        'latitude' => -6.88948000,
        'longitude' => 107.61077000,
        'description' => ''
    ],
    [
        'name' => 'Universitas Padjadjaran (UNPAD)',
        'location' => 'Jatinagor, Bandung',
        'latitude' => -6.89333300,
        'longitude' => 107.61694400,
        'description' => ''
    ],
    [
        'name' => 'Universitas Airlangga (UNAIR)',
        'location' => 'Surabaya',
        'latitude' => -7.26852800,
        'longitude' => 112.78415800,
        'description' => ''
    ],
    [
        'name' => 'Universitas Brawijaya (UB)',
        'location' => 'Malang, Jawa Timur',
        'latitude' => -7.96688900,
        'longitude' => 112.63205600,
        'description' => ''
    ],
    [
        'name' => 'Universitas Sumatera Utara (USU)',
        'location' => 'Medan, Sumatera Utara',
        'latitude' => -3.56174100,
        'longitude' => 98.65605200,
        'description' => ''
    ],
    [
        'name' => 'Universitas Lampung (UNILA)',
        'location' => 'Bandar Lampung, Lampung',
        'latitude' => -5.36361600,
        'longitude' => 105.24256500,
        'description' => ''
    ],
    [
        'name' => 'Universitas Sriwijaya (UNSRI)',
        'location' => 'Palembang, Sumatera Selatan',
        'latitude' => -2.96400940,
        'longitude' => 104.75122150,
        'description' => ''
    ],
    [
        'name' => 'Universitas Buana Perjuangan (UBP)',
        'location' => 'karawang',
        'latitude' => -6.32359720,
        'longitude' => 107.30132530,
        'description' => ''
    ],
];

foreach ($stations as $station) {
    try {
        $stmt = $db->prepare("
            INSERT INTO monitoring_stations (name, location, latitude, longitude, description) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $station['name'],
            $station['location'],
            $station['latitude'],
            $station['longitude'],
            $station['description']
        ]);
        echo "✓ Inserted station: {$station['name']}\n";
    } catch (Exception $e) {
        echo "✗ Failed to insert station: " . $e->getMessage() . "\n";
    }
}

// Seed air quality readings
$readings = [
    // Station 1 - Good to Moderate
    ['station_id' => 1, 'aqi_index' => 45, 'pm25' => 12.5, 'pm10' => 20.3, 'o3' => 25, 'no2' => 15, 'so2' => 8, 'co' => 0.5, 'status' => 'Baik'],
    ['station_id' => 1, 'aqi_index' => 78, 'pm25' => 22.1, 'pm10' => 35.8, 'o3' => 30, 'no2' => 20, 'so2' => 10, 'co' => 0.8, 'status' => 'Sedang'],
    ['station_id' => 1, 'aqi_index' => 92, 'pm25' => 28.5, 'pm10' => 42.3, 'o3' => 35, 'no2' => 25, 'so2' => 12, 'co' => 1.0, 'status' => 'Sedang'],
    
    // Station 2 - Moderate to Unhealthy
    ['station_id' => 2, 'aqi_index' => 65, 'pm25' => 18.3, 'pm10' => 28.5, 'o3' => 28, 'no2' => 18, 'so2' => 9, 'co' => 0.7, 'status' => 'Sedang'],
    ['station_id' => 2, 'aqi_index' => 115, 'pm25' => 35.2, 'pm10' => 52.5, 'o3' => 40, 'no2' => 30, 'so2' => 15, 'co' => 1.2, 'status' => 'Tidak Sehat untuk Kelompok Sensitif'],
    ['station_id' => 2, 'aqi_index' => 165, 'pm25' => 48.6, 'pm10' => 68.3, 'o3' => 50, 'no2' => 38, 'so2' => 18, 'co' => 1.5, 'status' => 'Tidak Sehat'],
    
    // Station 3 - Healthy
    ['station_id' => 3, 'aqi_index' => 35, 'pm25' => 10.1, 'pm10' => 15.2, 'o3' => 20, 'no2' => 12, 'so2' => 6, 'co' => 0.4, 'status' => 'Baik'],
    ['station_id' => 3, 'aqi_index' => 55, 'pm25' => 16.5, 'pm10' => 25.8, 'o3' => 26, 'no2' => 16, 'so2' => 8, 'co' => 0.6, 'status' => 'Sedang'],
];

foreach ($readings as $reading) {
    try {
        $stmt = $db->prepare("
            INSERT INTO air_quality_readings 
            (station_id, aqi_index, pm25, pm10, o3, no2, so2, co, status, source_api) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'manual')
        ");
        $stmt->execute([
            $reading['station_id'],
            $reading['aqi_index'],
            $reading['pm25'],
            $reading['pm10'],
            $reading['o3'],
            $reading['no2'],
            $reading['so2'],
            $reading['co'],
            $reading['status']
        ]);
        echo "✓ Inserted reading for station {$reading['station_id']}: AQI {$reading['aqi_index']}\n";
    } catch (Exception $e) {
        echo "✗ Failed to insert reading: " . $e->getMessage() . "\n";
    }
}

// Seed notifications
$notifications = [
    ['user_id' => 2, 'station_id' => 2, 'title' => 'Peringatan Kualitas Udara', 'message' => 'Stasiun Gedung B memiliki AQI Tidak Sehat', 'aqi_value' => 165, 'type' => 'danger'],
    ['user_id' => 3, 'station_id' => 2, 'title' => 'Peringatan Kualitas Udara', 'message' => 'Stasiun Gedung B memiliki AQI Tidak Sehat', 'aqi_value' => 165, 'type' => 'danger'],
];

foreach ($notifications as $notif) {
    try {
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, station_id, title, message, aqi_value, type) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $notif['user_id'],
            $notif['station_id'],
            $notif['title'],
            $notif['message'],
            $notif['aqi_value'],
            $notif['type']
        ]);
        echo "✓ Inserted notification\n";
    } catch (Exception $e) {
        echo "✗ Failed to insert notification: " . $e->getMessage() . "\n";
    }
}

echo "\n✓ Database seeding completed!\n";
echo "\nDefault Credentials:\n";
echo "Admin Email: admin@siapkak.local\n";
echo "Password: password123\n";
