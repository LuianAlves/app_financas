(() => {
    // Elementos
    const installBtn = document.querySelector('[data-install]'); // comece com d-none no HTML
    const iosHint = document.getElementById('ios-a2hs');

    // --- Detectores robustos ---
    const ua = navigator.userAgent || '';
    const isStandalone =
        matchMedia('(display-mode: standalone)').matches ||
        (navigator.standalone === true); // iOS Safari

    // iOS/iPadOS: inclui casos em que o iPadOS reporta "MacIntel" mas tem touch
    const isIOSLike = (() => {
        const iOSUA = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
        const iPadOS13Plus = navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1;
        return iOSUA || iPadOS13Plus;
    })();

    // Desktop (macOS/Windows/Linux) = NÃO iOSLike e não Android
    const isAndroid = /Android/i.test(ua);

    // Garantia: começa tudo escondido
    installBtn?.classList.add('d-none');
    iosHint?.classList.add('d-none');

    // Já instalado? (PWA aberto em standalone)
    if (isStandalone) {
        // Nada a exibir em nenhum SO
        return;
    }

    // --- Fluxo iOS/iPadOS: mostrar HINT apenas no iOS quando não instalado ---
    if (isIOSLike) {
        // iOS não tem beforeinstallprompt — o fluxo é via menu “Compartilhar”
        iosHint?.classList.remove('d-none');   // mostra o banner de instrução
        installBtn?.classList.add('d-none');   // botão nunca aparece no iOS
        return; // encerra aqui (o restante é Android/desktop)
    }

    // --- Fluxo Android/Chrome/Edge: mostrar botão só quando o navegador permitir ---
    let deferred = null;

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferred = e;
        // Mostra botão **apenas** quando pode instalar
        installBtn?.classList.remove('d-none');
        iosHint?.classList.add('d-none');
    });

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-install]');
        if (!btn || !deferred) return;
        try {
            await deferred.prompt();
            await deferred.userChoice; // opcional: pode inspecionar outcome
        } finally {
            deferred = null;
            btn.classList.add('d-none'); // esconde após a tentativa
        }
    });

    // Se o navegador sinalizar que foi instalado por outro caminho, esconda tudo
    window.addEventListener('appinstalled', () => {
        installBtn?.classList.add('d-none');
        iosHint?.classList.add('d-none');
    });

    // Em desktop (não iOS, não Android), normalmente nada aparece — ok.
})();

