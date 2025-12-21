<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class MonitoringStation
{
    private $db;
    private $table = 'monitoring_stations';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findById($id)
    {
        $query = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($name, $location, $latitude, $longitude, $description = null)
    {
        $query = "INSERT INTO {$this->table} (name, location, latitude, longitude, description, created_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$name, $location, $latitude, $longitude, $description]);
    }

    public function update($id, $name, $location, $latitude, $longitude, $description = null)
    {
        $query = "UPDATE {$this->table} SET name = ?, location = ?, latitude = ?, longitude = ?, 
                  description = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$name, $location, $latitude, $longitude, $description, $id]);
    }

    public function delete($id)
    {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }

    public function getAll($limit = 10, $offset = 0)
    {
        $query = "SELECT * FROM {$this->table} ORDER BY id ASC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function count()
    {
        $query = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetch()['total'];
    }

    public function getAllWithLatestReading()
    {
        $query = "SELECT 
                    ms.*,
                    COALESCE(aqr.aqi_index, 0) as latest_aqi,
                    COALESCE(aqr.pm25, 0) as latest_pm25,
                    COALESCE(aqr.pm10, 0) as latest_pm10,
                    COALESCE(aqr.o3, 0) as latest_o3,
                    COALESCE(aqr.no2, 0) as latest_no2,
                    COALESCE(aqr.so2, 0) as latest_so2,
                    COALESCE(aqr.co, 0) as latest_co,
                    COALESCE(aqr.status, 'Unknown') as latest_status,
                    COALESCE(aqr.measured_at, 'N/A') as latest_measured_at
                  FROM {$this->table} ms
                  LEFT JOIN air_quality_readings aqr ON 
                    ms.id = aqr.station_id AND 
                    aqr.measured_at = (
                        SELECT MAX(measured_at) FROM air_quality_readings 
                        WHERE station_id = ms.id
                    )
                  ORDER BY ms.id ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Find station by external ID (from AQICN API)
     * @param string $external_id
     * @return array|false
     */
    public function findByExternalId($external_id)
    {
        $query = "SELECT * FROM {$this->table} WHERE external_id = ? LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$external_id]);
        return $stmt->fetch();
    }

    /**
     * Create or update station (upsert)
     * @param array $data Station data
     * @return int Station ID
     */
    public function createOrUpdate($data)
    {
        // Check if station exists by external_id
        if (isset($data['external_id'])) {
            $existing = $this->findByExternalId($data['external_id']);
            
            if ($existing) {
                // Update existing station
                $query = "UPDATE {$this->table} SET 
                          name = ?, location = ?, latitude = ?, longitude = ?, 
                          description = ?, updated_at = NOW() 
                          WHERE external_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    $data['name'],
                    $data['location'],
                    $data['latitude'],
                    $data['longitude'],
                    $data['description'] ?? null,
                    $data['external_id']
                ]);
                return $existing['id'];
            }
        }

        // Insert new station
        $query = "INSERT INTO {$this->table} 
                  (name, location, latitude, longitude, description, external_id, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['location'],
            $data['latitude'],
            $data['longitude'],
            $data['description'] ?? null,
            $data['external_id'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
}
