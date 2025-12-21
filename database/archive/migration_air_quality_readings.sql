-- Migration: Create Air Quality Readings Table
-- Description: Table to store air quality measurements from monitoring stations

CREATE TABLE IF NOT EXISTS `air_quality_readings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `station_id` INT NOT NULL,
    `aqi` INT NULL,
    `pm25` DECIMAL(10, 2) NULL,
    `pm10` DECIMAL(10, 2) NULL,
    `co` DECIMAL(10, 2) NULL,
    `no2` DECIMAL(10, 2) NULL,
    `o3` DECIMAL(10, 2) NULL,
    `so2` DECIMAL(10, 2) NULL,
    `temperature` DECIMAL(5, 2) NULL,
    `humidity` DECIMAL(5, 2) NULL,
    `pressure` DECIMAL(7, 2) NULL,
    `wind_speed` DECIMAL(5, 2) NULL,
    `wind_direction` VARCHAR(10) NULL,
    `recorded_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_station_id` (`station_id`),
    INDEX `idx_recorded_at` (`recorded_at`),
    INDEX `idx_station_time` (`station_id`, `recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
