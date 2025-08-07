if ('serviceWorker' in navigator && 'PushManager' in window) {
    navigator.serviceWorker.register('/sw.js').then(function(registration) {
        console.log('Service Worker registrado');

        registration.pushManager.getSubscription().then(async function(subscription) {
            if (subscription) return;

            const response = await fetch('/vapid-public-key');
            const vapidPublicKey = await response.text();

            const convertedVapidKey = urlBase64ToUint8Array(vapidPublicKey);

            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: convertedVapidKey
            });

            await fetch('/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(subscription)
            });

            console.log('Push registrado com sucesso');
        });
    });
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+').replace(/_/g, '/');

    const rawData = window.atob(base64);
    return Uint8Array.from([...rawData].map(char => char.charCodeAt(0)));
}
