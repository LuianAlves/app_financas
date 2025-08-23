(() => {
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const standalone = matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    const installBtn = document.querySelector('[data-install]');
    const iosHint = document.getElementById('ios-a2hs');

    // Sempre começa escondido (d-none já está no HTML)
    let deferred;

    // iOS: não existe beforeinstallprompt. Mostre só o hint quando NÃO estiver instalado.
    if (isIOS) {
        // botão nunca aparece no iOS
        installBtn?.classList.add('d-none');
        if (!standalone) iosHint?.classList.remove('d-none');
        return; // encerra: o restante é só Android/Chrome
    }

    // Android/Chrome/Edge: mostra o botão quando o navegador realmente permite instalar
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferred = e;
        installBtn?.classList.remove('d-none'); // aparece só quando pode instalar
        iosHint?.classList.add('d-none');
    });

    // Se já estiver instalado, mantenha oculto
    if (standalone) {
        installBtn?.classList.add('d-none');
        iosHint?.classList.add('d-none');
    }

    // Clique do botão
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-install]');
        if (!btn || !deferred) return;
        try {
            await deferred.prompt();
            await deferred.userChoice;
        } finally {
            deferred = null;
            btn.classList.add('d-none'); // esconde após a tentativa
        }
    });

    // Opcional: se o app foi instalado por outro fluxo, esconda também
    window.addEventListener('appinstalled', () => {
        installBtn?.classList.add('d-none');
        iosHint?.classList.add('d-none');
    });
})();

