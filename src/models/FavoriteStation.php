<?php

namespace App\Models;

use PDO;

class FavoriteStation
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get all favorite stations for a user
     * 
     * @param int $userId
     * @return array
     */
    public function getUserFavorites(int $userId): array
    {
        // Try with JOIN to stations table
        try {
            $sql = "SELECT 
                        ufs.id as favorite_id,
                        ufs.nickname,
                        ufs.created_at as favorited_at,
                        ms.id as station_id,
                        ms.name as station_name,
                        ms.location,
                        ms.latitude,
                        ms.longitude,
                        ms.external_id
                    FROM user_favorite_stations ufs
                    INNER JOIN monitoring_stations ms ON ufs.station_id = ms.id
                    WHERE ufs.user_id = :user_id
                    ORDER BY ufs.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // If monitoring_stations table doesn't exist, return simple list
            if ($e->getCode() === '42S02') {
                return $this->getUserFavoritesSimple($userId);
            }
            throw $e;
        }
    }

    /**
     * Get user favorites without JOIN (fallback when stations table missing)
     * 
     * @param int $userId
     * @return array
     */
    private function getUserFavoritesSimple(int $userId): array
    {
        $sql = "SELECT 
                    id as favorite_id,
                    station_id,
                    nickname,
                    created_at as favorited_at
                FROM user_favorite_stations
                WHERE user_id = :user_id
                ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get favorite stations with latest readings
     * 
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getUserFavoritesWithReadings(int $userId, int $limit = 10): array
    {
        $sql = "SELECT 
                    ufs.id as favorite_id,
                    ufs.nickname,
                    ms.id as station_id,
                    ms.name as station_name,
                    ms.location,
                    ms.latitude,
                    ms.longitude,
                    r.aqi_index as aqi,
                    r.pm25,
                    r.pm10,
                    NULL as temperature,
                    NULL as humidity,
                    r.measured_at as recorded_at
                FROM user_favorite_stations ufs
                INNER JOIN monitoring_stations ms ON ufs.station_id = ms.id
                LEFT JOIN (
                    SELECT station_id, aqi_index, pm25, pm10, measured_at
                    FROM air_quality_readings
                    WHERE (station_id, measured_at) IN (
                        SELECT station_id, MAX(measured_at)
                        FROM air_quality_readings
                        GROUP BY station_id
                    )
                ) r ON ms.id = r.station_id
                WHERE ufs.user_id = :user_id
                ORDER BY ufs.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add a station to favorites
     * 
     * @param int $userId
     * @param int $stationId
     * @param string|null $nickname
     * @return bool|int Returns favorite ID on success, false on failure
     */
    public function addFavorite(int $userId, int $stationId, ?string $nickname = null): bool|int
    {
        // Check if already exists
        if ($this->isFavorite($userId, $stationId)) {
            return false; // Already favorited
        }

        $sql = "INSERT INTO user_favorite_stations (user_id, station_id, nickname)
                VALUES (:user_id, :station_id, :nickname)";
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            'user_id' => $userId,
            'station_id' => $stationId,
            'nickname' => $nickname
        ]);

        return $success ? (int)$this->db->lastInsertId() : false;
    }

    /**
     * Remove a station from favorites
     * 
     * @param int $userId
     * @param int $stationId
     * @return bool
     */
    public function removeFavorite(int $userId, int $stationId): bool
    {
        $sql = "DELETE FROM user_favorite_stations 
                WHERE user_id = :user_id AND station_id = :station_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'station_id' => $stationId
        ]);
    }

    /**
     * Remove favorite by ID (with user ownership check)
     * 
     * @param int $favoriteId
     * @param int $userId
     * @return bool
     */
    public function removeFavoriteById(int $favoriteId, int $userId): bool
    {
        $sql = "DELETE FROM user_favorite_stations 
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $favoriteId,
            'user_id' => $userId
        ]);
    }

    /**
     * Check if a station is in user's favorites
     * 
     * @param int $userId
     * @param int $stationId
     * @return bool
     */
    public function isFavorite(int $userId, int $stationId): bool
    {
        $sql = "SELECT COUNT(*) as count 
                FROM user_favorite_stations 
                WHERE user_id = :user_id AND station_id = :station_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'station_id' => $stationId
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Get favorite IDs for a user (for quick lookup)
     * 
     * @param int $userId
     * @return array Array of station IDs
     */
    public function getUserFavoriteIds(int $userId): array
    {
        $sql = "SELECT station_id FROM user_favorite_stations WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'station_id');
    }

    /**
     * Update favorite nickname
     * 
     * @param int $favoriteId
     * @param int $userId
     * @param string|null $nickname
     * @return bool
     */
    public function updateNickname(int $favoriteId, int $userId, ?string $nickname): bool
    {
        $sql = "UPDATE user_favorite_stations 
                SET nickname = :nickname 
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $favoriteId,
            'user_id' => $userId,
            'nickname' => $nickname
        ]);
    }

    /**
     * Get total favorites count for a user
     * 
     * @param int $userId
     * @return int
     */
    public function getFavoritesCount(int $userId): int
    {
        $sql = "SELECT COUNT(*) as count FROM user_favorite_stations WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    /**
     * Get most popular stations (most favorited)
     * 
     * @param int $limit
     * @return array
     */
    public function getMostPopularStations(int $limit = 10): array
    {
        $sql = "SELECT 
                    ms.id as station_id,
                    ms.name as station_name,
                    ms.location,
                    COUNT(ufs.id) as favorite_count
                FROM monitoring_stations ms
                INNER JOIN user_favorite_stations ufs ON ms.id = ufs.station_id
                GROUP BY ms.id, ms.name, ms.location
                ORDER BY favorite_count DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
