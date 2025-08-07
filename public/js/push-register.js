// public/js/push-register.js

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');
    const rawData = window.atob(base64);
    return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
}

if ('serviceWorker' in navigator && 'PushManager' in window) {
    // 1) Registra o SW
    navigator.serviceWorker.register('/sw.js')
        .then(() => console.log('Service Worker registrado'))
        .catch(err => console.error('Erro ao registrar SW:', err));

    // 2) Quando o SW estiver pronto
    navigator.serviceWorker.ready.then(async registration => {
        console.log('Service Worker pronto:', registration);

        // 3) Pede permissão (se ainda não tiver feito)
        const perm = await Notification.requestPermission();
        console.log('Permissão de notificação:', perm);
        if (perm !== 'granted') {
            return console.error('Permissão de notificações negada');
        }

        // 4) Pega a subscription existente ou cria uma nova
        let subscription = await registration.pushManager.getSubscription();
        if (!subscription) {
            const vapidKey = await fetch('/vapid-public-key').then(r => r.text());
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidKey)
            });
            console.log('Nova subscription criada:', subscription);
        } else {
            console.log('Subscription existente encontrada:', subscription);
        }

        // 5) Envia (ou re‐envia) para o Laravel
        try {
            const resp = await fetch('/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(subscription.toJSON())
            });
            console.log('/push/subscribe response:', await resp.json());
        } catch (err) {
            console.error('Erro ao enviar subscription:', err);
        }
    });
}
