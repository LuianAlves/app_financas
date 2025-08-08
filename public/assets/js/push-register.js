// public/js/push-register.js

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
}

async function initializePush() {
    if (!('serviceWorker' in navigator && 'PushManager' in window)) {
        console.warn('Push não suportado neste navegador');
        return;
    }

    // 1) registra o Service Worker
    try {
        await navigator.serviceWorker.register('/sw.js');
        //console.log('Service Worker registrado');
    } catch (e) {
        console.error('Falha ao registrar SW:', e);
        return;
    }

    const registration = await navigator.serviceWorker.ready;
    //console.log('Service Worker pronto');

    // 2) pede permissão (vai abrir prompt imediatamente)
    if (Notification.permission === 'default') {
        const perm = await Notification.requestPermission();
        //console.log('Permissão de notificação:', perm);
        if (perm !== 'granted') {
            console.warn('Notificações negadas');
            return;
        }
    }

    const vapidKey = await fetch('/vapid-public-key').then(r => r.text());

    let sub = await registration.pushManager.getSubscription();

    if (!sub) {
        sub = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidKey)
        });

        //console.log('Subscription criada', sub);
    } else {
        // console.log('Subscription existente', sub);
    }

    if (!sub) {
        try {
            const resp = await fetch('/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(sub.toJSON())
            });

            console.log('/push/subscribe →', await resp.json());
        } catch (err) {
            console.error('Erro enviando subscription:', err);
        }

        registration.showNotification('🔔 Permissões OK!', {
            body: 'Toque aqui para instalar o app na sua tela inicial.',
            icon: '/laravelpwa/icons/icon-192x192.png',
            data: { url: '/' }
        });
    }
}

function setupPushOnGesture() {
    // dispara direto no load para browsers que aceitam
    initializePush();

    // fallback: se requestPermission não disparar (ex: Safari PWA), aguarda o primeiro toque
    if (Notification.permission === 'default') {
        const handler = () => {
            initializePush();
            window.removeEventListener('click', handler);
            window.removeEventListener('touchstart', handler);
        };
        window.addEventListener('click', handler);
        window.addEventListener('touchstart', handler);
    }
}

// expõe globalmente e dispara no load
window.addEventListener('DOMContentLoaded', setupPushOnGesture);
