<?php

namespace App\Config;

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushNotification
{
    private $vapidPublicKey;
    private $vapidPrivateKey;
    private $vapidSubject;
    private $webPush;

    public function __construct()
    {
        // Get VAPID keys from environment or use defaults
        $this->vapidPublicKey = $_ENV['VAPID_PUBLIC_KEY'] ?? 'BCvJ0C_DPLn3lsH4gA_JDWg_IZjYYk1I2c_aVbJXQQMVvKKaU6UVu_XBW4nJVXXrLYv8jlYXQQ0pMq1sJz5Z6Ok';
        $this->vapidPrivateKey = $_ENV['VAPID_PRIVATE_KEY'] ?? 'IPA7bMLkVDjQG0JZd5IHzKtO6bJ8QYj5GhVQ8IZj5K8';
        $this->vapidSubject = $_ENV['VAPID_SUBJECT'] ?? 'mailto:admin@siapkak.local';

        // Initialize WebPush with VAPID
        $auth = [
            'VAPID' => [
                'subject' => $this->vapidSubject,
                'publicKey' => $this->vapidPublicKey,
                'privateKey' => $this->vapidPrivateKey
            ]
        ];

        $this->webPush = new WebPush($auth);
    }

    /**
     * Get VAPID Public Key
     */
    public function getPublicKey()
    {
        return $this->vapidPublicKey;
    }

    /**
     * Get VAPID Private Key
     */
    public function getPrivateKey()
    {
        return $this->vapidPrivateKey;
    }

    /**
     * Get VAPID Subject
     */
    public function getSubject()
    {
        return $this->vapidSubject;
    }

    /**
     * Send Web Push Notification
     * 
     * @param array $subscriptionData Array with 'endpoint' and 'keys' (p256dh, auth)
     * @param string $title Notification title
     * @param string $message Notification body
     * @param array $data Additional data payload
     * @return array Result with success status and message
     */
    public function sendPush($subscriptionData, $title, $message, $data = [])
    {
        try {
            // Validate subscription data
            if (empty($subscriptionData['endpoint'])) {
                throw new \Exception('Missing endpoint in subscription');
            }

            // Prepare notification payload
            $payload = json_encode([
                'title' => $title,
                'message' => $message,
                'body' => $message, // Fallback
                'icon' => '/siapkak/public/img/logo-siapkak.png',
                'badge' => '/siapkak/public/img/logo-siapkak.png',
                'data' => $data,
                'tag' => 'siapkak-' . ($data['notification_id'] ?? uniqid()),
                'requireInteraction' => $data['type'] === 'danger' ? true : false,
                'notification_id' => $data['notification_id'] ?? null,
                'type' => $data['type'] ?? 'info',
                'aqi_value' => $data['aqi_value'] ?? null,
                'url' => $data['url'] ?? '/siapkak/dashboard'
            ]);

            // Create subscription object
            $subscription = Subscription::create([
                'endpoint' => $subscriptionData['endpoint'],
                'keys' => [
                    'p256dh' => $subscriptionData['keys']['p256dh'] ?? null,
                    'auth' => $subscriptionData['keys']['auth'] ?? null
                ]
            ]);

            // Send notification
            $report = $this->webPush->sendOneNotification($subscription, $payload);

            // Check result
            if ($report->isSuccess()) {
                return [
                    'success' => true,
                    'message' => 'Push notification sent successfully',
                    'status' => 'sent'
                ];
            } else {
                $errorMessage = 'Unknown error';
                if ($report->getResponse()) {
                    $errorMessage = $report->getResponse()->getReasonPhrase();
                }
                
                return [
                    'success' => false,
                    'message' => 'Push notification failed: ' . $errorMessage,
                    'status' => 'failed',
                    'expired' => $report->isSubscriptionExpired()
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Push notification error: ' . $e->getMessage(),
                'status' => 'failed'
            ];
        }
    }

    /**
     * Send batch push notifications
     * 
     * @param array $subscriptions Array of subscription data
     * @param string $title Notification title
     * @param string $message Notification body
     * @param array $data Additional data
     * @return array Summary of results
     */
    public function sendBatchPush($subscriptions, $title, $message, $data = [])
    {
        $results = [
            'total' => count($subscriptions),
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($subscriptions as $sub) {
            $result = $this->sendPush($sub, $title, $message, $data);
            
            if ($result['success']) {
                $results['sent']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'endpoint' => $sub['endpoint'],
                    'error' => $result['message']
                ];
            }
        }

        return $results;
    }

    /**
     * Check push notification support
     */
    public function isSupported()
    {
        return class_exists('Minishlink\WebPush\WebPush');
    }
}
