-- SIAPKAK Notification System Migration
-- Add tables for Push Subscriptions and Notification Logs
-- Date: 2025-11-30

-- =====================================================
-- Create push_subscriptions table
-- =====================================================

CREATE TABLE IF NOT EXISTS `push_subscriptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `endpoint` VARCHAR(500) NOT NULL UNIQUE,
    `p256dh_key` VARCHAR(255),
    `auth_key` VARCHAR(255),
    `active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_active` (`user_id`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Create notification_logs table
-- =====================================================

CREATE TABLE IF NOT EXISTS `notification_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `notification_id` INT NOT NULL,
    `delivery_method` ENUM('push', 'email', 'both') NOT NULL,
    `status` ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    `error_message` TEXT,
    `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`notification_id`) REFERENCES `notifications`(`id`) ON DELETE CASCADE,
    INDEX `idx_notification_status` (`notification_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Alter notifications table to add delivery_status
-- =====================================================

ALTER TABLE `notifications` 
ADD COLUMN IF NOT EXISTS `delivery_status` ENUM('sent', 'failed', 'pending') DEFAULT 'pending' AFTER `type`,
ADD COLUMN IF NOT EXISTS `delivery_method` VARCHAR(50) DEFAULT NULL AFTER `delivery_status`;

-- =====================================================
-- Migration completed successfully!
-- =====================================================
-- 
-- New Tables:
-- 1. push_subscriptions - Store web push subscriptions
-- 2. notification_logs - Log all notification deliveries
--
-- Updated Tables:
-- 1. notifications - Added delivery_status and delivery_method columns
--
-- =====================================================
