<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Notification
{
    private $db;
    private $table = 'notifications';

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

    public function create($data)
    {
        // Support both array and individual parameters
        if (is_array($data)) {
            $user_id = $data['user_id'];
            $station_id = $data['station_id'] ?? null;
            $title = $data['title'];
            $message = $data['message'];
            $aqi_value = $data['aqi_value'] ?? null;
            $type = $data['type'] ?? 'warning';
        } else {
            // Old signature for backward compatibility
            $user_id = func_get_arg(0);
            $station_id = func_get_arg(1);
            $title = func_get_arg(2);
            $message = func_get_arg(3);
            $aqi_value = func_get_arg(4);
            $type = func_num_args() > 5 ? func_get_arg(5) : 'warning';
        }
        
        $query = "INSERT INTO {$this->table} 
                  (user_id, station_id, title, message, aqi_value, type, is_read, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, 0, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$user_id, $station_id, $title, $message, $aqi_value, $type]);
        
        return $this->db->lastInsertId();
    }

    public function markAsRead($id)
    {
        $query = "UPDATE {$this->table} SET is_read = 1, read_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }

    public function markAllAsRead($user_id)
    {
        $query = "UPDATE {$this->table} SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$user_id]);
    }

    public function delete($id)
    {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }

    public function getByUserId($user_id, $limit = 20, $offset = 0)
    {
        $query = "SELECT * FROM {$this->table} WHERE user_id = ? 
                  ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(1, (int)$user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(3, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUnreadByUserId($user_id)
    {
        $query = "SELECT * FROM {$this->table} WHERE user_id = ? AND is_read = 0 
                  ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public function countUnreadByUserId($user_id)
    {
        $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE user_id = ? AND is_read = 0";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->fetch()['total'];
    }

    public function getAll($limit = 50, $offset = 0)
    {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT ? OFFSET ?";
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

    /**
     * Save push subscription for user
     */
    public function savePushSubscription($userId, $subscription)
    {
        $query = "INSERT INTO push_subscriptions (user_id, endpoint, p256dh_key, auth_key, created_at)
                  VALUES (?, ?, ?, ?, NOW())
                  ON DUPLICATE KEY UPDATE
                  p256dh_key = VALUES(p256dh_key),
                  auth_key = VALUES(auth_key),
                  updated_at = NOW()";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            $userId,
            $subscription['endpoint'],
            $subscription['keys']['p256dh'] ?? null,
            $subscription['keys']['auth'] ?? null
        ]);
    }

    /**
     * Get push subscriptions for user
     */
    public function getPushSubscriptions($userId)
    {
        $query = "SELECT * FROM push_subscriptions WHERE user_id = ? AND active = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Remove push subscription
     */
    public function removePushSubscription($userId, $endpoint)
    {
        $query = "UPDATE push_subscriptions SET active = 0 WHERE user_id = ? AND endpoint = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$userId, $endpoint]);
    }

    /**
     * Log notification delivery
     */
    public function logDelivery($notificationId, $method, $status, $errorMessage = null)
    {
        $query = "INSERT INTO notification_logs (notification_id, delivery_method, status, error_message, sent_at)
                  VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$notificationId, $method, $status, $errorMessage]);
    }

    /**
     * Update notification delivery status
     */
    public function updateDeliveryStatus($notificationId, $status)
    {
        $query = "UPDATE {$this->table} SET delivery_status = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$status, $notificationId]);
    }

    /**
     * Get notification delivery logs
     */
    public function getDeliveryLogs($notificationId)
    {
        $query = "SELECT * FROM notification_logs WHERE notification_id = ? ORDER BY sent_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$notificationId]);
        return $stmt->fetchAll();
    }
}
