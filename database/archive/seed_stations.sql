-- Seed Data: Monitoring Stations
-- Description: Sample air quality monitoring stations in Indonesia

INSERT INTO `stations` (`name`, `location`, `latitude`, `longitude`, `external_id`, `description`, `status`) VALUES
('Jakarta Central', 'Jakarta Pusat, DKI Jakarta', -6.2088, 106.8456, 'JKT-001', 'Stasiun pemantauan kualitas udara di pusat Jakarta, dekat dengan area perkantoran dan komersial.', 'active'),
('Bandung City Center', 'Bandung, Jawa Barat', -6.9175, 107.6191, 'BDG-001', 'Stasiun monitoring udara di pusat kota Bandung, area dengan kepadatan lalu lintas tinggi.', 'active'),
('Surabaya Industrial', 'Surabaya, Jawa Timur', -7.2575, 112.7521, 'SBY-001', 'Stasiun di area industri Surabaya untuk monitoring polusi udara dari aktivitas pabrik.', 'active'),
('Yogyakarta Airport', 'Yogyakarta, DIY', -7.7956, 110.3695, 'YOG-001', 'Stasiun pemantauan di dekat bandara internasional Yogyakarta.', 'active'),
('Semarang Port Area', 'Semarang, Jawa Tengah', -6.9667, 110.4167, 'SMG-001', 'Monitoring udara di area pelabuhan Tanjung Emas, Semarang.', 'active'),
('Medan Downtown', 'Medan, Sumatera Utara', 3.5952, 98.6722, 'MDN-001', 'Stasiun di pusat kota Medan dengan traffic padat.', 'active'),
('Makassar Coastal', 'Makassar, Sulawesi Selatan', -5.1477, 119.4327, 'MKS-001', 'Pemantauan kualitas udara di area pesisir Makassar.', 'active'),
('Bali Denpasar', 'Denpasar, Bali', -8.6705, 115.2126, 'DPS-001', 'Stasiun monitoring di pusat kota Denpasar, Bali.', 'active'),
('Palembang City', 'Palembang, Sumatera Selatan', -2.9761, 104.7754, 'PLM-001', 'Monitoring udara di kota Palembang, sering terkena dampak kebakaran hutan.', 'active'),
('Balikpapan Industrial', 'Balikpapan, Kalimantan Timur', -1.2379, 116.8529, 'BPN-001', 'Stasiun di area industri minyak dan gas Balikpapan.', 'active');
