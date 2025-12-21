<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Creating user_menu_access table...\n\n";
    
    // Create table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `user_menu_access` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `menu_id` INT NOT NULL,
            `can_access` BOOLEAN DEFAULT TRUE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`menu_id`) REFERENCES `menus`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `unique_user_menu` (`user_id`, `menu_id`),
            INDEX `idx_user` (`user_id`),
            INDEX `idx_menu` (`menu_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Table created\n\n";
    
    echo "Setting up default permissions (Dashboard only for non-admin)...\n";
    
    // Insert default permissions for existing users
    $db->exec("
        INSERT INTO `user_menu_access` (`user_id`, `menu_id`, `can_access`)
        SELECT u.id, m.id, 
            CASE 
                WHEN m.route = 'dashboard' THEN TRUE
                WHEN m.required_role = 'all' THEN TRUE
                ELSE FALSE
            END as can_access
        FROM users u
        CROSS JOIN menus m
        WHERE u.role = 'user'
        ON DUPLICATE KEY UPDATE can_access = VALUES(can_access)
    ");
    echo "✓ Default permissions set\n\n";
    
    echo "\n=== User Menu Access Summary ===\n\n";
    
    $stmt = $db->query("
        SELECT 
            u.name as user_name,
            u.role,
            m.title as menu_title,
            m.route,
            uma.can_access
        FROM user_menu_access uma
        JOIN users u ON uma.user_id = u.id
        JOIN menus m ON uma.menu_id = m.id
        WHERE u.role = 'user'
        ORDER BY u.name, m.order_index
    ");
    
    $currentUser = null;
    while ($row = $stmt->fetch()) {
        if ($currentUser !== $row['user_name']) {
            if ($currentUser !== null) echo "\n";
            echo "User: {$row['user_name']} (role: {$row['role']})\n";
            echo str_repeat("-", 50) . "\n";
            $currentUser = $row['user_name'];
        }
        
        $access = $row['can_access'] ? '✓ Access' : '✗ No Access';
        echo "  {$access} - {$row['menu_title']} ({$row['route']})\n";
    }
    
    echo "\n✅ User menu access table created and configured!\n";
    echo "Non-admin users can now only access Dashboard.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
