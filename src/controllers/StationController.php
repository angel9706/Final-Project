<?php

namespace App\Controllers;

use App\Config\Auth;
use App\Config\Response;
use App\Models\MonitoringStation;
use App\Models\AirQualityReading;

class StationController
{
    private $stationModel;
    private $readingModel;

    public function __construct()
    {
        $this->stationModel = new MonitoringStation();
        $this->readingModel = new AirQualityReading();
    }

    /**
     * Get all monitoring stations
     */
    public function index()
    {
        $limit = $_GET['limit'] ?? 10;
        $page = $_GET['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $stations = $this->stationModel->getAllWithLatestReading();
        
        // If limit is 0 or not set for sync, return all stations
        if ($limit == 0 || (!isset($_GET['limit']) && !isset($_GET['page']))) {
            Response::success([
                'stations' => $stations
            ]);
        } else {
            $total = $this->stationModel->count();
            Response::success([
                'stations' => array_slice($stations, $offset, $limit),
                'pagination' => [
                    'total' => $total,
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        }
    }

    /**
     * Get single station
     */
    public function show()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed', null, 405);
        }

        $id = $_GET['id'] ?? null;

        if (!$id) {
            Response::validationError(['id' => 'Station ID is required']);
        }

        $station = $this->stationModel->findById($id);

        if (!$station) {
            Response::notFound();
        }

        // Get latest reading
        $latestReading = $this->readingModel->getLatestByStationId($id);

        // Add aliases for frontend compatibility
        if ($latestReading) {
            $latestReading['aqi'] = $latestReading['aqi_index'] ?? null;
            $latestReading['recorded_at'] = $latestReading['measured_at'] ?? null;
            $latestReading['temperature'] = null; // Not in current schema
            $latestReading['humidity'] = null; // Not in current schema
        }

        Response::success([
            'station' => $station,
            'latest_reading' => $latestReading
        ]);
    }

    /**
     * Create new station
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
        if (empty($input['name']) || empty($input['location']) || empty($input['latitude']) || empty($input['longitude'])) {
            Response::validationError([
                'name' => 'Station name is required',
                'location' => 'Location is required',
                'latitude' => 'Latitude is required',
                'longitude' => 'Longitude is required'
            ]);
        }

        // Validate coordinates
        if (!is_numeric($input['latitude']) || !is_numeric($input['longitude'])) {
            Response::validationError([
                'latitude' => 'Latitude must be numeric',
                'longitude' => 'Longitude must be numeric'
            ]);
        }

        // Create station
        if ($this->stationModel->create(
            $input['name'],
            $input['location'],
            $input['latitude'],
            $input['longitude'],
            $input['description'] ?? null
        )) {
            Response::success(null, 'Station created successfully', 201);
        }

        Response::error('Failed to create station');
    }

    /**
     * Update station
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
            Response::validationError(['id' => 'Station ID is required']);
        }

        $station = $this->stationModel->findById($id);

        if (!$station) {
            Response::notFound();
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Validation
        if (empty($input['name']) || empty($input['location']) || empty($input['latitude']) || empty($input['longitude'])) {
            Response::validationError([
                'name' => 'Station name is required',
                'location' => 'Location is required',
                'latitude' => 'Latitude is required',
                'longitude' => 'Longitude is required'
            ]);
        }

        if ($this->stationModel->update(
            $id,
            $input['name'],
            $input['location'],
            $input['latitude'],
            $input['longitude'],
            $input['description'] ?? null
        )) {
            Response::success(null, 'Station updated successfully');
        }

        Response::error('Failed to update station');
    }

    /**
     * Delete station
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
            Response::validationError(['id' => 'Station ID is required']);
        }

        $station = $this->stationModel->findById($id);

        if (!$station) {
            Response::notFound();
        }

        if ($this->stationModel->delete($id)) {
            Response::success(null, 'Station deleted successfully');
        }

        Response::error('Failed to delete station');
    }
}
