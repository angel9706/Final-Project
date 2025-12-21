<?php
/**
 * Migration Runner: User Favorite Stations
 * Run this file to create the user_favorite_stations table
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Starting migration: User Favorite Stations...\n\n";
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/migration_favorite_stations.sql');
    
    // Remove comments and split by semicolon
    $lines = explode("\n", $sql);
    $statements = [];
    $currentStatement = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines and comment lines
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        
        $currentStatement .= ' ' . $line;
        
        // Check if statement is complete (ends with semicolon)
        if (substr($line, -1) === ';') {
            $statements[] = trim($currentStatement);
            $currentStatement = '';
        }
    }
    
    // Execute each statement
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $db->exec($statement);
        }
    }
    
    echo "\n✅ Migration completed successfully!\n";
    echo "Table 'user_favorite_stations' has been created.\n";
    
    // Verify table creation
    $result = $db->query("SHOW TABLES LIKE 'user_favorite_stations'")->fetch();
    if ($result) {
        echo "✅ Verification: Table exists in database.\n";
        
        // Show table structure
        echo "\nTable structure:\n";
        $columns = $db->query("DESCRIBE user_favorite_stations")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "  - {$column['Field']} ({$column['Type']})\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
