<!DOCTYPE html>
<html>
<head>
    <title>Laporan Kualitas Udara</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        h1 { text-align: center; font-size: 16px; margin-bottom: 5px; }
        h2 { font-size: 12px; margin: 10px 0 5px 0; }
        .subtitle { text-align: center; font-size: 10px; margin-bottom: 15px; color: #666; }
        .summary { margin: 20px 0; padding: 10px; background: #f5f5f5; }
        .summary-item { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 8px; }
        th { background: #4a5568; color: white; padding: 8px 4px; text-align: left; }
        td { border: 1px solid #ddd; padding: 6px 4px; }
        tr:nth-child(even) { background: #f9f9f9; }
        .footer { margin-top: 20px; text-align: center; font-size: 8px; color: #666; }
    </style>
</head>
<body>
    <h1>LAPORAN KUALITAS UDARA</h1>
    <div class="subtitle">
        Periode: <?= ucfirst($reportType) ?> - <?= $dateRange['label'] ?><br>
        Generated: <?= date('d F Y H:i:s') ?>
    </div>

    <div class="summary">
        <h2>Ringkasan</h2>
        <div class="summary-item"><strong>Total Data:</strong> <?= $summary['total_readings'] ?></div>
        <div class="summary-item"><strong>Rata-rata AQI:</strong> <?= $summary['avg_aqi'] ?></div>
        <div class="summary-item"><strong>AQI Tertinggi:</strong> <?= $summary['max_aqi'] ?></div>
        <div class="summary-item"><strong>AQI Terendah:</strong> <?= $summary['min_aqi'] ?></div>
        <div class="summary-item"><strong>Rata-rata PM2.5:</strong> <?= $summary['avg_pm25'] ?> µg/m³</div>
        <div class="summary-item"><strong>Rata-rata PM10:</strong> <?= $summary['avg_pm10'] ?> µg/m³</div>
    </div>

    <h2>Data Detail</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Stasiun</th>
                <th>Lokasi</th>
                <th>AQI</th>
                <th>Status</th>
                <th>PM2.5</th>
                <th>PM10</th>
                <th>O3</th>
                <th>NO2</th>
                <th>SO2</th>
                <th>CO</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($readings as $reading): ?>
            <tr>
                <td><?= date('Y-m-d H:i', strtotime($reading['measured_at'])) ?></td>
                <td><?= htmlspecialchars($reading['station_name']) ?></td>
                <td><?= htmlspecialchars($reading['station_location']) ?></td>
                <td><?= $reading['aqi'] ?></td>
                <td><?= $reading['aqi_status'] ?></td>
                <td><?= $reading['pm25'] ?? '-' ?></td>
                <td><?= $reading['pm10'] ?? '-' ?></td>
                <td><?= $reading['o3'] ?? '-' ?></td>
                <td><?= $reading['no2'] ?? '-' ?></td>
                <td><?= $reading['so2'] ?? '-' ?></td>
                <td><?= $reading['co'] ?? '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        SIAPKAK - Sistem Information Air Pollution Kampus Area Karawang
    </div>
</body>
</html>
