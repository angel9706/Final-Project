/**
 * SIAPKAK Push Notifications Client
 * Web Push subscription and management
 */

const PUSH_MANAGER = {
    swRegistration: null,
    isSubscribed: false,
    vapidPublicKey: 'BCvJ0C_DPLn3lsH4gA_JDWg_IZjYYk1I2c_aVbJXQQMVvKKaU6UVu_XBW4nJVXXrLYv8jlYXQQ0pMq1sJz5Z6Ok',

    /**
     * Initialize push notifications
     */
    async init() {
        console.log('[Push] Initializing push notifications');

        // Check browser support
        if (!('serviceWorker' in navigator)) {
            console.warn('[Push] Service Worker not supported');
            this.showUnsupportedMessage();
            return;
        }

        if (!('PushManager' in window)) {
            console.warn('[Push] Push notifications not supported');
            this.showUnsupportedMessage();
            return;
        }

        try {
            // Register Service Worker
            this.swRegistration = await navigator.serviceWorker.register('/siapkak/public/sw.js', {
                scope: '/siapkak/'
            });
            console.log('[Push] Service Worker registered:', this.swRegistration);

            // Wait for Service Worker to be ready
            await navigator.serviceWorker.ready;

            // Check current subscription status
            await this.checkSubscription();

            // Setup UI handlers
            this.setupUIHandlers();

        } catch (error) {
            console.error('[Push] Service Worker registration failed:', error);
        }
    },

    /**
     * Check if user is already subscribed
     */
    async checkSubscription() {
        try {
            const subscription = await this.swRegistration.pushManager.getSubscription();
            this.isSubscribed = !(subscription === null);
            
            console.log('[Push] Subscription status:', this.isSubscribed);
            this.updateUIState(this.isSubscribed);

            if (this.isSubscribed) {
                console.log('[Push] User is subscribed:', subscription.endpoint);
            }
        } catch (error) {
            console.error('[Push] Error checking subscription:', error);
        }
    },

    /**
     * Subscribe to push notifications
     */
    async subscribe() {
        try {
            console.log('[Push] Requesting notification permission...');

            // Request notification permission
            const permission = await Notification.requestPermission();
            
            if (permission !== 'granted') {
                console.warn('[Push] Notification permission denied');
                alert('Notifikasi tidak dapat diaktifkan karena izin ditolak. Silakan ubah pengaturan browser Anda.');
                return false;
            }

            console.log('[Push] Notification permission granted');

            // Convert VAPID key to Uint8Array
            const applicationServerKey = this.urlBase64ToUint8Array(this.vapidPublicKey);

            // Subscribe to push manager
            const subscription = await this.swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey
            });

            console.log('[Push] Push subscription created:', subscription);

            // Send subscription to server
            const result = await this.sendSubscriptionToServer(subscription);

            if (result.success) {
                this.isSubscribed = true;
                this.updateUIState(true);
                this.showSuccessMessage('Notifikasi push berhasil diaktifkan!');
                return true;
            } else {
                throw new Error('Failed to save subscription to server');
            }

        } catch (error) {
            console.error('[Push] Subscription failed:', error);
            alert('Gagal mengaktifkan notifikasi: ' + error.message);
            return false;
        }
    },

    /**
     * Unsubscribe from push notifications
     */
    async unsubscribe() {
        try {
            const subscription = await this.swRegistration.pushManager.getSubscription();
            
            if (subscription) {
                // Unsubscribe from push manager
                await subscription.unsubscribe();
                console.log('[Push] Push subscription removed');

                // Remove subscription from server
                await this.removeSubscriptionFromServer(subscription);

                this.isSubscribed = false;
                this.updateUIState(false);
                this.showSuccessMessage('Notifikasi push berhasil dinonaktifkan.');
                return true;
            }

        } catch (error) {
            console.error('[Push] Unsubscribe failed:', error);
            alert('Gagal menonaktifkan notifikasi: ' + error.message);
            return false;
        }
    },

    /**
     * Send subscription to server
     */
    async sendSubscriptionToServer(subscription) {
        try {
            const response = await fetch('/siapkak/api/notifications/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(subscription.toJSON())
            });

            if (!response.ok) {
                throw new Error('Server returned ' + response.status);
            }

            const data = await response.json();
            console.log('[Push] Subscription saved to server:', data);
            return { success: true, data };

        } catch (error) {
            console.error('[Push] Error sending subscription to server:', error);
            return { success: false, error: error.message };
        }
    },

    /**
     * Remove subscription from server
     */
    async removeSubscriptionFromServer(subscription) {
        try {
            const response = await fetch('/siapkak/api/notifications/unsubscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(subscription.toJSON())
            });

            const data = await response.json();
            console.log('[Push] Subscription removed from server:', data);
            return data;

        } catch (error) {
            console.error('[Push] Error removing subscription from server:', error);
        }
    },

    /**
     * Setup UI event handlers
     */
    setupUIHandlers() {
        const subscribeBtn = document.getElementById('subscribe-push-btn');
        const unsubscribeBtn = document.getElementById('unsubscribe-push-btn');

        if (subscribeBtn) {
            subscribeBtn.addEventListener('click', () => {
                subscribeBtn.disabled = true;
                this.subscribe().finally(() => {
                    subscribeBtn.disabled = false;
                });
            });
        }

        if (unsubscribeBtn) {
            unsubscribeBtn.addEventListener('click', () => {
                if (confirm('Apakah Anda yakin ingin menonaktifkan notifikasi push?')) {
                    unsubscribeBtn.disabled = true;
                    this.unsubscribe().finally(() => {
                        unsubscribeBtn.disabled = false;
                    });
                }
            });
        }
    },

    /**
     * Update UI based on subscription state
     */
    updateUIState(isSubscribed) {
        const subscribeBtn = document.getElementById('subscribe-push-btn');
        const unsubscribeBtn = document.getElementById('unsubscribe-push-btn');
        const statusBadge = document.getElementById('push-status-badge');

        if (subscribeBtn) {
            subscribeBtn.style.display = isSubscribed ? 'none' : 'inline-block';
        }
        if (unsubscribeBtn) {
            unsubscribeBtn.style.display = isSubscribed ? 'inline-block' : 'none';
        }
        if (statusBadge) {
            statusBadge.textContent = isSubscribed ? 'Aktif' : 'Tidak Aktif';
            statusBadge.className = isSubscribed ? 'badge badge-success' : 'badge badge-secondary';
        }
    },

    /**
     * Show unsupported message
     */
    showUnsupportedMessage() {
        const container = document.getElementById('push-notification-container');
        if (container) {
            container.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Browser Anda tidak mendukung notifikasi push. Silakan gunakan browser modern seperti Chrome, Firefox, atau Edge.
                </div>
            `;
        }
    },

    /**
     * Show success message
     */
    showSuccessMessage(message) {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            <i class="fas fa-check-circle"></i> ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        `;
        document.body.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.remove();
        }, 5000);
    },

    /**
     * Convert VAPID key to Uint8Array
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        PUSH_MANAGER.init();
    });
} else {
    PUSH_MANAGER.init();
}
