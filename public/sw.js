self.addEventListener('install', () => {
    self.skipWaiting();
});
self.addEventListener('activate', (e) => {
    e.waitUntil(self.clients.claim());
});
self.addEventListener('fetch', () => {});

self.addEventListener('push', (event) => {
    if (!event.data) return;
    let data;
    try { data = event.data.json(); } catch { return; }
    const title = data.title || 'Nova Notificação';
    const options = {
        body: data.body || '',
        icon: data.icon || '/laravelpwa/icons/icon-192x192.png',
        data: data.data || {}
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data.url || '/'));
});

// opcional: re-assinar quando o browser rotaciona as chaves
self.addEventListener('pushsubscriptionchange', (event) => {
    // aqui você pode re-obter VAPID e re-assinar, depois POSTar ao backend
});
