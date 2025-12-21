<?php
/**
 * Migration Runner: Stations Table
 * 
 * Creates the stations table and seeds sample data
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "========================================\n";
    echo "STATIONS TABLE MIGRATION\n";
    echo "========================================\n\n";
    
    // Step 1: Run table creation migration
    echo "Step 1: Creating stations table...\n";
    $migrationSQL = file_get_contents(__DIR__ . '/migration_stations.sql');
    
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
    
    echo "✅ Stations table created successfully!\n\n";
    
    // Step 2: Check if table already has data
    $countCheck = $db->query("SELECT COUNT(*) as count FROM stations")->fetch();
    
    if ($countCheck['count'] > 0) {
        echo "⚠️  Stations table already has {$countCheck['count']} records.\n";
        echo "Skipping seed data to prevent duplicates.\n\n";
    } else {
        // Step 3: Seed sample data
        echo "Step 2: Seeding sample stations data...\n";
        $seedSQL = file_get_contents(__DIR__ . '/seed_stations.sql');
        
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
    $result = $db->query("DESCRIBE stations")->fetchAll(PDO::FETCH_COLUMN);
    echo "Table structure:\n";
    foreach ($result as $column) {
        echo "  - $column\n";
    }
    
    $count = $db->query("SELECT COUNT(*) as count FROM stations")->fetch();
    echo "\nTotal stations: {$count['count']}\n\n";
    
    // Show sample records
    echo "Sample stations:\n";
    $samples = $db->query("SELECT id, name, location, status FROM stations LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($samples as $station) {
        echo "  [{$station['id']}] {$station['name']} - {$station['location']} ({$station['status']})\n";
    }
    
    echo "\n========================================\n";
    echo "✅ MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "========================================\n\n";
    
    echo "Next steps:\n";
    echo "1. Refresh your browser dashboard\n";
    echo "2. Go to Stations page to see the stations\n";
    echo "3. Add some stations to favorites (click ⭐)\n";
    echo "4. Click favorite cards in dashboard to see details\n\n";
    
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
