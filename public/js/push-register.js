// public/js/push-register.js

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');
    const rawData = window.atob(base64);
    return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
}

document.getElementById('enable-notifications').addEventListener('click', async () => {
    if (!('serviceWorker' in navigator && 'PushManager' in window)) {
        return alert('Este navegador não suporta Push Notifications.');
    }

    // 1) Registrar o SW
    try {
        await navigator.serviceWorker.register('/sw.js');
        console.log('Service Worker registrado');
    } catch (err) {
        return console.error('Erro ao registrar SW:', err);
    }

    const registration = await navigator.serviceWorker.ready;
    console.log('Service Worker pronto:', registration);

    // 2) Pedir permissão dentro do click
    const permission = await Notification.requestPermission();
    console.log('Permissão de notificação:', permission);
    if (permission !== 'granted') {
        return alert('Você negou as notificações. Ative nas configurações do iOS.');
    }

    // 3) Criar ou obter a subscription
    let subscription = await registration.pushManager.getSubscription();
    if (!subscription) {
        const vapidKey = await fetch('/vapid-public-key').then(r => r.text());
        subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidKey)
        });
        console.log('Nova subscription criada:', subscription);
    } else {
        console.log('Subscription existente:', subscription);
    }

    // 4) Enviar para o backend
    try {
        const resp = await fetch('/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(subscription.toJSON())
        });
        const json = await resp.json();
        console.log('/push/subscribe response:', json);
        if (!json.success) throw new Error(json.error);
        alert('Notificações ativadas com sucesso!');
    } catch (err) {
        console.error('Erro ao enviar subscription:', err);
        alert('Não foi possível ativar notificações.');
    }
});
