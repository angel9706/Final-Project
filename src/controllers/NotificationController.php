<?php

namespace App\Controllers;

use App\Config\Response;
use App\Config\Auth;
use App\Config\PushNotification;
use App\Config\EmailNotification;
use App\Models\Notification;
use App\Models\User;

class NotificationController
{
    private $notificationModel;
    private $pushService;
    private $emailService;
    private $userModel;

    public function __construct()
    {
        $this->notificationModel = new Notification();
        $this->pushService = new PushNotification();
        $this->emailService = new EmailNotification();
        $this->userModel = new User();
    }

    /**
     * Get user notifications
     */
    public function getByUser()
    {
        $user = Auth::getCurrentUser();
        if (!$user) {
            Response::unauthorized();
        }

        $limit = $_GET['limit'] ?? 20;
        $page = $_GET['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $notifications = $this->notificationModel->getByUserId($user->sub, $limit, $offset);
        $unreadCount = $this->notificationModel->countUnreadByUserId($user->sub);

        Response::success([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit
            ]
        ]);
    }

    /**
     * Get unread notifications
     */
    public function getUnread()
    {
        $user = Auth::getCurrentUser();
        if (!$user) {
            Response::unauthorized();
        }

        $notifications = $this->notificationModel->getUnreadByUserId($user->sub);

        Response::success([
            'notifications' => $notifications,
            'count' => count($notifications)
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', null, 405);
        }

        $user = Auth::getCurrentUser();
        if (!$user) {
            Response::unauthorized();
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['id'])) {
            Response::validationError(['id' => 'Notification ID is required']);
        }

        $notification = $this->notificationModel->findById($input['id']);

        if (!$notification || $notification['user_id'] !== $user->sub) {
            Response::notFound();
        }

        if ($this->notificationModel->markAsRead($input['id'])) {
            Response::success(null, 'Notification marked as read');
        }

        Response::error('Failed to mark notification as read');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', null, 405);
        }

        $user = Auth::getCurrentUser();
        if (!$user) {
            Response::unauthorized();
        }

        if ($this->notificationModel->markAllAsRead($user->sub)) {
            Response::success(null, 'All notifications marked as read');
        }

