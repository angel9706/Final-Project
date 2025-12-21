-- Migration: User Favorite Stations
-- Date: 2025-12-20
-- Description: Allow users to save their favorite stations for quick access

-- Create user_favorite_stations table
CREATE TABLE IF NOT EXISTS user_favorite_stations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    station_id INT NOT NULL,
    nickname VARCHAR(100) NULL COMMENT 'Optional custom name like "Home", "Office"',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorite (user_id, station_id),
    KEY idx_user_favorites (user_id),
    KEY idx_station_popularity (station_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores user favorite stations for personalized dashboard';
