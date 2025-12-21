<?php

require_once __DIR__ . '/../src/config/Env.php';
require_once __DIR__ . '/../src/config/Database.php';

use App\Config\Env;
use App\Config\Database;

Env::load();

$db = Database::getInstance()->getConnection();

// Drop tables if exists
$tables = ['notifications', 'air_quality_readings', 'monitoring_stations', 'users'];

foreach ($tables as $table) {
    try {
        $db->exec("DROP TABLE IF EXISTS {$table}");
        echo "✓ Dropped table: {$table}\n";
    } catch (Exception $e) {
        echo "✗ Failed to drop {$table}: " . $e->getMessage() . "\n";
    }
}

// Create users table
try {
    $db->exec("
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('user', 'admin') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created table: users\n";
} catch (Exception $e) {
    echo "✗ Failed to create users table: " . $e->getMessage() . "\n";
}

// Create monitoring_stations table
try {
    $db->exec("
        CREATE TABLE monitoring_stations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            location VARCHAR(255) NOT NULL,
            latitude DECIMAL(10, 8) NOT NULL,
            longitude DECIMAL(11, 8) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_location (location)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created table: monitoring_stations\n";
} catch (Exception $e) {
    echo "✗ Failed to create monitoring_stations table: " . $e->getMessage() . "\n";
}

// Create air_quality_readings table
try {
    $db->exec("
        CREATE TABLE air_quality_readings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            station_id INT NOT NULL,
            aqi_index INT NOT NULL,
            pm25 DECIMAL(10, 2),
            pm10 DECIMAL(10, 2),
            o3 DECIMAL(10, 2),
            no2 DECIMAL(10, 2),
            so2 DECIMAL(10, 2),
            co DECIMAL(10, 2),
            status VARCHAR(100),
            source_api VARCHAR(50) DEFAULT 'manual',
            measured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (station_id) REFERENCES monitoring_stations(id) ON DELETE CASCADE,
            INDEX idx_station_measured (station_id, measured_at),
            INDEX idx_aqi_status (aqi_index, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created table: air_quality_readings\n";
} catch (Exception $e) {
    echo "✗ Failed to create air_quality_readings table: " . $e->getMessage() . "\n";
}

// Create notifications table
try {
    $db->exec("
        CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            station_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT,
            aqi_value INT,
            type ENUM('info', 'warning', 'danger') DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (station_id) REFERENCES monitoring_stations(id) ON DELETE CASCADE,
            INDEX idx_user_read (user_id, is_read),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created table: notifications\n";
} catch (Exception $e) {
    echo "✗ Failed to create notifications table: " . $e->getMessage() . "\n";
}

echo "\n✓ Database migration completed!\n";
