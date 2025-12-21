<?php
namespace App\Controllers;

use App\Config\Database;
use App\Config\Response;
use App\Models\AirQualityReading;
use App\Models\MonitoringStation;

class ReportController {
    private $db;
    private $readingModel;
    private $stationModel;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->readingModel = new AirQualityReading();
        $this->stationModel = new MonitoringStation();
    }

    /**
     * Generate report based on type and filters
     */
    public function generate() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $stationId = $input['station_id'] ?? null;
            $reportType = $input['report_type'] ?? 'daily';
            $date = $input['date'] ?? date('Y-m-d');

            // Calculate date range based on report type
            $dateRange = $this->calculateDateRange($reportType, $date);

            // Get report data
            $readings = $this->getReadingsForReport($stationId, $dateRange['start'], $dateRange['end']);
            $summary = $this->calculateSummary($readings);

            Response::success([
                'summary' => $summary,
                'readings' => $readings,
                'report_type' => $reportType,
                'date_range' => $dateRange,
                'station_id' => $stationId
            ], 'Laporan berhasil digenerate');

        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Download report in specified format
     */
    public function download() {
        try {
            $stationId = $_GET['station_id'] ?? null;
            $reportType = $_GET['report_type'] ?? 'daily';
            $date = $_GET['date'] ?? date('Y-m-d');
            $format = $_GET['format'] ?? 'csv';

            // Calculate date range
            $dateRange = $this->calculateDateRange($reportType, $date);

            // Get data
            $readings = $this->getReadingsForReport($stationId, $dateRange['start'], $dateRange['end']);
            $summary = $this->calculateSummary($readings);

            // Generate filename
            $stationName = $stationId ? $this->getStationName($stationId) : 'Semua_Stasiun';
            $filename = sprintf(
                'Laporan_%s_%s_%s',
                $stationName,
                ucfirst($reportType),
                date('Y-m-d', strtotime($date))
            );

            // Export based on format
            switch ($format) {
                case 'csv':
                    $this->exportCSV($readings, $summary, $filename);
                    break;
                case 'xlsx':
                    $this->exportExcel($readings, $summary, $filename, $reportType, $dateRange);
                    break;
                case 'pdf':
                    $this->exportPDF($readings, $summary, $filename, $reportType, $dateRange);
                    break;
                default:
                    Response::error('Format tidak didukung', 400);
            }

        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Calculate date range based on report type
     */
    private function calculateDateRange($type, $date) {
        $timestamp = strtotime($date);
        
        switch ($type) {
            case 'daily':
                return [
                    'start' => date('Y-m-d 00:00:00', $timestamp),
                    'end' => date('Y-m-d 23:59:59', $timestamp),
                    'label' => date('d F Y', $timestamp)
                ];
            
            case 'weekly':
                // Get Monday to Sunday of the week
                $monday = strtotime('monday this week', $timestamp);
                $sunday = strtotime('sunday this week', $timestamp);
                return [
                    'start' => date('Y-m-d 00:00:00', $monday),
                    'end' => date('Y-m-d 23:59:59', $sunday),
                    'label' => sprintf(
                        'Minggu %s - %s',
                        date('d M Y', $monday),
                        date('d M Y', $sunday)
                    )
                ];
            
            case 'monthly':
                return [
                    'start' => date('Y-m-01 00:00:00', $timestamp),
                    'end' => date('Y-m-t 23:59:59', $timestamp),
                    'label' => date('F Y', $timestamp)
                ];
            
            case 'yearly':
                return [
                    'start' => date('Y-01-01 00:00:00', $timestamp),
                    'end' => date('Y-12-31 23:59:59', $timestamp),
                    'label' => date('Y', $timestamp)
                ];
            
            default:
                return [
                    'start' => date('Y-m-d 00:00:00', $timestamp),
                    'end' => date('Y-m-d 23:59:59', $timestamp),
                    'label' => date('d F Y', $timestamp)
                ];
        }
    }

    /**
     * Get readings for report with filters
     */
    private function getReadingsForReport($stationId, $startDate, $endDate) {
        $sql = "
            SELECT 
                r.*,
                r.aqi_index as aqi,
                CASE 
                    WHEN r.aqi_index <= 50 THEN 'Baik'
                    WHEN r.aqi_index <= 100 THEN 'Sedang'
                    WHEN r.aqi_index <= 150 THEN 'Tidak Sehat untuk Kelompok Sensitif'
                    WHEN r.aqi_index <= 200 THEN 'Tidak Sehat'
                    WHEN r.aqi_index <= 300 THEN 'Sangat Tidak Sehat'
                    ELSE 'Berbahaya'
                END as aqi_status,
                s.name as station_name,
                s.location as station_location,
                s.latitude,
                s.longitude
            FROM air_quality_readings r
            INNER JOIN monitoring_stations s ON r.station_id = s.id
            WHERE r.measured_at BETWEEN ? AND ?
        ";

        $params = [$startDate, $endDate];

        if ($stationId) {
            $sql .= " AND r.station_id = ?";
            $params[] = $stationId;
        }

        $sql .= " ORDER BY r.measured_at DESC, s.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Calculate summary statistics
     */
    private function calculateSummary($readings) {
        if (empty($readings)) {
            return [
                'total_readings' => 0,
                'avg_aqi' => 0,
                'max_aqi' => 0,
                'min_aqi' => 0,
                'avg_pm25' => 0,
                'avg_pm10' => 0,
                'stations_count' => 0
            ];
        }

        $aqiValues = array_filter(array_column($readings, 'aqi'), function($val) {
            return $val !== null && $val !== '';
        });
        $pm25Values = array_filter(array_column($readings, 'pm25'), function($val) {
            return $val !== null && $val !== '';
        });
        $pm10Values = array_filter(array_column($readings, 'pm10'), function($val) {
            return $val !== null && $val !== '';
        });
        $uniqueStations = array_unique(array_column($readings, 'station_id'));

        return [
            'total_readings' => count($readings),
            'avg_aqi' => !empty($aqiValues) ? round(array_sum($aqiValues) / count($aqiValues), 1) : 0,
            'max_aqi' => !empty($aqiValues) ? max($aqiValues) : 0,
            'min_aqi' => !empty($aqiValues) ? min($aqiValues) : 0,
            'avg_pm25' => !empty($pm25Values) ? round(array_sum($pm25Values) / count($pm25Values), 1) : 0,
            'avg_pm10' => !empty($pm10Values) ? round(array_sum($pm10Values) / count($pm10Values), 1) : 0,
            'stations_count' => count($uniqueStations)
        ];
    }

    /**
     * Get station name by ID
     */
    private function getStationName($stationId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT name FROM monitoring_stations WHERE id = ?");
        $stmt->execute([$stationId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['name'] : 'Unknown Station';
    }

    /**
     * Export to CSV
     */
    private function exportCSV($readings, $summary, $filename) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Header - App Info
        fputcsv($output, ['=============================================================']);
        fputcsv($output, ['SIAPKAK - Sistem Informasi Air Quality Kalimantan']);
        fputcsv($output, ['Laporan Kualitas Udara']);
        fputcsv($output, ['=============================================================']);
        fputcsv($output, []);
        
        // Report Info
        fputcsv($output, ['Tanggal Cetak:', date('d F Y H:i:s')]);
        fputcsv($output, ['Periode Laporan:', ucfirst($_GET['report_type'] ?? 'daily')]);
        fputcsv($output, ['Tanggal:', $_GET['date'] ?? date('Y-m-d')]);
        if (!empty($_GET['station_id'])) {
            $stationName = $this->getStationName($_GET['station_id']);
            fputcsv($output, ['Stasiun:', str_replace('_', ' ', $stationName)]);
        } else {
            fputcsv($output, ['Stasiun:', 'Semua Stasiun']);
        }
        fputcsv($output, ['Jumlah Stasiun:', $summary['stations_count']]);
        fputcsv($output, []);

        // Summary section
        fputcsv($output, ['=== RINGKASAN STATISTIK ===']);
        fputcsv($output, ['Total Data Pembacaan', $summary['total_readings']]);
        fputcsv($output, ['Rata-rata AQI', $summary['avg_aqi']]);
        fputcsv($output, ['AQI Tertinggi', $summary['max_aqi']]);
        fputcsv($output, ['AQI Terendah', $summary['min_aqi']]);
        fputcsv($output, ['Rata-rata PM2.5 (µg/m³)', $summary['avg_pm25']]);
        fputcsv($output, ['Rata-rata PM10 (µg/m³)', $summary['avg_pm10']]);
        fputcsv($output, []);
        fputcsv($output, []);

        // Headers
        fputcsv($output, ['=== DATA DETAIL ===']);
        fputcsv($output, [
            'Tanggal & Waktu',
            'Stasiun',
            'Lokasi',
            'Latitude',
            'Longitude',
            'AQI',
            'Status Kualitas Udara',
            'PM2.5 (µg/m³)',
            'PM10 (µg/m³)',
            'O3 (ppb)',
            'NO2 (ppb)',
            'SO2 (ppb)',
            'CO (ppm)'
        ]);

        // Data rows
        foreach ($readings as $reading) {
            fputcsv($output, [
                $reading['measured_at'],
                $reading['station_name'],
                $reading['station_location'],
                $reading['latitude'],
                $reading['longitude'],
                $reading['aqi'],
                $reading['aqi_status'],
                $reading['pm25'] ?? '-',
                $reading['pm10'] ?? '-',
                $reading['o3'] ?? '-',
                $reading['no2'] ?? '-',
                $reading['so2'] ?? '-',
                $reading['co'] ?? '-'
            ]);
        }

        // Footer
        fputcsv($output, []);
        fputcsv($output, []);
        fputcsv($output, ['=============================================================']);
        fputcsv($output, ['Sumber Data: AQICN API & Database Internal']);
        fputcsv($output, ['Dicetak oleh: SIAPKAK']);
        fputcsv($output, ['Website: http://siapkak.local']);
        fputcsv($output, ['© 2025 SIAPKAK - Sistem Informasi Air Quality Kalimantan']);
        fputcsv($output, ['=============================================================']);

        fclose($output);
        exit;
    }

    /**
     * Export to Excel (XLSX)
     */
    private function exportExcel($readings, $summary, $filename, $reportType, $dateRange) {
        // Check if PhpSpreadsheet is available
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // Fallback to CSV if library not installed
            $this->exportCSV($readings, $summary, $filename);
            return;
        }

        require_once __DIR__ . '/../../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set sheet name
        $sheet->setTitle('Laporan Kualitas Udara');

        $row = 1;
        
        // Logo and Title Section
        $sheet->setCellValue('A' . $row, 'SIAPKAK');
        $sheet->mergeCells('A' . $row . ':M' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(20)->getColor()->setARGB('FF0066CC');
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Sistem Informasi Air Quality Kalimantan');
        $sheet->mergeCells('A' . $row . ':M' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setSize(12)->setItalic(true);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        $row++;
        $sheet->setCellValue('A' . $row, 'LAPORAN KUALITAS UDARA');
        $sheet->mergeCells('A' . $row . ':M' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE8F4FF');
        
        $row += 2;

        // Report Info Section
        $sheet->setCellValue('A' . $row, 'Tanggal Cetak:');
        $sheet->setCellValue('B' . $row, date('d F Y H:i:s'));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Periode Laporan:');
        $sheet->setCellValue('B' . $row, ucfirst($reportType) . ' - ' . $dateRange['label']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        if (!empty($_GET['station_id'])) {
            $stationName = $this->getStationName($_GET['station_id']);
            $sheet->setCellValue('A' . $row, 'Stasiun:');
            $sheet->setCellValue('B' . $row, str_replace('_', ' ', $stationName));
        } else {
            $sheet->setCellValue('A' . $row, 'Stasiun:');
            $sheet->setCellValue('B' . $row, 'Semua Stasiun');
        }
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Jumlah Stasiun:');
        $sheet->setCellValue('B' . $row, $summary['stations_count']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        
        $row += 2;

        // Summary section
        $sheet->setCellValue('A' . $row, 'RINGKASAN STATISTIK');
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF0066CC');
        $sheet->getStyle('A' . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Data Pembacaan:');
        $sheet->setCellValue('B' . $row, $summary['total_readings']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Rata-rata AQI:');
        $sheet->setCellValue('B' . $row, $summary['avg_aqi']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        
        $row++;
        $sheet->setCellValue('A' . $row, 'AQI Tertinggi:');
        $sheet->setCellValue('B' . $row, $summary['max_aqi']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->getStyle('B' . $row)->getFont()->getColor()->setARGB('FFFF0000');
        
        $row++;
        $sheet->setCellValue('A' . $row, 'AQI Terendah:');
        $sheet->setCellValue('B' . $row, $summary['min_aqi']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->getStyle('B' . $row)->getFont()->getColor()->setARGB('FF00AA00');
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Rata-rata PM2.5 (µg/m³):');
        $sheet->setCellValue('B' . $row, $summary['avg_pm25']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Rata-rata PM10 (µg/m³):');
        $sheet->setCellValue('B' . $row, $summary['avg_pm10']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        // Data table
        $row += 2;
        $sheet->setCellValue('A' . $row, 'DATA DETAIL PEMBACAAN');
        $sheet->mergeCells('A' . $row . ':P' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF0066CC');
        $sheet->getStyle('A' . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
        
        $row++;
        $headers = [
            'Tanggal & Waktu', 'Stasiun', 'Lokasi', 'Lat', 'Lon', 'AQI', 'Status',
            'PM2.5', 'PM10', 'O3', 'NO2', 'SO2', 'CO'
        ];
        
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFCCCCCC');
            $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        // Data rows
        $row++;
        foreach ($readings as $reading) {
            $sheet->setCellValue('A' . $row, $reading['measured_at']);
            $sheet->setCellValue('B' . $row, $reading['station_name']);
            $sheet->setCellValue('C' . $row, $reading['station_location']);
            $sheet->setCellValue('D' . $row, $reading['latitude']);
            $sheet->setCellValue('E' . $row, $reading['longitude']);
            $sheet->setCellValue('F' . $row, $reading['aqi']);
            $sheet->setCellValue('G' . $row, $reading['aqi_status']);
            $sheet->setCellValue('H' . $row, $reading['pm25'] ?? '-');
            $sheet->setCellValue('I' . $row, $reading['pm10'] ?? '-');
            $sheet->setCellValue('J' . $row, $reading['o3'] ?? '-');
            $sheet->setCellValue('K' . $row, $reading['no2'] ?? '-');
            $sheet->setCellValue('L' . $row, $reading['so2'] ?? '-');
            $sheet->setCellValue('M' . $row, $reading['co'] ?? '-');
            $row++;
        }
        
        // Footer
        $row += 2;
        $sheet->setCellValue('A' . $row, 'Sumber Data: AQICN API & Database Internal');
        $sheet->mergeCells('A' . $row . ':M' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setItalic(true)->setSize(9);
        
        $row++;
        $sheet->setCellValue('A' . $row, '© 2025 SIAPKAK - Sistem Informasi Air Quality Kalimantan');
        $sheet->mergeCells('A' . $row . ':M' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setItalic(true)->setSize(9);

        // Auto-size columns
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Add borders to data table
        $lastRow = $row - 2;
        $sheet->getStyle('A' . ($row - count($readings) - 3) . ':M' . $lastRow)
            ->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export to PDF
     */
    private function exportPDF($readings, $summary, $filename, $reportType, $dateRange) {
        // Check if TCPDF is available
        if (!class_exists('TCPDF')) {
            // Simple HTML to PDF fallback
            $this->exportSimplePDF($readings, $summary, $filename, $reportType, $dateRange);
            return;
        }

        require_once __DIR__ . '/../../vendor/autoload.php';

        $pdf = new \TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Document info
        $pdf->SetCreator('SIAPKAK - Sistem Informasi Air Quality Kalimantan');
        $pdf->SetAuthor('SIAPKAK');
        $pdf->SetTitle('Laporan Kualitas Udara');

        // Header/Footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Margins
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);

        // Add page
        $pdf->AddPage();
        
        // Logo and Header
        $logoPath = __DIR__ . '/../../public/img/logo.png';
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 15, 12, 20, 0, 'PNG');
        }
        
        // Title next to logo
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetTextColor(0, 102, 204);
        $pdf->SetXY(40, 12);
        $pdf->Cell(0, 7, 'SIAPKAK', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetX(40);
        $pdf->Cell(0, 5, 'Sistem Informasi Air Quality Kalimantan', 0, 1, 'L');
        
        // Reset color
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(5);

        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetFillColor(232, 244, 255);
        $pdf->Cell(0, 8, 'LAPORAN KUALITAS UDARA', 0, 1, 'C', true);
        $pdf->Ln(3);
        
        // Report Info
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(50, 5, 'Tanggal Cetak:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, date('d F Y H:i:s'), 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(50, 5, 'Periode Laporan:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, ucfirst($reportType) . ' - ' . $dateRange['label'], 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(50, 5, 'Stasiun:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        if (!empty($_GET['station_id'])) {
            $stationName = $this->getStationName($_GET['station_id']);
            $pdf->Cell(0, 5, str_replace('_', ' ', $stationName), 0, 1, 'L');
        } else {
            $pdf->Cell(0, 5, 'Semua Stasiun', 0, 1, 'L');
        }
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(50, 5, 'Jumlah Stasiun:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, $summary['stations_count'], 0, 1, 'L');
        $pdf->Ln(3);

        // Summary
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetFillColor(0, 102, 204);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 7, 'RINGKASAN STATISTIK', 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        
        $pdf->SetFont('helvetica', '', 9);
        $summaryData = [
            'Total Data Pembacaan: ' . $summary['total_readings'],
            'Rata-rata AQI: ' . $summary['avg_aqi'],
            'AQI Tertinggi: ' . $summary['max_aqi'],
            'AQI Terendah: ' . $summary['min_aqi'],
            'Rata-rata PM2.5: ' . $summary['avg_pm25'] . ' µg/m³',
            'Rata-rata PM10: ' . $summary['avg_pm10'] . ' µg/m³'
        ];
        
        foreach ($summaryData as $data) {
            $pdf->Cell(0, 5, $data, 0, 1, 'L');
        }
        $pdf->Ln(3);

        // Table
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(0, 102, 204);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 7, 'DATA DETAIL PEMBACAAN', 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(1);
        
        // Table headers
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(204, 204, 204);
        $pdf->Cell(35, 6, 'Tanggal & Waktu', 1, 0, 'C', true);
        $pdf->Cell(40, 6, 'Stasiun', 1, 0, 'C', true);
        $pdf->Cell(18, 6, 'AQI', 1, 0, 'C', true);
        $pdf->Cell(30, 6, 'Status', 1, 0, 'C', true);
        $pdf->Cell(20, 6, 'PM2.5', 1, 0, 'C', true);
        $pdf->Cell(20, 6, 'PM10', 1, 0, 'C', true);
        $pdf->Cell(20, 6, 'O3', 1, 0, 'C', true);
        $pdf->Cell(20, 6, 'NO2', 1, 0, 'C', true);
        $pdf->Cell(20, 6, 'SO2', 1, 0, 'C', true);
        $pdf->Cell(20, 6, 'CO', 1, 1, 'C', true);

        // Data
        $pdf->SetFont('helvetica', '', 7);
        $fill = false;
        foreach ($readings as $reading) {
            // Alternating row colors
            if ($fill) {
                $pdf->SetFillColor(245, 245, 245);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }
            
            // Color code AQI
            $aqiColor = [0, 0, 0];
            $aqi = (int)$reading['aqi'];
            if ($aqi > 200) {
                $aqiColor = [139, 0, 0]; // Dark red
            } elseif ($aqi > 150) {
                $aqiColor = [255, 0, 0]; // Red
            } elseif ($aqi > 100) {
                $aqiColor = [255, 140, 0]; // Orange
            } elseif ($aqi > 50) {
                $aqiColor = [255, 215, 0]; // Yellow
            } else {
                $aqiColor = [0, 128, 0]; // Green
            }
            
            $pdf->Cell(35, 5, date('d/m/Y H:i', strtotime($reading['measured_at'])), 1, 0, 'C', true);
            $pdf->Cell(40, 5, substr(str_replace('_', ' ', $reading['station_name']), 0, 25), 1, 0, 'L', true);
            
            $pdf->SetTextColor($aqiColor[0], $aqiColor[1], $aqiColor[2]);
            $pdf->Cell(18, 5, $reading['aqi'], 1, 0, 'C', true);
            $pdf->SetTextColor(0, 0, 0);
            
            $pdf->Cell(30, 5, $reading['aqi_status'], 1, 0, 'C', true);
            $pdf->Cell(20, 5, isset($reading['pm25']) ? number_format($reading['pm25'], 1) : '-', 1, 0, 'C', true);
            $pdf->Cell(20, 5, isset($reading['pm10']) ? number_format($reading['pm10'], 1) : '-', 1, 0, 'C', true);
            $pdf->Cell(20, 5, isset($reading['o3']) ? number_format($reading['o3'], 1) : '-', 1, 0, 'C', true);
            $pdf->Cell(20, 5, isset($reading['no2']) ? number_format($reading['no2'], 1) : '-', 1, 0, 'C', true);
            $pdf->Cell(20, 5, isset($reading['so2']) ? number_format($reading['so2'], 1) : '-', 1, 0, 'C', true);
            $pdf->Cell(20, 5, isset($reading['co']) ? number_format($reading['co'], 2) : '-', 1, 1, 'C', true);
            
            $fill = !$fill;
        }
        
        // Footer
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 4, 'Sumber Data: AQICN API & Database Internal', 0, 1, 'L');
        $pdf->Cell(0, 4, '© 2025 SIAPKAK - Sistem Informasi Air Quality Kalimantan', 0, 1, 'L');
        $pdf->Cell(0, 4, 'Website: http://siapkak.local', 0, 1, 'L');

        // Output
        $pdf->Output($filename . '.pdf', 'D');
        exit;
    }

    /**
     * Simple PDF export (fallback if TCPDF not available)
     */
    private function exportSimplePDF($readings, $summary, $filename, $reportType, $dateRange) {
        // Use HTML to PDF conversion
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');

        require_once __DIR__ . '/../views/report_pdf.php';
        exit;
    }
}
