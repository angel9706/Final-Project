<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class AirQualityReading
{
    private $db;
    private $table = 'air_quality_readings';

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

    public function create($station_id, $aqi_index, $pm25, $pm10, $o3, $no2, $so2, $co, $status, $source_api = 'aqicn')
    {
        $query = "INSERT INTO {$this->table} 
                  (station_id, aqi_index, pm25, pm10, o3, no2, so2, co, status, source_api, measured_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$station_id, $aqi_index, $pm25, $pm10, $o3, $no2, $so2, $co, $status, $source_api]);
    }

    public function update($id, $aqi_index, $pm25, $pm10, $o3, $no2, $so2, $co, $status)
    {
        $query = "UPDATE {$this->table} SET 
                  aqi_index = ?, pm25 = ?, pm10 = ?, o3 = ?, no2 = ?, so2 = ?, co = ?, 
                  status = ?, updated_at = NOW() 
                  WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$aqi_index, $pm25, $pm10, $o3, $no2, $so2, $co, $status, $id]);
    }

    public function delete($id)
    {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }

    public function getByStationId($station_id, $limit = 24, $offset = 0)
    {
        $query = "SELECT * FROM {$this->table} WHERE station_id = ? 
                  ORDER BY measured_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(1, (int)$station_id, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(3, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getLatestByStationId($station_id)
    {
        $query = "SELECT * FROM {$this->table} WHERE station_id = ? 
                  ORDER BY measured_at DESC LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$station_id]);
        return $stmt->fetch();
    }

    public function getTrendByStationId($station_id, $hours = 24)
    {
        $query = "SELECT 
                    DATE_FORMAT(measured_at, '%Y-%m-%d %H:00:00') as hour,
                    AVG(aqi_index) as avg_aqi,
                    AVG(pm25) as avg_pm25,
                    AVG(pm10) as avg_pm10,
                    MAX(aqi_index) as max_aqi,
                    MIN(aqi_index) as min_aqi
                  FROM {$this->table} 
                  WHERE station_id = ? AND measured_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
                  GROUP BY hour
                  ORDER BY hour ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$station_id, $hours]);
        return $stmt->fetchAll();
    }

    public function getAll($limit = 50, $offset = 0)
    {
        $query = "SELECT 
                    aqr.*,
                    ms.name as station_name,
                    ms.location as station_location
                  FROM {$this->table} aqr
                  LEFT JOIN monitoring_stations ms ON aqr.station_id = ms.id
                  ORDER BY aqr.measured_at DESC 
                  LIMIT ? OFFSET ?";
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

    public function countByStationId($station_id)
    {
        $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE station_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$station_id]);
        return $stmt->fetch()['total'];
    }

    public function getUnhealthyReadings($threshold = 150)
    {
        $query = "SELECT * FROM {$this->table} 
                  WHERE aqi_index >= ? AND measured_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  ORDER BY measured_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$threshold]);
        return $stmt->fetchAll();
    }

    /**
     * Check if reading exists for station at specific time
     * @param int $station_id
     * @param string $measured_at (datetime string)
     * @return bool
     */
    public function existsByStationAndTime($station_id, $measured_at)
    {
        $query = "SELECT COUNT(*) as count FROM {$this->table} 
                  WHERE station_id = ? AND measured_at = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$station_id, $measured_at]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Insert or update reading (prevents duplicates)
     * @param array $data
     * @return bool
     */
    public function createOrUpdate($data)
    {
        $query = "INSERT INTO {$this->table} 
                  (station_id, aqi_index, pm25, pm10, o3, no2, so2, co, status, source_api, measured_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE
                  aqi_index = VALUES(aqi_index),
                  pm25 = VALUES(pm25),
                  pm10 = VALUES(pm10),
                  o3 = VALUES(o3),
                  no2 = VALUES(no2),
                  so2 = VALUES(so2),
                  co = VALUES(co),
                  status = VALUES(status),
                  updated_at = NOW()";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            $data['station_id'],
            $data['aqi_index'],
            $data['pm25'] ?? null,
            $data['pm10'] ?? null,
            $data['o3'] ?? null,
            $data['no2'] ?? null,
            $data['so2'] ?? null,
            $data['co'] ?? null,
            $data['status'],
            $data['source_api'] ?? 'aqicn',
            $data['measured_at']
        ]);
    }

    /**
     * Bulk insert or update readings
     * @param array $readings Array of reading data
     * @return int Number of affected rows
     */
    public function bulkCreateOrUpdate($readings)
    {
        if (empty($readings)) {
            return 0;
        }

        $affected = 0;
        
        // Begin transaction for better performance
        $this->db->beginTransaction();
        
        try {
            foreach ($readings as $data) {
                if ($this->createOrUpdate($data)) {
                    $affected++;
                }
            }
            
            $this->db->commit();
            return $affected;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get date range of stored readings for a station
     * @param int $station_id
     * @return array ['min_date' => ..., 'max_date' => ...]
     */
    public function getDateRangeByStation($station_id)
    {
        $query = "SELECT 
                    MIN(measured_at) as min_date,
                    MAX(measured_at) as max_date
                  FROM {$this->table} 
                  WHERE station_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$station_id]);
        return $stmt->fetch();
    }
}
