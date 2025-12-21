<?php

namespace App\Controllers;

use App\Config\Auth;
use App\Config\Response;
use App\Models\AirQualityReading;
use App\Models\MonitoringStation;
use App\Models\User;

class AnalyticsController
{
    private $readingModel;
    private $stationModel;
    private $userModel;

    public function __construct()
    {
        $this->readingModel = new AirQualityReading();
        $this->stationModel = new MonitoringStation();
        $this->userModel = new User();
    }

    /**
     * Get dashboard analytics summary
     */
    public function dashboard()
    {
        try {
            $allReadings = $this->readingModel->getAll(10000);
            $allStations = $this->stationModel->getAll(100);
            $totalUsers = $this->userModel->count();

            // Basic statistics
            $totalReadings = count($allReadings);
            $totalStations = count($allStations);
            $avgAqi = 0;
            $maxAqi = 0;
            $minAqi = 999;
            $unhealthyCount = 0;

            // Calculate from readings
            $aqiSum = 0;
            foreach ($allReadings as $reading) {
                $aqi = $reading['aqi_index'] ?? 0;
                $aqiSum += $aqi;
                $maxAqi = max($maxAqi, $aqi);
                $minAqi = min($minAqi, $aqi);
                if ($aqi >= 150) {
                    $unhealthyCount++;
                }
            }

            $avgAqi = $totalReadings > 0 ? round($aqiSum / $totalReadings, 2) : 0;

            Response::success([
                'summary' => [
                    'total_readings' => $totalReadings,
                    'total_stations' => $totalStations,
                    'total_users' => $totalUsers,
                    'average_aqi' => $avgAqi,
                    'max_aqi' => $maxAqi,
                    'min_aqi' => $minAqi < 999 ? $minAqi : 0,
                    'unhealthy_readings' => $unhealthyCount,
                    'healthy_percentage' => $totalReadings > 0 ? round((($totalReadings - $unhealthyCount) / $totalReadings) * 100, 2) : 0
                ],
                'top_stations' => $this->getTopStations(),
                'status_distribution' => $this->getStatusDistribution()
            ]);
        } catch (\Exception $e) {
            Response::error('Failed to get analytics: ' . $e->getMessage());
        }
    }

