<?php
/**
 * Run notification system migration
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

echo "Starting notification system migration...\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $sql = file_get_contents(__DIR__ . '/migration_notifications_system.sql');
    
    // Split by semicolon and filter empty statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && strlen($stmt) > 10;
        }
    );
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $stmt) {
        try {
            $db->exec($stmt);
            $success++;
            echo "✓ Executed statement successfully\n";
        } catch (Exception $e) {
            $errors++;
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    echo "===========================================\n";
    echo "Migration Summary:\n";
    echo "  Success: $success statements\n";
    echo "  Errors:  $errors statements\n";
    echo "===========================================\n\n";
    
    if ($errors === 0) {
        echo "✅ Migration completed successfully!\n";
        echo "\nTables created:\n";
        echo "  - push_subscriptions\n";
        echo "  - notification_logs\n";
        echo "  - notifications (columns added)\n";
    } else {
        echo "⚠️  Migration completed with errors.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
