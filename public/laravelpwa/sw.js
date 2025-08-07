self.addEventListener('install', function(e) {
    console.log('Service Worker instalado');
});

self.addEventListener('fetch', function(event) {
});

self.addEventListener('push', function(event) {
    if (!event.data) return;

    const data = event.data.json();

    const title = data.title || 'Nova Notificação';
    const options = {
        body: data.body || '',
        icon: '/icons/icon-192x192.png',
        data: {
            url: data.data?.url || '/'
        }
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});
