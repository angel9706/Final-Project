-- SIAPKAK Database Migration SQL Script
-- Database: siapkak
-- Charset: utf8mb4_unicode_ci
-- For: Air Quality Monitoring System

-- =====================================================
-- Drop existing tables (if any)
-- =====================================================

DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `air_quality_readings`;
DROP TABLE IF EXISTS `monitoring_stations`;
DROP TABLE IF EXISTS `users`;

-- =====================================================
-- Create users table
-- =====================================================

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'admin') DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Create monitoring_stations table
-- =====================================================

CREATE TABLE `monitoring_stations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Create air_quality_readings table
-- =====================================================

CREATE TABLE `air_quality_readings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `station_id` INT NOT NULL,
    `aqi_index` INT NOT NULL,
    `pm25` DECIMAL(10, 2),
    `pm10` DECIMAL(10, 2),
    `o3` DECIMAL(10, 2),
    `no2` DECIMAL(10, 2),
    `so2` DECIMAL(10, 2),
    `co` DECIMAL(10, 2),
    `status` VARCHAR(100),
    `source_api` VARCHAR(50) DEFAULT 'manual',
    `measured_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`station_id`) REFERENCES `monitoring_stations`(`id`) ON DELETE CASCADE,
    INDEX `idx_station_measured` (`station_id`, `measured_at`),
    INDEX `idx_aqi_status` (`aqi_index`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Create notifications table
-- =====================================================

CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `station_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT,
    `aqi_value` INT,
    `type` ENUM('info', 'warning', 'danger') DEFAULT 'info',
    `is_read` BOOLEAN DEFAULT FALSE,
    `read_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`station_id`) REFERENCES `monitoring_stations`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_read` (`user_id`, `is_read`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Migration completed successfully!
-- =====================================================
-- 
-- Tables created:
-- 1. users (4 columns + timestamps)
-- 2. monitoring_stations (6 columns + timestamps)
-- 3. air_quality_readings (13 columns + timestamps)
-- 4. notifications (11 columns + timestamps)
--
-- Relationships:
-- - air_quality_readings -> monitoring_stations (station_id)
-- - notifications -> users (user_id)
-- - notifications -> monitoring_stations (station_id)
--
-- Charset: utf8mb4_unicode_ci (supports emoji, special characters)
-- Engine: InnoDB (supports foreign keys, transactions)
--
-- =====================================================