        Response::error('Failed to mark all notifications as read');
    }

    /**
     * Delete notification
     */
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', null, 405);
        }

        $user = Auth::getCurrentUser();
        if (!$user) {
            Response::unauthorized();
        }

        $id = $_GET['id'] ?? null;

        if (!$id) {
            Response::validationError(['id' => 'Notification ID is required']);
        }

        $notification = $this->notificationModel->findById($id);

        if (!$notification || $notification['user_id'] !== $user->sub) {
            Response::notFound();
        }

        if ($this->notificationModel->delete($id)) {
            Response::success(null, 'Notification deleted');
        }

        Response::error('Failed to delete notification');
    }

    /**
     * Subscribe to push notifications
     */
    public function subscribePush()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', null, 405);
        }

        $user = Auth::getCurrentUser();
        if (!$user) {
            Response::unauthorized();
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['endpoint'])) {
            Response::validationError(['endpoint' => 'Push endpoint is required']);
        }

        // Save subscription to database
        try {
            $this->notificationModel->savePushSubscription($user->sub, $input);
            Response::success(['subscribed' => true], 'Subscribed to push notifications', 201);
        } catch (\Exception $e) {
            Response::error('Failed to save subscription: ' . $e->getMessage());
        }
    }

    /**
     * Unsubscribe from push notifications
     */
    public function unsubscribePush()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', null, 405);
        }

        $user = Auth::getCurrentUser();
        if (!$user) {
            Response::unauthorized();
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!empty($input['endpoint'])) {
            $this->notificationModel->removePushSubscription($user->sub, $input['endpoint']);
        }

        Response::success(null, 'Unsubscribed from push notifications');
    }

    /**
     * Send notification with Web Push and Email fallback
     */
    public function sendNotification($userId, $title, $message, $type = 'info', $stationId = null, $aqiValue = null)
    {
        $deliveryStatus = 'failed';
        $deliveryMethod = null;
        $errorMessages = [];

        // Create notification record
        $notificationId = $this->notificationModel->create([
            'user_id' => $userId,
            'station_id' => $stationId,
            'title' => $title,
            'message' => $message,
            'aqi_value' => $aqiValue,
            'type' => $type,
            'delivery_status' => 'pending'
        ]);

        // Try Web Push first
        $pushSubscriptions = $this->notificationModel->getPushSubscriptions($userId);
        $pushSuccess = false;

        if (!empty($pushSubscriptions)) {
            foreach ($pushSubscriptions as $subscription) {
                try {
                    $subscriptionData = [
                        'endpoint' => $subscription['endpoint'],
                        'keys' => [
                            'p256dh' => $subscription['p256dh_key'],
                            'auth' => $subscription['auth_key']
                        ]
                    ];

                    $result = $this->pushService->sendPush(
                        $subscriptionData,
                        $title,
                        $message,
                        [
                            'notification_id' => $notificationId,
                            'type' => $type,
                            'aqi_value' => $aqiValue,
                            'url' => '/siapkak/dashboard'
                        ]
                    );

                    if ($result['success']) {
                        $pushSuccess = true;
                        $deliveryMethod = 'push';
                        $this->notificationModel->logDelivery($notificationId, 'push', 'sent');
                    } else {
                        $this->notificationModel->logDelivery($notificationId, 'push', 'failed', $result['message']);
                        $errorMessages[] = 'Push: ' . $result['message'];
                    }
                } catch (\Exception $e) {
                    $this->notificationModel->logDelivery($notificationId, 'push', 'failed', $e->getMessage());
                    $errorMessages[] = 'Push: ' . $e->getMessage();
                }
            }
        }

        // Fallback to Email if Push failed or not available
        if (!$pushSuccess) {
            try {
                $user = $this->userModel->findById($userId);
                
                if ($user && !empty($user['email'])) {
                    $emailSent = false;
                    
                    // Send email for all AQI-related notifications
                    if ($aqiValue !== null && $stationId) {
                        // Send AQI alert email (works for all AQI levels)
                        $stationName = $this->getStationName($stationId);
                        $status = $this->getAQIStatus($aqiValue);
                        
                        $emailSent = $this->emailService->sendAQIAlert(
                            $user['email'],
                            $user['name'],
                            $stationName,
                            $aqiValue,
                            $status
                        );
                        
                        error_log("Sending AQI email to {$user['email']}: Station={$stationName}, AQI={$aqiValue}, Status={$status}");
                    } else {
                        // Send general notification email
                        $emailSent = $this->emailService->sendNotification(
                            $user['email'],
                            $user['name'],
                            $title,
                            $message
                        );
                        
                        error_log("Sending general notification email to {$user['email']}: {$title}");
                    }

                    if ($emailSent) {
                        $deliveryStatus = 'sent';
                        $deliveryMethod = $pushSuccess ? 'both' : 'email';
                        $this->notificationModel->logDelivery($notificationId, 'email', 'sent');
                        error_log("Email successfully sent to {$user['email']}");
                    } else {
                        $this->notificationModel->logDelivery($notificationId, 'email', 'failed', 'Email send failed');
                        $errorMessages[] = 'Email: Send failed';
                        error_log("Email failed to send to {$user['email']}");
                    }
                } else {
                    error_log("User not found or no email: userId={$userId}");
                }
            } catch (\Exception $e) {
                $this->notificationModel->logDelivery($notificationId, 'email', 'failed', $e->getMessage());
                $errorMessages[] = 'Email: ' . $e->getMessage();
                error_log("Email exception: " . $e->getMessage());
            }
        } else {
            $deliveryStatus = 'sent';
        }

        // Update notification status
        $this->notificationModel->updateDeliveryStatus($notificationId, $deliveryStatus);

        return [
            'success' => $deliveryStatus === 'sent',
            'notification_id' => $notificationId,
            'delivery_method' => $deliveryMethod,
            'delivery_status' => $deliveryStatus,
            'errors' => $errorMessages
        ];
    }

    /**
     * Get station name helper
     */
    private function getStationName($stationId)
    {
        try {
            $stationModel = new \App\Models\MonitoringStation();
            $station = $stationModel->findById($stationId);
            return $station ? $station['name'] : 'Unknown Station';
        } catch (\Exception $e) {
            return 'Unknown Station';
        }
    }

    /**
     * Get AQI status helper
     */
    private function getAQIStatus($aqi)
    {
        if ($aqi <= 50) return 'Baik';
        if ($aqi <= 100) return 'Sedang';
        if ($aqi <= 150) return 'Tidak Sehat (Sensitif)';
        if ($aqi <= 200) return 'Tidak Sehat';
        if ($aqi <= 300) return 'Sangat Tidak Sehat';
        return 'Berbahaya';
    }

    /**
     * Sync notifications (untuk offline queueing)
     */
    public function syncNotifications()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', null, 405);
        }

        $user = Auth::getCurrentUser();
        if (!$user) {
            Response::unauthorized();
        }

        // Get unread notifications untuk di-sync
        $notifications = $this->notificationModel->getUnreadByUserId($user->sub);

        Response::success([
            'notifications' => $notifications,
            'synced_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Send test notification
     */
    public function sendTestNotification()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed', null, 405);
        }

        $user = Auth::getCurrentUser();
        if (!$user) {
            Response::unauthorized();
        }

        try {
            // Send test notification to current user
            $result = $this->sendNotification(
                $user->sub,
                'Test Notification - SIAPKAK',
                'Ini adalah test notification dari sistem SIAPKAK. Jika Anda melihat pesan ini, notifikasi berhasil dikirim!',
                'info',
                null,
                null
            );

            if ($result['success']) {
                Response::success($result, 'Test notification sent successfully');
            } else {
                Response::error('Failed to send test notification', $result);
            }
        } catch (\Exception $e) {
            Response::error('Error sending test notification: ' . $e->getMessage());
        }
    }
}
