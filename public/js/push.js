// Web Push Notification handler untuk frontend

class PushNotificationManager {
    constructor() {
        this.registration = null;
        this.subscription = null;
        this.vapidPublicKey = null;
        this.apiBaseUrl = '/siapkak/api';
        
        this.init();
    }

    /**
     * Initialize Push Notification
     */
    async init() {
        // Check browser support
        if (!('serviceWorker' in navigator)) {
            console.warn('Service Worker tidak didukung');
            return false;
        }

        if (!('PushManager' in window)) {
            console.warn('Push Manager tidak didukung');
            return false;
        }

        try {
            // Register service worker
            this.registration = await navigator.serviceWorker.register('/siapkak/public/service-worker.js', {
                scope: '/siapkak/public/'
            });
            
            console.log('Service Worker registered:', this.registration);

            // Get VAPID public key
            await this.getVAPIDPublicKey();

            // Check existing subscription
            this.subscription = await this.registration.pushManager.getSubscription();

            // Request notification permission
            if (Notification.permission === 'default') {
                await this.requestPermission();
            }

            // Subscribe if has permission
            if (Notification.permission === 'granted') {
                if (!this.subscription) {
                    await this.subscribe();
                }
            }

            return true;
        } catch (error) {
            console.error('Failed to initialize push notifications:', error);
            return false;
        }
    }

    /**
     * Get VAPID Public Key dari API
     */
    async getVAPIDPublicKey() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/push/vapid-public-key`);
            const data = await response.json();
            this.vapidPublicKey = data.public_key;
        } catch (error) {
            console.error('Failed to get VAPID public key:', error);
        }
    }

    /**
     * Request notification permission
     */
    async requestPermission() {
        try {
            const permission = await Notification.requestPermission();
            
            if (permission === 'granted') {
                console.log('Notification permission granted');
                
                // Subscribe after permission granted
                if (this.registration && !this.subscription) {
                    await this.subscribe();
                }
                
                return true;
            } else if (permission === 'denied') {
                console.log('Notification permission denied');
                return false;
            }
        } catch (error) {
            console.error('Failed to request notification permission:', error);
            return false;
        }
    }

    /**
     * Subscribe to push notifications
     */
    async subscribe() {
        if (!this.registration) {
            console.error('Service Worker tidak terdaftar');
            return false;
        }

        try {
            const subscriptionOptions = {
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.vapidPublicKey)
            };

            this.subscription = await this.registration.pushManager.subscribe(subscriptionOptions);

            // Send subscription to backend
            await this.sendSubscriptionToBackend(this.subscription);

            console.log('Push subscription successful:', this.subscription);
            return true;
        } catch (error) {
            console.error('Failed to subscribe to push notifications:', error);
            
            // Fallback: gunakan email notifications
            if (error.message.includes('VAPID')) {
                console.log('VAPID key error, switching to email notifications');
                this.enableEmailNotifications();
            }
            
            return false;
        }
    }

    /**
     * Send subscription to backend
     */
    async sendSubscriptionToBackend(subscription) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/notifications/subscribe-push`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    keys: {
                        auth: this.arrayBufferToBase64(subscription.getKey('auth')),
                        p256dh: this.arrayBufferToBase64(subscription.getKey('p256dh'))
                    }
                })
            });

            if (response.ok) {
                console.log('Subscription sent to backend');
                return true;
            }
        } catch (error) {
            console.error('Failed to send subscription to backend:', error);
            return false;
        }
    }

    /**
     * Unsubscribe from push notifications
     */
    async unsubscribe() {
        if (!this.subscription) {
            return false;
        }

        try {
            // Send unsubscribe to backend
            await fetch(`${this.apiBaseUrl}/notifications/unsubscribe-push`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                }
            });

            // Unsubscribe from push manager
            const result = await this.subscription.unsubscribe();
            
            if (result) {
                this.subscription = null;
                console.log('Unsubscribed from push notifications');
            }

            return result;
        } catch (error) {
            console.error('Failed to unsubscribe:', error);
            return false;
        }
    }

    /**
     * Enable email notifications fallback
     */
    async enableEmailNotifications() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/notifications/enable-email`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                }
            });

            if (response.ok) {
                console.log('Email notifications enabled');
                return true;
            }
        } catch (error) {
            console.error('Failed to enable email notifications:', error);
            return false;
        }
    }

    /**
     * Show local notification (testing)
     */
    async showNotification(title, options = {}) {
        if (!this.registration) {
            console.error('Service Worker tidak terdaftar');
            return;
        }

        const defaultOptions = {
            icon: '/siapkak/public/img/icon-192.png',
            badge: '/siapkak/public/img/badge-72.png',
            tag: 'siapkak-notification',
            requireInteraction: true,
            ...options
        };

        try {
            await this.registration.showNotification(title, defaultOptions);
        } catch (error) {
            console.error('Failed to show notification:', error);
        }
    }

    /**
     * Utility: Convert base64 string to Uint8Array
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }

        return outputArray;
    }

    /**
     * Utility: Convert ArrayBuffer to Base64
     */
    arrayBufferToBase64(buffer) {
        let binary = '';
        const bytes = new Uint8Array(buffer);
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }

    /**
     * Get subscription status
     */
    async getStatus() {
        return {
            isServiceWorkerSupported: 'serviceWorker' in navigator,
            isServiceWorkerRegistered: this.registration !== null,
            isPushSupported: 'PushManager' in window,
            isSubscribed: this.subscription !== null,
            notificationPermission: Notification.permission,
            subscriptionEndpoint: this.subscription?.endpoint || null
        };
    }
}

// Initialize global instance
const pushNotificationManager = new PushNotificationManager();
