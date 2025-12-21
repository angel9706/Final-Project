-- SIAPKAK Database Migration - User & Menu Management
-- Database: siapkak
-- Charset: utf8mb4_unicode_ci

-- =====================================================
-- Alter users table for better management
-- =====================================================

ALTER TABLE `users` 
ADD COLUMN `username` VARCHAR(100) UNIQUE AFTER `name`,
ADD COLUMN `phone` VARCHAR(20) AFTER `email`,
ADD COLUMN `avatar` VARCHAR(255) AFTER `phone`,
ADD COLUMN `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active' AFTER `role`,
ADD COLUMN `last_login` TIMESTAMP NULL AFTER `status`,
ADD INDEX `idx_status` (`status`),
ADD INDEX `idx_role` (`role`);

-- =====================================================
-- Create menus table
-- =====================================================

CREATE TABLE IF NOT EXISTS `menus` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT NULL DEFAULT NULL,
    `title` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(100) NOT NULL DEFAULT 'fas fa-circle',
    `url` VARCHAR(255) NOT NULL,
    `route` VARCHAR(100) NOT NULL,
    `order_index` INT DEFAULT 0,
    `is_active` BOOLEAN DEFAULT TRUE,
    `is_visible` BOOLEAN DEFAULT TRUE,
    `required_role` ENUM('admin', 'user', 'all') DEFAULT 'all',
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`parent_id`) REFERENCES `menus`(`id`) ON DELETE CASCADE,
    INDEX `idx_parent` (`parent_id`),
    INDEX `idx_order` (`order_index`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Create user_activity_logs table
-- =====================================================

CREATE TABLE IF NOT EXISTS `user_activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_action` (`user_id`, `action`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Insert default menus
-- =====================================================

INSERT INTO `menus` (`parent_id`, `title`, `icon`, `url`, `route`, `order_index`, `required_role`, `description`) VALUES
-- Dashboard
(NULL, 'Dashboard', 'fas fa-home', '/siapkak/dashboard', 'dashboard', 1, 'all', 'Dashboard overview with analytics'),

-- Monitoring (Parent)
(NULL, 'Monitoring', 'fas fa-broadcast-tower', '#', 'monitoring', 2, 'all', 'Monitoring menu group'),
-- Monitoring Children
(2, 'Stasiun', 'fas fa-map-marker-alt', '/siapkak/stations', 'stations', 1, 'all', 'Monitoring stations management'),
(2, 'Data Readings', 'fas fa-database', '/siapkak/readings', 'readings', 2, 'all', 'Air quality readings data'),

-- Reports
(NULL, 'Laporan', 'fas fa-file-alt', '/siapkak/reports', 'reports', 3, 'all', 'Reports and exports'),

-- Management (Parent) - Admin Only
(NULL, 'Manajemen', 'fas fa-cogs', '#', 'management', 4, 'admin', 'Management menu group'),
-- Management Children
(6, 'User Management', 'fas fa-users', '/siapkak/users', 'users', 1, 'admin', 'User management'),
(6, 'Menu Management', 'fas fa-bars', '/siapkak/menus', 'menus', 2, 'admin', 'Menu management'),

-- Settings
(NULL, 'Pengaturan', 'fas fa-cog', '/siapkak/settings', 'settings', 5, 'all', 'System settings');

-- =====================================================
-- Migration completed successfully!
-- =====================================================
