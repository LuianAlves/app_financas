(() => {
    let deferred;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault(); deferred = e;
        document.querySelector('[data-install]')?.classList.remove('d-none');
    });
    document.addEventListener('click', async (e) => {
        const b = e.target.closest('[data-install]'); if (!b || !deferred) return;
        try { await deferred.prompt(); await deferred.userChoice; } finally {
            deferred = null; b.classList.add('d-none');
        }
    });
    const standalone = matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    if (standalone) document.querySelector('[data-install]')?.classList.add('d-none');
})();
