(function () {
    const ua = navigator.userAgent || '';
    window.PUSH_CFG = Object.assign({
        vapidKeyUrl: '/vapid-public-key',
        subscribeUrl: '/push/subscribe',
        swUrl: '/sw.js',
        loginPath: '/login',
        isIOS: /iPad|iPhone|iPod/.test(ua) && !window.MSStream
    }, window.PUSH_CFG || {});

    // ==== helpers ====
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = atob(base64);
        return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
    }

    async function registerSW() {
        if (!('serviceWorker' in navigator)) return null;
        try {
            return await navigator.serviceWorker.register(window.PUSH_CFG.swUrl);
        } catch (e) {
            console.error('Falha ao registrar SW:', e);
            return null;
        }
    }

    async function postSubscription(subJSON) {
        // se não logado, guarda e sai
        if (!window.AUTH) {
            localStorage.setItem('pendingPushSub', JSON.stringify(subJSON));
            return;
        }
        // logado → envia
        await fetch(window.PUSH_CFG.subscribeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(subJSON)
        });
    }

    async function sendPendingIfAny() {
        if (!window.AUTH) return;
        const pending = localStorage.getItem('pendingPushSub');
        if (!pending) return;
        try {
            await fetch(window.PUSH_CFG.subscribeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: pending
            });
        } finally {
            localStorage.removeItem('pendingPushSub');
        }
    }

    let initializing = false; // trava simples para não duplicar

    async function initializePush() {
        if (initializing) return;
        initializing = true;

        try {
            if (!('serviceWorker' in navigator && 'PushManager' in window)) {
                console.warn('Push não suportado neste navegador');
                return;
            }

            const registration = await registerSW();
            if (!registration) return;
            await navigator.serviceWorker.ready;

            // permissão
            if (Notification.permission === 'default') {
                const perm = await Notification.requestPermission();
                if (perm !== 'granted') {
                    console.warn('Notificações negadas');
                    return;
                }
            } else if (Notification.permission !== 'granted') {
                console.warn('Notificações negadas');
                return;
            }

            // VAPID
            let vapidKey;
            try {
                const respKey = await fetch(window.PUSH_CFG.vapidKeyUrl, {cache: 'no-store'});
                vapidKey = (await respKey.text()).trim();
            } catch (e) {
                console.error('Falha ao obter VAPID:', e);
                return;
            }

            // assinatura
            let sub = await registration.pushManager.getSubscription();
            if (!sub) {
                try {
                    sub = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array(vapidKey)
                    });
                } catch (e) {
                    console.error('Falha ao assinar Push:', e);
                    return;
                }
            }

            await postSubscription(sub.toJSON());

            // marcou como concedido → não precisa mais de gesto
            localStorage.setItem('pushGranted', '1');
        } finally {
            initializing = false;
        }
    }

    async function ensurePermissionByGesture() {
        const isStandalone = matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
        const isiOS = window.PUSH_CFG.isIOS;

        async function ensurePermissionByGesture() {
            if (isiOS && !isStandalone) {
                return;
            }

            // fluxo atual:
            if (Notification.permission === 'granted') {
                await initializePush();
            } else if (Notification.permission === 'default') {
                // precise de gesto? mantenha seu handler de clique/touch aqui
                const handler = async () => {
                    await initializePush();
                    document.removeEventListener('click', handler);
                    document.removeEventListener('touchstart', handler);
                };
                document.addEventListener('click', handler, {once: true});
                document.addEventListener('touchstart', handler, {once: true});
            }
        }
    }

    window.addEventListener('DOMContentLoaded', async () => {
        await sendPendingIfAny();   // se logou agora, envia a sub pendente
        await ensurePermissionByGesture();
    });
})();
