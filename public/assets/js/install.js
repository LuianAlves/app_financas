(() => {
    let deferred;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferred = e;
        document.querySelector('[data-install]')?.classList.remove('d-none');
    });

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-install]');
        if (!btn || !deferred) return;
        btn.disabled = true;
        try { await deferred.prompt(); await deferred.userChoice; }
        finally { deferred = null; btn.disabled = false; btn.classList.add('d-none'); }
    });

    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    if (isStandalone) document.querySelector('[data-install]')?.classList.add('d-none');
})();
