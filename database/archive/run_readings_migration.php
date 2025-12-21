<?php
/**
 * Migration Runner: Air Quality Readings Table
 * 
 * Creates the air_quality_readings table and seeds sample data
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "========================================\n";
    echo "AIR QUALITY READINGS MIGRATION\n";
    echo "========================================\n\n";
    
    // Step 1: Run table creation migration
    echo "Step 1: Creating air_quality_readings table...\n";
    $migrationSQL = file_get_contents(__DIR__ . '/migration_air_quality_readings.sql');
    
    // Remove SQL comments
    $lines = explode("\n", $migrationSQL);
    $cleanLines = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && !preg_match('/^--/', $line)) {
            $cleanLines[] = $line;
        }
    }
    $cleanSQL = implode("\n", $cleanLines);
    
    // Execute the entire CREATE TABLE statement
    $db->exec($cleanSQL);
    
    echo "✅ Air quality readings table created successfully!\n\n";
    
    // Step 2: Check if table already has data
    $countCheck = $db->query("SELECT COUNT(*) as count FROM air_quality_readings")->fetch();
    
    if ($countCheck['count'] > 0) {
        echo "⚠️  Table already has {$countCheck['count']} records.\n";
        echo "Skipping seed data to prevent duplicates.\n\n";
    } else {
        // Step 3: Seed sample data
        echo "Step 2: Seeding sample readings data...\n";
        $seedSQL = file_get_contents(__DIR__ . '/seed_air_quality_readings.sql');
        
        // Remove SQL comments
        $lines = explode("\n", $seedSQL);
        $cleanLines = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && !preg_match('/^--/', $line)) {
                $cleanLines[] = $line;
            }
        }
        $cleanSQL = implode("\n", $cleanLines);
        
        // Execute the INSERT statement
        $db->exec($cleanSQL);
        
        echo "✅ Sample data seeded successfully!\n\n";
    }
    
    // Step 4: Verify migration
    echo "Step 3: Verifying migration...\n";
    $result = $db->query("DESCRIBE air_quality_readings")->fetchAll(PDO::FETCH_COLUMN);
    echo "Table structure:\n";
    foreach ($result as $column) {
        echo "  - $column\n";
    }
    
    $count = $db->query("SELECT COUNT(*) as count FROM air_quality_readings")->fetch();
    echo "\nTotal readings: {$count['count']}\n\n";
    
    // Show sample records
    echo "Sample readings:\n";
    $samples = $db->query("
        SELECT r.id, s.name as station_name, r.aqi, r.pm25, r.recorded_at 
        FROM air_quality_readings r
        JOIN stations s ON r.station_id = s.id
        ORDER BY r.recorded_at DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($samples as $reading) {
        echo "  [{$reading['id']}] {$reading['station_name']} - AQI: {$reading['aqi']}, PM2.5: {$reading['pm25']} ({$reading['recorded_at']})\n";
    }
    
    echo "\n========================================\n";
    echo "✅ MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "========================================\n\n";
    
    echo "Next steps:\n";
    echo "1. Refresh your browser dashboard\n";
    echo "2. Favorite stations will now show real-time data\n";
    echo "3. Click favorite cards to see detailed readings\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Unexpected error!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
