/**
 * Service Worker for SIAPKAK Web Push Notifications & Caching
 */

const CACHE_NAME = 'siapkak-v1';
const DASHBOARD_URL = '/siapkak/dashboard';
const urlsToCache = [
  '/siapkak/',
  '/siapkak/index.html',
  '/siapkak/dashboard',
  '/siapkak/public/js/main.js',
  '/siapkak/public/js/push-notifications.js',
  '/siapkak/public/js/ajax.js',
  '/siapkak/public/js/charts.js',
  'https://cdn.tailwindcss.com',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

/**
 * Install event - cache essential assets
 */
self.addEventListener('install', (event) => {
    console.log('[SW] Installing Service Worker');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Caching assets');
                return cache.addAll(urlsToCache);
            })
            .then(() => self.skipWaiting())
    );
});

/**
 * Activate event - clean old caches
 */
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating Service Worker');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => {
                        console.log('[SW] Deleting old cache:', name);
                        return caches.delete(name);
                    })
            );
        }).then(() => self.clients.claim())
    );
});

/**
 * Fetch event - Network first strategy with cache fallback
 */
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Validate response
                if (!response || response.status !== 200 || response.type !== 'basic') {
                    return response;
                }

                // Clone response for caching
                const responseToCache = response.clone();
                caches.open(CACHE_NAME)
                    .then((cache) => {
                        cache.put(event.request, responseToCache);
                    });

                return response;
            })
            .catch(() => {
                // Fallback to cache
                return caches.match(event.request)
                    .then((response) => {
                        return response || new Response('Offline - Page not cached');
                    });
            })
    );
});

/**
 * Push event - receive and display notifications
 */
self.addEventListener('push', (event) => {
    console.log('[SW] Push notification received');
    
    let notificationData = {
        title: 'SIAPKAK Notification',
        body: 'You have a new notification',
        icon: '/siapkak/public/img/logo-siapkak.png',
        badge: '/siapkak/public/img/logo-siapkak.png',
        tag: 'siapkak-notification',
        requireInteraction: false,
        data: {
            url: DASHBOARD_URL
        }
    };

    if (event.data) {
        try {
            const payload = event.data.json();
            
            notificationData.title = payload.title || notificationData.title;
            notificationData.body = payload.message || payload.body || notificationData.body;
            notificationData.tag = payload.notification_id || notificationData.tag;
            notificationData.data = {
                url: payload.url || DASHBOARD_URL,
                notification_id: payload.notification_id,
                type: payload.type,
                aqi_value: payload.aqi_value
            };

            // Set icon and priority based on notification type
            if (payload.type === 'danger' && payload.aqi_value >= 150) {
                notificationData.requireInteraction = true;
                notificationData.badge = '/siapkak/public/img/alert-red.png';
                notificationData.vibrate = [200, 100, 200];
                
                // Add AQI status to body
                const aqiStatus = getAQIStatus(payload.aqi_value);
                notificationData.body = `AQI: ${payload.aqi_value} - ${aqiStatus}\n${notificationData.body}`;
            } else if (payload.type === 'warning' && payload.aqi_value >= 100) {
                notificationData.vibrate = [100, 50, 100];
                notificationData.badge = '/siapkak/public/img/alert-orange.png';
            }

            // Add action buttons for high AQI
            if (payload.aqi_value >= 150) {
                notificationData.actions = [
                    {
                        action: 'view',
                        title: 'View Dashboard'
                    },
                    {
                        action: 'close',
                        title: 'Dismiss'
                    }
                ];
            }
        } catch (error) {
            console.error('[SW] Error parsing push data:', error);
        }
    }

    event.waitUntil(
        self.registration.showNotification(notificationData.title, {
            body: notificationData.body,
            icon: notificationData.icon,
            badge: notificationData.badge,
            tag: notificationData.tag,
            requireInteraction: notificationData.requireInteraction,
            vibrate: notificationData.vibrate,
            actions: notificationData.actions,
            data: notificationData.data
        })
    );
});

/**
 * Notification click event - handle user interaction
 */
self.addEventListener('notificationclick', (event) => {
    console.log('[SW] Notification clicked:', event.action);
    
    event.notification.close();

    if (event.action === 'close') {
        // User dismissed the notification
        return;
    }

    // Open or focus the dashboard
    const urlToOpen = event.notification.data?.url || DASHBOARD_URL;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Check if dashboard is already open
                for (let client of clientList) {
                    if (client.url.includes('/siapkak/') && 'focus' in client) {
                        return client.focus();
                    }
                }
                // Open new window if not found
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

/**
 * Background sync event - retry failed operations
 */
self.addEventListener('sync', (event) => {
    console.log('[SW] Background sync triggered:', event.tag);
    
    if (event.tag === 'sync-notifications') {
        event.waitUntil(syncNotifications());
    }
});

/**
 * Sync notifications with server
 */
async function syncNotifications() {
    try {
        const response = await fetch('/siapkak/api/notifications/sync', {
            method: 'POST',
            credentials: 'include'
        });
        
        if (response.ok) {
            console.log('[SW] Notifications synced successfully');
        }
    } catch (error) {
        console.error('[SW] Failed to sync notifications:', error);
        throw error; // Retry on next sync
    }
}

/**
 * Get AQI status text
 */
function getAQIStatus(aqi) {
    if (aqi <= 50) return 'Baik';
    if (aqi <= 100) return 'Sedang';
    if (aqi <= 150) return 'Tidak Sehat (Sensitif)';
    if (aqi <= 200) return 'Tidak Sehat';
    if (aqi <= 300) return 'Sangat Tidak Sehat';
    return 'Berbahaya';
}

/**
 * Message event - handle messages from client
 */
self.addEventListener('message', (event) => {
    console.log('[SW] Message received:', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
