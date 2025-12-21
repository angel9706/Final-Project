// Service Worker untuk Web Push Notifications

const CACHE_NAME = 'siapkak-v1';
const urlsToCache = [
  '/siapkak/',
  '/siapkak/index.html',
  '/siapkak/dashboard.html',
  '/siapkak/js/main.js',
  'https://cdn.tailwindcss.com',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// Install event
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
      .then(() => self.skipWaiting())
  );
});

// Activate event
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch event (Network first, fallback to cache)
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then(response => {
        if (!response || response.status !== 200 || response.type !== 'basic') {
          return response;
        }
        const responseToCache = response.clone();
        caches.open(CACHE_NAME)
          .then(cache => {
            cache.put(event.request, responseToCache);
          });
        return response;
      })
      .catch(() => {
        return caches.match(event.request)
          .then(response => {
            return response || new Response('Offline - Page not cached');
          });
      })
  );
});

// Push event
self.addEventListener('push', event => {
  let notificationData = {
    title: 'SIAPKAK',
    body: 'Notifikasi dari SIAPKAK',
    icon: '/siapkak/public/img/icon-192.png',
    badge: '/siapkak/public/img/badge-72.png',
    tag: 'siapkak-notification'
  };

  if (event.data) {
    try {
      notificationData = event.data.json();
    } catch (e) {
      notificationData.body = event.data.text();
    }
  }

  event.waitUntil(
    self.registration.showNotification(notificationData.title, {
      body: notificationData.body,
      icon: notificationData.icon,
      badge: notificationData.badge,
      tag: notificationData.tag,
      requireInteraction: true,
      data: notificationData.data || {}
    })
  );
});

// Notification click event
self.addEventListener('notificationclick', event => {
  event.notification.close();

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then(clientList => {
        // Cek apakah sudah ada window terbuka
        for (let i = 0; i < clientList.length; i++) {
          const client = clientList[i];
          if (client.url === '/siapkak/' && 'focus' in client) {
            return client.focus();
          }
        }
        // Jika tidak, buka window baru
        if (clients.openWindow) {
          return clients.openWindow('/siapkak/');
        }
      })
  );
});

// Background sync (untuk offline notification queueing)
self.addEventListener('sync', event => {
  if (event.tag === 'sync-notifications') {
    event.waitUntil(syncNotifications());
  }
});

async function syncNotifications() {
  try {
    const response = await fetch('/siapkak/api/notifications/sync', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    });
    return response.json();
  } catch (error) {
    console.error('Sync notifications failed:', error);
  }
}
