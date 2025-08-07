// urlBase64ToUint8Array vem de ex.: https://stackoverflow.com/a/46475254
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
}

async function initializePush() {
    if (!('serviceWorker' in navigator && 'PushManager' in window)) return;

    // 1) registra o SW (ajuste o path se estiver em subpasta)
    try {
        await navigator.serviceWorker.register('/laravelpwa/sw.js');
        console.log('Service Worker registrado');
    } catch (e) {
        return console.error('Falha ao registrar SW:', e);
    }

    const registration = await navigator.serviceWorker.ready;
    console.log('Service Worker pronto');

    // 2) se ainda não granted, pede permissão
    if (Notification.permission !== 'granted') {
        const perm = await Notification.requestPermission();
        console.log('Permissão:', perm);
        if (perm !== 'granted') {
            return console.warn('Notificações negadas');
        }
    }

    // 3) cria ou recupera a subscription
    const vapidKey = await fetch('/vapid-public-key').then(r => r.text());
    let sub = await registration.pushManager.getSubscription();
    if (!sub) {
        sub = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidKey)
        });
        console.log('Subscription criada', sub);
    } else {
        console.log('Subscription existente', sub);
    }

    // 4) envia pro backend
    try {
        const resp = await fetch('/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type':'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(sub.toJSON())
        });
        console.log('/push/subscribe →', await resp.json());
    } catch (err) {
        console.error('Erro enviando subscription:', err);
    }
}

function setupPushOnGesture() {
    if (Notification.permission === 'granted') {
        initializePush();          // já concedido: dispara agora
    } else if (Notification.permission === 'default') {
        // aguarda o primeiro clique ou toque
        const handler = () => {
            initializePush();
            window.removeEventListener('click', handler);
            window.removeEventListener('touchstart', handler);
        };
        window.addEventListener('click', handler);
        window.addEventListener('touchstart', handler);
    } else {
        console.warn('Notificações negadas — ative manualmente nas configurações.');
    }
}

// expõe no escopo global
window.setupPushOnGesture = setupPushOnGesture;