    /**
     * Get top 5 stations by average AQI
     */
    public function getTopStations()
    {
        try {
            $stations = $this->stationModel->getAll(100);
            $stationStats = [];

            foreach ($stations as $station) {
                $readings = $this->readingModel->getByStationId($station['id'], 100);
                
                if (empty($readings)) continue;

                $aqiSum = 0;
                $maxAqi = 0;
                foreach ($readings as $reading) {
                    $aqi = $reading['aqi_index'] ?? 0;
                    $aqiSum += $aqi;
                    $maxAqi = max($maxAqi, $aqi);
                }

                $avgAqi = round($aqiSum / count($readings), 2);

                $stationStats[] = [
                    'id' => $station['id'],
                    'name' => $station['name'],
                    'location' => $station['location'],
                    'average_aqi' => $avgAqi,
                    'max_aqi' => $maxAqi,
                    'reading_count' => count($readings)
                ];
            }

            // Sort by average AQI descending
            usort($stationStats, function($a, $b) {
                return $b['average_aqi'] <=> $a['average_aqi'];
            });

            return array_slice($stationStats, 0, 5);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get status distribution across all readings
     */
    public function getStatusDistribution()
    {
        try {
            $readings = $this->readingModel->getAll(10000);

            $distribution = [
                'Excellent' => 0,
                'Good' => 0,
                'Moderate' => 0,
                'Poor' => 0,
                'Very Poor' => 0,
                'Severe' => 0
            ];

            foreach ($readings as $reading) {
                $status = $reading['status'] ?? 'Unknown';
                if (isset($distribution[$status])) {
                    $distribution[$status]++;
                }
            }

            return $distribution;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get daily average AQI for specific station
     */
    public function dailyTrend()
    {
        $stationId = $_GET['station_id'] ?? null;
        $days = $_GET['days'] ?? 30;

        if (!$stationId) {
            Response::validationError(['station_id' => 'Station ID is required']);
        }

        try {
            $readings = $this->readingModel->getByStationId($stationId, 10000);

            // Group by date
            $dailyData = [];
            foreach ($readings as $reading) {
                $date = date('Y-m-d', strtotime($reading['created_at']));
                
                if (!isset($dailyData[$date])) {
                    $dailyData[$date] = [
                        'aqi_values' => [],
                        'pm25_values' => [],
                        'pm10_values' => []
                    ];
                }

                $dailyData[$date]['aqi_values'][] = $reading['aqi_index'] ?? 0;
                $dailyData[$date]['pm25_values'][] = $reading['pm25'] ?? 0;
                $dailyData[$date]['pm10_values'][] = $reading['pm10'] ?? 0;
            }

            // Calculate daily averages
            $trend = [];
            foreach ($dailyData as $date => $data) {
                $trend[] = [
                    'date' => $date,
                    'avg_aqi' => round(array_sum($data['aqi_values']) / count($data['aqi_values']), 2),
                    'avg_pm25' => round(array_sum($data['pm25_values']) / count($data['pm25_values']), 2),
                    'avg_pm10' => round(array_sum($data['pm10_values']) / count($data['pm10_values']), 2),
                    'max_aqi' => max($data['aqi_values']),
                    'min_aqi' => min($data['aqi_values'])
                ];
            }

            // Sort by date
            usort($trend, function($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });

            // Limit to requested days
            $trend = array_slice($trend, -$days);

            Response::success([
                'station_id' => $stationId,
                'days' => $days,
                'data' => $trend
            ]);
        } catch (\Exception $e) {
            Response::error('Failed to get trend data: ' . $e->getMessage());
        }
    }

    /**
     * Get hourly trend for current day
     */
    public function hourlyTrend()
    {
        $stationId = $_GET['station_id'] ?? null;

        if (!$stationId) {
            Response::validationError(['station_id' => 'Station ID is required']);
        }

        try {
            $readings = $this->readingModel->getByStationId($stationId, 500);

            // Group by hour
            $hourlyData = [];
            foreach ($readings as $reading) {
                $hour = date('H:00', strtotime($reading['created_at']));
                $date = date('Y-m-d', strtotime($reading['created_at']));
                
                // Only today
                if ($date !== date('Y-m-d')) continue;

                if (!isset($hourlyData[$hour])) {
                    $hourlyData[$hour] = [
                        'aqi_values' => [],
                        'pm25_values' => []
                    ];
                }

                $hourlyData[$hour]['aqi_values'][] = $reading['aqi_index'] ?? 0;
                $hourlyData[$hour]['pm25_values'][] = $reading['pm25'] ?? 0;
            }

            // Calculate hourly averages
            $trend = [];
            foreach ($hourlyData as $hour => $data) {
                $trend[] = [
                    'hour' => $hour,
                    'avg_aqi' => round(array_sum($data['aqi_values']) / count($data['aqi_values']), 2),
                    'avg_pm25' => round(array_sum($data['pm25_values']) / count($data['pm25_values']), 2),
                    'max_aqi' => max($data['aqi_values']),
                    'min_aqi' => min($data['aqi_values'])
                ];
            }

            Response::success([
                'station_id' => $stationId,
                'data' => $trend
            ]);
        } catch (\Exception $e) {
            Response::error('Failed to get hourly trend: ' . $e->getMessage());
        }
    }

    /**
     * Get comparative analysis between stations
     */
    public function compareStations()
    {
        $stationIds = $_GET['stations'] ?? '';
        
        if (!$stationIds) {
            Response::validationError(['stations' => 'At least one station ID is required']);
        }

        $stationIds = explode(',', $stationIds);

        try {
            $comparison = [];

            foreach ($stationIds as $stationId) {
                $stationId = (int) $stationId;
                $station = $this->stationModel->findById($stationId);

                if (!$station) continue;

                $readings = $this->readingModel->getByStationId($stationId, 100);

                if (empty($readings)) continue;

                $aqiValues = array_column($readings, 'aqi_index');
                $pm25Values = array_column($readings, 'pm25');

                $comparison[] = [
                    'id' => $stationId,
                    'name' => $station['name'],
                    'location' => $station['location'],
                    'average_aqi' => round(array_sum($aqiValues) / count($aqiValues), 2),
                    'max_aqi' => max($aqiValues),
                    'min_aqi' => min($aqiValues),
                    'latest_aqi' => $readings[0]['aqi_index'] ?? 0,
                    'reading_count' => count($readings)
                ];
            }

            Response::success($comparison);
        } catch (\Exception $e) {
            Response::error('Failed to compare stations: ' . $e->getMessage());
        }
    }

    /**
     * Get user activity analytics
     */
    public function userActivity()
    {
        try {
            $totalUsers = $this->userModel->count();
            $users = $this->userModel->getAll(1000);

            // Count by role
            $roleDistribution = [
                'admin' => 0,
                'user' => 0
            ];

            foreach ($users as $user) {
                $role = strtolower($user['role'] ?? 'user');
                if (isset($roleDistribution[$role])) {
                    $roleDistribution[$role]++;
                }
            }

            Response::success([
                'total_users' => $totalUsers,
                'role_distribution' => $roleDistribution,
                'new_users_this_week' => 0, // Would need created_at filtering
                'active_users_today' => 0   // Would need activity logging
            ]);
        } catch (\Exception $e) {
            Response::error('Failed to get user activity: ' . $e->getMessage());
        }
    }

    /**
     * Get summary report
     */
    public function report()
    {
        try {
            $user = Auth::getCurrentUser();

            if (!$user) {
                Response::unauthorized();
            }

            Response::success([
                'generated_at' => date('Y-m-d H:i:s'),
                'generated_by' => $user->email,
                'dashboard' => $this->getDashboardData(),
                'top_stations' => $this->getTopStations(),
                'status_distribution' => $this->getStatusDistribution(),
                'user_stats' => $this->getUserStats()
            ]);
        } catch (\Exception $e) {
            Response::error('Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Helper: get dashboard data
     */
    private function getDashboardData()
    {
        $allReadings = $this->readingModel->getAll(10000);
        $totalReadings = count($allReadings);

        $aqiSum = 0;
        $maxAqi = 0;
        $unhealthyCount = 0;

        foreach ($allReadings as $reading) {
            $aqi = $reading['aqi_index'] ?? 0;
            $aqiSum += $aqi;
            $maxAqi = max($maxAqi, $aqi);
            if ($aqi >= 150) {
                $unhealthyCount++;
            }
        }

        return [
            'total_readings' => $totalReadings,
            'average_aqi' => $totalReadings > 0 ? round($aqiSum / $totalReadings, 2) : 0,
            'max_aqi' => $maxAqi,
            'unhealthy_readings' => $unhealthyCount
        ];
    }

    /**
     * Helper: get user statistics
     */
    private function getUserStats()
    {
        $users = $this->userModel->getAll(1000);

        return [
            'total_users' => count($users),
            'admin_count' => count(array_filter($users, function($u) {
                return strtolower($u['role'] ?? '') === 'admin';
            })),
            'user_count' => count(array_filter($users, function($u) {
                return strtolower($u['role'] ?? '') === 'user';
            }))
        ];
    }
}
