-- Seed Data: Air Quality Readings
-- Description: Sample air quality measurements for existing stations

INSERT INTO `air_quality_readings` (`station_id`, `aqi`, `pm25`, `pm10`, `co`, `no2`, `o3`, `so2`, `temperature`, `humidity`, `pressure`, `wind_speed`, `wind_direction`, `recorded_at`) VALUES
-- Jakarta Central
(1, 95, 35.2, 52.8, 0.8, 42.5, 38.1, 12.3, 31.5, 72.0, 1012.5, 5.2, 'SE', NOW() - INTERVAL 15 MINUTE),
(1, 92, 33.8, 51.2, 0.7, 40.2, 36.8, 11.9, 31.2, 73.5, 1012.8, 4.8, 'SE', NOW() - INTERVAL 1 HOUR),
(1, 88, 31.5, 48.6, 0.6, 38.9, 35.2, 11.2, 30.8, 74.2, 1013.1, 4.5, 'S', NOW() - INTERVAL 2 HOUR),

-- Bandung City Center
(2, 68, 22.5, 38.4, 0.5, 28.6, 42.1, 8.5, 24.8, 65.0, 1015.2, 3.2, 'N', NOW() - INTERVAL 10 MINUTE),
(2, 65, 21.2, 36.8, 0.4, 27.3, 40.8, 8.1, 24.5, 66.2, 1015.5, 2.9, 'N', NOW() - INTERVAL 1 HOUR),

-- Surabaya Industrial
(3, 112, 45.8, 68.2, 1.2, 52.4, 28.5, 18.6, 33.2, 78.5, 1010.8, 6.5, 'E', NOW() - INTERVAL 20 MINUTE),
(3, 108, 43.2, 65.8, 1.1, 50.1, 27.2, 17.9, 33.0, 79.0, 1011.0, 6.2, 'E', NOW() - INTERVAL 1 HOUR),

-- Yogyakarta Airport
(4, 72, 24.8, 42.1, 0.6, 32.5, 48.2, 9.8, 28.5, 68.0, 1014.0, 4.5, 'W', NOW() - INTERVAL 5 MINUTE),
(4, 70, 23.5, 40.5, 0.5, 31.2, 46.8, 9.4, 28.2, 68.8, 1014.2, 4.2, 'W', NOW() - INTERVAL 1 HOUR),

-- Semarang Port Area
(5, 85, 30.2, 48.5, 0.8, 38.6, 32.4, 14.2, 30.5, 75.0, 1011.5, 7.2, 'NE', NOW() - INTERVAL 12 MINUTE),

-- Medan Downtown
(6, 98, 37.5, 56.2, 0.9, 45.8, 35.2, 13.8, 32.0, 71.5, 1012.0, 5.8, 'S', NOW() - INTERVAL 8 MINUTE),

-- Makassar Coastal
(7, 58, 18.5, 32.8, 0.4, 24.2, 52.1, 6.8, 29.8, 70.0, 1013.5, 8.5, 'E', NOW() - INTERVAL 18 MINUTE),

-- Bali Denpasar
(8, 62, 20.8, 35.6, 0.5, 26.5, 48.5, 7.5, 30.2, 69.5, 1013.2, 6.8, 'SE', NOW() - INTERVAL 22 MINUTE),

-- Palembang City
(9, 125, 52.8, 78.5, 1.4, 58.2, 25.8, 22.5, 32.5, 80.0, 1010.2, 4.2, 'NE', NOW() - INTERVAL 25 MINUTE),

-- Balikpapan Industrial
(10, 102, 40.5, 62.8, 1.0, 48.6, 30.2, 16.8, 31.8, 77.5, 1011.2, 5.5, 'W', NOW() - INTERVAL 30 MINUTE);
