-- =====================================================
-- Add User Menu Access Permission Table
-- =====================================================

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Set default: Non-admin users only have Dashboard access
-- =====================================================

-- Get all non-admin users
INSERT INTO `user_menu_access` (`user_id`, `menu_id`, `can_access`)
SELECT u.id, m.id, 
    CASE 
        WHEN m.route = 'dashboard' THEN TRUE
        ELSE FALSE
    END as can_access
FROM users u
CROSS JOIN menus m
WHERE u.role = 'user'
ON DUPLICATE KEY UPDATE can_access = VALUES(can_access);

-- Verify
SELECT 
    u.name as user_name,
    u.role,
    m.title as menu_title,
    uma.can_access
FROM user_menu_access uma
JOIN users u ON uma.user_id = u.id
JOIN menus m ON uma.menu_id = m.id
WHERE u.role = 'user'
ORDER BY u.name, m.order_index;
