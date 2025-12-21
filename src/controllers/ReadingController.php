<?php

namespace App\Controllers;

use App\Config\Auth;
use App\Config\Response;
use App\Config\ApiClientAqicn;
use App\Models\AirQualityReading;
use App\Models\MonitoringStation;
use App\Models\Notification;

class ReadingController
{
    private $readingModel;
    private $stationModel;
    private $notificationModel;
    private $apiClient;

    public function __construct()
    {
        $this->readingModel = new AirQualityReading();
        $this->stationModel = new MonitoringStation();
        $this->notificationModel = new Notification();
        $this->apiClient = new ApiClientAqicn();
    }

    /**
     * Get all readings with pagination
     */
    public function index()
    {
        $limit = $_GET['limit'] ?? 50;
        $page = $_GET['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $readings = $this->readingModel->getAll($limit, $offset);
        $total = $this->readingModel->count();

        Response::success([
            'readings' => $readings,
            'pagination' => [
                'total' => $total,
                'page' => (int)$page,
                'limit' => (int)$limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Get readings for specific station
     */
    public function byStation()
    {
        $stationId = $_GET['station_id'] ?? null;
        $limit = $_GET['limit'] ?? 24;
        $page = $_GET['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        if (!$stationId) {
            Response::validationError(['station_id' => 'Station ID is required']);
        }

        $station = $this->stationModel->findById($stationId);

        if (!$station) {
            Response::notFound();
        }

        $readings = $this->readingModel->getByStationId($stationId, $limit, $offset);
        $total = $this->readingModel->countByStationId($stationId);

        Response::success([
            'station' => $station,
            'readings' => $readings,
            'pagination' => [
                'total' => $total,
                'page' => (int)$page,
                'limit' => (int)$limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Get trend data for specific station
     */
    public function trend()
    {
        $stationId = $_GET['station_id'] ?? null;
        $hours = $_GET['hours'] ?? 24;

        if (!$stationId) {
            Response::validationError(['station_id' => 'Station ID is required']);
        }

        $station = $this->stationModel->findById($stationId);

        if (!$station) {
            Response::notFound();
        }

        $trend = $this->readingModel->getTrendByStationId($stationId, $hours);

        Response::success([
            'station' => $station,
            'trend' => $trend,
            'hours' => (int)$hours
        ]);
    }

    /**
     * Get single reading detail
     */
    public function show()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', null, 405);
        }

        $id = $_GET['id'] ?? null;

        if (!$id) {
            Response::validationError(['id' => 'Reading ID is required']);
        }

        $reading = $this->readingModel->findById($id);

        if (!$reading) {
            Response::notFound();
        }

        Response::success([
            'reading' => $reading
        ]);
    }

    /**
     * Create new reading (manual or from API)
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', null, 405);
        }

        // Check authentication
        $user = Auth::getCurrentUser();
        if (!$user) {
            Response::unauthorized();
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Validation
        $required = ['station_id', 'aqi_index', 'pm25', 'pm10', 'o3', 'no2', 'so2', 'co', 'status'];
        $errors = [];

        foreach ($required as $field) {
            if (!isset($input[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }

        if ($errors) {
            Response::validationError($errors);
        }

        // Check if station exists
        if (!$this->stationModel->findById($input['station_id'])) {
            Response::validationError(['station_id' => 'Station not found']);
        }

        // Create reading
        if ($this->readingModel->create(
            $input['station_id'],
            $input['aqi_index'],
            $input['pm25'],
            $input['pm10'],
            $input['o3'],
            $input['no2'],
            $input['so2'],
            $input['co'],
            $input['status'],
            $input['source_api'] ?? 'manual'
        )) {
            // Check if AQI is unhealthy and create notification
            if ($input['aqi_index'] >= 150) {
                $this->createUnhealthyNotification($input['station_id'], $input['aqi_index'], $input['status']);
            }

            Response::success(null, 'Reading created successfully', 201);
        }

        Response::error('Failed to create reading');
    }

    /**
     * Update reading
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', null, 405);
        }

        // Check authentication
        $user = Auth::getCurrentUser();
        if (!$user) {
            Response::unauthorized();
        }

        $id = $_GET['id'] ?? null;

        if (!$id) {
            Response::validationError(['id' => 'Reading ID is required']);
        }

        $reading = $this->readingModel->findById($id);

        if (!$reading) {
            Response::notFound();
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Validation
        $required = ['aqi_index', 'pm25', 'pm10', 'o3', 'no2', 'so2', 'co', 'status'];
        $errors = [];

        foreach ($required as $field) {
            if (!isset($input[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }

        if ($errors) {
            Response::validationError($errors);
        }

        if ($this->readingModel->update(
            $id,
            $input['aqi_index'],
            $input['pm25'],
            $input['pm10'],
            $input['o3'],
            $input['no2'],
            $input['so2'],
            $input['co'],
            $input['status']
        )) {
            Response::success(null, 'Reading updated successfully');
        }

        Response::error('Failed to update reading');
    }

    /**
     * Delete reading
     */
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', null, 405);
        }

        // Check authentication
        $user = Auth::getCurrentUser();
        if (!$user) {
            Response::unauthorized();
        }

        $id = $_GET['id'] ?? null;

        if (!$id) {
            Response::validationError(['id' => 'Reading ID is required']);
        }

        $reading = $this->readingModel->findById($id);

        if (!$reading) {
            Response::notFound();
        }

        if ($this->readingModel->delete($id)) {
            Response::success(null, 'Reading deleted successfully');
        }

        Response::error('Failed to delete reading');
    }

    /**
     * Fetch from AQICN API and store
     */
    public function syncFromAqicn()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', null, 405);
        }

        // Check authentication
        $user = Auth::getCurrentUser();
        if (!$user) {
            Response::unauthorized();
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['station_id']) || (empty($input['latitude']) && empty($input['city']))) {
            Response::validationError([
                'station_id' => 'Station ID is required',
                'latitude' => 'Latitude or City is required'
            ]);
        }

        $station = $this->stationModel->findById($input['station_id']);

        if (!$station) {
            Response::notFound();
        }

        // Fetch from API
        $data = null;
        
        // Try to fetch by external_id first (most accurate)
        if (!empty($station['external_id'])) {
            $url = "https://api.waqi.info/feed/@{$station['external_id']}/?token=" . ($_ENV['AQICN_API_KEY'] ?? '');
            
            try {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    $result = json_decode($response, true);
                    if (isset($result['data']) && $result['status'] === 'ok') {
                        $data = $result['data'];
                    }
                }
            } catch (\Exception $e) {
                // Continue to fallback
            }
        }
        
        // Fallback to coordinates or city
        if (!$data) {
            if (!empty($input['latitude']) && !empty($input['longitude'])) {
                $data = $this->apiClient->fetchByCoordinates($input['latitude'], $input['longitude']);
            } elseif (!empty($input['city'])) {
                $data = $this->apiClient->fetchByCity($input['city']);
            }
        }

        if (!$data || !isset($data['aqi']) || $data['aqi'] === '-') {
            Response::error('No valid AQI data available from AQICN API');
        }

        // Extract data with validation
        $aqi = is_numeric($data['aqi']) ? (int)$data['aqi'] : 0;
        $status = ApiClientAqicn::getAqiStatus($aqi);
        $iaqi = $data['iaqi'] ?? [];
        $pm25 = isset($iaqi['pm25']['v']) && is_numeric($iaqi['pm25']['v']) ? $iaqi['pm25']['v'] : 0;
        $pm10 = isset($iaqi['pm10']['v']) && is_numeric($iaqi['pm10']['v']) ? $iaqi['pm10']['v'] : 0;
        $o3 = isset($iaqi['o3']['v']) && is_numeric($iaqi['o3']['v']) ? $iaqi['o3']['v'] : 0;
        $no2 = isset($iaqi['no2']['v']) && is_numeric($iaqi['no2']['v']) ? $iaqi['no2']['v'] : 0;
        $so2 = isset($iaqi['so2']['v']) && is_numeric($iaqi['so2']['v']) ? $iaqi['so2']['v'] : 0;
        $co = isset($iaqi['co']['v']) && is_numeric($iaqi['co']['v']) ? $iaqi['co']['v'] : 0;

        // Store reading
        if ($this->readingModel->create(
            $input['station_id'],
            $aqi,
            $pm25,
            $pm10,
            $o3,
            $no2,
            $so2,
            $co,
            $status,
            'aqicn'
        )) {
            // Send notification email for every sync
            error_log("=== SYNC COMPLETE: Sending notification email ===");
            error_log("Station ID: {$input['station_id']}, AQI: {$aqi}, Status: {$status}");
            $this->sendSyncNotification($station, $aqi, $status);
            
            Response::success([
                'aqi' => $aqi,
                'status' => $status,
                'pm25' => $pm25,
                'pm10' => $pm10,
                'source' => 'aqicn',
                'notification_sent' => true
            ], 'Data synced from AQICN API and notification sent', 201);
        }

        Response::error('Failed to store reading');
    }

    /**
     * Get chart data for time-series (AQI trends by hours)
     */
    public function chartTimeSeries()
    {
        $stationId = $_GET['station_id'] ?? null;
        $hours = $_GET['hours'] ?? 24;

        if (!$stationId) {
            Response::validationError(['station_id' => 'Station ID is required']);
        }

        $station = $this->stationModel->findById($stationId);
        if (!$station) {
            Response::notFound();
        }

        // Get readings for time-series
        $readings = $this->readingModel->getTrendByStationId($stationId, $hours);

        // Format untuk Chart.js
        $labels = [];
        $aqiValues = [];
        $pm25Values = [];
        $pm10Values = [];

        foreach ($readings as $reading) {
            $labels[] = date('H:i', strtotime($reading['hour_time']));
            $aqiValues[] = $reading['avg_aqi'];
            $pm25Values[] = $reading['pm25'] ?? 0;
            $pm10Values[] = $reading['pm10'] ?? 0;
        }

        Response::success([
            'station' => $station,
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'AQI Index',
                    'data' => $aqiValues,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                    'fill' => true
                ],
                [
                    'label' => 'PM2.5 (μg/m³)',
                    'data' => $pm25Values,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'tension' => 0.4,
                    'fill' => false
                ]
            ]
        ]);
    }

    /**
     * Get chart data for status distribution (kategori)
     */
    public function chartStatusDistribution()
    {
        // Get all latest readings
        $readings = $this->readingModel->getAll(1000);

        $statusCounts = [
            'Excellent' => 0,
            'Good' => 0,
            'Moderate' => 0,
            'Poor' => 0,
            'Very Poor' => 0,
            'Severe' => 0
        ];

        // Count readings by status
        foreach ($readings as $reading) {
            $status = $reading['status'] ?? 'Unknown';
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }
        }

        // Format untuk Chart.js
        $labels = array_keys($statusCounts);
        $data = array_values($statusCounts);

        Response::success([
            'labels' => $labels,
            'data' => $data,
            'datasets' => [
                [
                    'label' => 'Status Distribution',
                    'data' => $data,
                    'backgroundColor' => [
                        '#10b981', // Excellent - green
                        '#3b82f6', // Good - blue
                        '#eab308', // Moderate - yellow
                        '#f97316', // Poor - orange
                        '#ef4444', // Very Poor - red
                        '#7c3aed'  // Severe - purple
                    ],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2
                ]
            ]
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function chartStatistics()
    {
        // Get all readings and stations
        $readings = $this->readingModel->getAll(10000);
        $stations = $this->stationModel->getAll(100);

        $totalReadings = count($readings);
        $totalStations = count($stations);
        $avgAqi = 0;
        $maxAqi = 0;
        $unhealthyCount = 0;

        // Calculate statistics
        $aqiSum = 0;
        foreach ($readings as $reading) {
            $aqi = $reading['aqi_index'] ?? 0;
            $aqiSum += $aqi;
            if ($aqi > $maxAqi) {
                $maxAqi = $aqi;
            }
            if ($aqi >= 150) {
                $unhealthyCount++;
            }
        }

        $avgAqi = $totalReadings > 0 ? round($aqiSum / $totalReadings, 2) : 0;

        Response::success([
            'statistics' => [
                'total_readings' => $totalReadings,
                'total_stations' => $totalStations,
                'average_aqi' => $avgAqi,
                'max_aqi' => $maxAqi,
                'unhealthy_readings' => $unhealthyCount,
                'healthy_percentage' => $totalReadings > 0 ? round((($totalReadings - $unhealthyCount) / $totalReadings) * 100, 2) : 0
            ]
        ]);
    }

    /**
     * Create notification for unhealthy air
     */
    private function createUnhealthyNotification($stationId, $aqi, $status)
    {
        // Get all users (in production, you might want to track subscriptions)
        // For now, we'll create a sample notification
        $title = '⚠️ Peringatan Kualitas Udara Rendah';
        $message = "Stasiun ini memiliki AQI {$aqi} ({$status}). Hindari aktivitas outdoor jika memungkinkan.";
        
        // You can store this in DB for user notifications
        // This is a placeholder for web push notification system
    }

    /**
     * Sync data for a specific station
     */
    public function syncStation()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', null, 405);
        }

        // Get station ID from URL
        $uri = $_SERVER['REQUEST_URI'];
        preg_match('/\/api\/sync\/station\/(\d+)/', $uri, $matches);
        $stationId = $matches[1] ?? null;

        if (!$stationId) {
            Response::error('Station ID is required');
        }

        // Get station
        $station = $this->stationModel->findById($stationId);
        if (!$station) {
            Response::notFound('Station not found');
        }

        try {
            // Fetch from AQICN API
            $data = $this->apiClient->fetchByCoordinates(
                $station['latitude'],
                $station['longitude'],
                false // Don't use cache
            );

            if (!$data || !isset($data['aqi'])) {
                Response::error('No data available from AQICN API');
            }

            // Extract pollutant data
            $iaqi = $data['iaqi'] ?? [];
            
            // Try to get PM2.5 and PM10 from iaqi first, then fallback to forecast
            $pm25 = $iaqi['pm25']['v'] ?? null;
            $pm10 = $iaqi['pm10']['v'] ?? null;
            
            // If not available in iaqi, try to get from forecast (today's average)
            if ($pm25 === null && isset($data['forecast']['daily']['pm25'])) {
                $today = date('Y-m-d');
                foreach ($data['forecast']['daily']['pm25'] as $forecast) {
                    if ($forecast['day'] === $today) {
                        $pm25 = $forecast['avg'] ?? null;
                        break;
                    }
                }
            }
            
            if ($pm10 === null && isset($data['forecast']['daily']['pm10'])) {
                $today = date('Y-m-d');
                foreach ($data['forecast']['daily']['pm10'] as $forecast) {
                    if ($forecast['day'] === $today) {
                        $pm10 = $forecast['avg'] ?? null;
                        break;
                    }
                }
            }

            // Prepare reading data
            $readingData = [
                'station_id' => $station['id'],
                'aqi_index' => $data['aqi'] ?? 0,
                'pm25' => $pm25,
                'pm10' => $pm10,
                'o3' => $iaqi['o3']['v'] ?? null,
                'no2' => $iaqi['no2']['v'] ?? null,
                'so2' => $iaqi['so2']['v'] ?? null,
                'co' => $iaqi['co']['v'] ?? null,
                'status' => ApiClientAqicn::getAqiStatus($data['aqi'] ?? 0),
                'source_api' => 'aqicn',
                'measured_at' => date('Y-m-d H:i:s')
            ];

            // If API provides time, use it
            if (isset($data['time']['iso'])) {
                $readingData['measured_at'] = date('Y-m-d H:i:s', strtotime($data['time']['iso']));
            }

            // Insert or update
            if ($this->readingModel->createOrUpdate($readingData)) {
                
                // Send notification for every sync (regardless of AQI level)
                $aqi = $readingData['aqi_index'];
                $this->sendSyncNotification($station, $aqi, $readingData['status']);
                
                Response::success([
                    'message' => 'Data berhasil di-sync',
                    'station' => $station['name'],
                    'aqi' => $readingData['aqi_index'],
                    'status' => $readingData['status']
                ]);
            } else {
                Response::error('Failed to save reading data');
            }

        } catch (\Exception $e) {
            Response::error('Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Send sync notification to all users (for every sync, regardless of AQI level)
     */
    private function sendSyncNotification($station, $aqi, $status)
    {
        try {
            // Get notification controller
            $notificationController = new \App\Controllers\NotificationController();
            
            // Get all active users
            $db = \App\Config\Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT id, name, email FROM users LIMIT 100");
            $users = $stmt->fetchAll();
            
            // Determine notification type based on AQI
            $type = 'info';
            if ($aqi >= 300) {
                $type = 'danger';
                $title = "🚨 BAHAYA! AQI Sangat Tinggi";
            } elseif ($aqi >= 200) {
                $type = 'danger';
                $title = "⚠️ AQI Sangat Tidak Sehat";
            } elseif ($aqi >= 150) {
                $type = 'warning';
                $title = "⚠️ AQI Tidak Sehat";
            } elseif ($aqi >= 100) {
                $type = 'warning';
                $title = "ℹ️ AQI Tidak Sehat (Sensitif)";
            } elseif ($aqi >= 50) {
                $type = 'info';
                $title = "✅ AQI Sedang";
            } else {
                $type = 'success';
                $title = "✅ AQI Baik";
            }
            
            $message = "Stasiun {$station['name']} baru saja di-sync:\n• AQI: {$aqi}\n• Status: {$status}\n• Waktu: " . date('d M Y, H:i') . " WIB";
            
            // Send notification to each user
            foreach ($users as $user) {
                $notificationController->sendNotification(
                    $user['id'],
                    $title,
                    $message,
                    $type,
                    $station['id'],
                    $aqi
                );
            }
            
            error_log("Sync notification sent for station {$station['name']}: AQI {$aqi}");
        } catch (\Exception $e) {
            error_log("Failed to send sync notification: " . $e->getMessage());
        }
    }

    /**
     * Send AQI alert to all users
     */
    private function sendAqiAlert($station, $aqi, $status)
    {
        try {
            // Get notification controller
            $notificationController = new \App\Controllers\NotificationController();
            
            // Get all users (you may want to add a method to User model to get all active users)
            $userModel = new \App\Models\User();
            $db = \App\Config\Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT id, name, email FROM users WHERE active = 1");
            $users = $stmt->fetchAll();
            
            // Send notification to each user
            foreach ($users as $user) {
                $title = "⚠️ Peringatan AQI Tinggi!";
                $message = "Stasiun {$station['name']} melaporkan AQI {$aqi} - {$status}. Hindari aktivitas outdoor dan gunakan masker jika harus keluar.";
                
                $notificationController->sendNotification(
                    $user['id'],
                    $title,
                    $message,
                    'danger',
                    $station['id'],
                    $aqi
                );
            }
            
            error_log("AQI Alert sent for station {$station['name']}: AQI {$aqi}");
        } catch (\Exception $e) {
            error_log("Failed to send AQI alert: " . $e->getMessage());
        }
    }

    /**
     * Sync data for all stations
     */
    public function syncAllStations()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', null, 405);
        }

        try {
            // Get all stations
            $stations = $this->stationModel->getAll(1000, 0);

            if (empty($stations)) {
                Response::error('No stations found');
            }

            $syncedCount = 0;
            $errors = [];

            foreach ($stations as $station) {
                try {
                    // Fetch from AQICN API
                    $data = $this->apiClient->fetchByCoordinates(
                        $station['latitude'],
                        $station['longitude'],
                        false
                    );

                    if (!$data || !isset($data['aqi'])) {
                        $errors[] = $station['name'] . ': No data';
                        continue;
                    }

                    // Extract and prepare data (same as syncStation)
                    $iaqi = $data['iaqi'] ?? [];
                    $pm25 = $iaqi['pm25']['v'] ?? null;
                    $pm10 = $iaqi['pm10']['v'] ?? null;
                    
                    if ($pm25 === null && isset($data['forecast']['daily']['pm25'])) {
                        $today = date('Y-m-d');
                        foreach ($data['forecast']['daily']['pm25'] as $forecast) {
                            if ($forecast['day'] === $today) {
                                $pm25 = $forecast['avg'] ?? null;
                                break;
                            }
                        }
                    }
                    
                    if ($pm10 === null && isset($data['forecast']['daily']['pm10'])) {
                        $today = date('Y-m-d');
                        foreach ($data['forecast']['daily']['pm10'] as $forecast) {
                            if ($forecast['day'] === $today) {
                                $pm10 = $forecast['avg'] ?? null;
                                break;
                            }
                        }
                    }

                    $readingData = [
                        'station_id' => $station['id'],
                        'aqi_index' => $data['aqi'] ?? 0,
                        'pm25' => $pm25,
                        'pm10' => $pm10,
                        'o3' => $iaqi['o3']['v'] ?? null,
                        'no2' => $iaqi['no2']['v'] ?? null,
                        'so2' => $iaqi['so2']['v'] ?? null,
                        'co' => $iaqi['co']['v'] ?? null,
                        'status' => ApiClientAqicn::getAqiStatus($data['aqi'] ?? 0),
                        'source_api' => 'aqicn',
                        'measured_at' => date('Y-m-d H:i:s')
                    ];

                    if (isset($data['time']['iso'])) {
                        $readingData['measured_at'] = date('Y-m-d H:i:s', strtotime($data['time']['iso']));
                    }

                    if ($this->readingModel->createOrUpdate($readingData)) {
                        $syncedCount++;
                    }

                    // Rate limiting
                    sleep(1);

                } catch (\Exception $e) {
                    $errors[] = $station['name'] . ': ' . $e->getMessage();
                }
            }

            Response::success([
                'message' => 'Sync completed',
                'synced' => $syncedCount,
                'total' => count($stations),
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Response::error('Sync all failed: ' . $e->getMessage());
        }
    }
}
