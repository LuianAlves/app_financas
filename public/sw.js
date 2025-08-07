self.addEventListener('install', e => {
    console.log('Service Worker instalado');
});

self.addEventListener('fetch', () => {});

self.addEventListener('push', event => {
    if (!event.data) return;
    const data = event.data.json();
    const title = data.title || 'Nova Notificação';
    const options = {
        body: data.body || '',
        icon: data.icon || '/laravelpwa/icons/icon-192x192.png',
        data: data.data || {}
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url || '/')
    );
});
