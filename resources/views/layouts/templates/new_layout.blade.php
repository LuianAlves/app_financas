<!doctype html>
<html lang="pt-br" class="h-full antialiased">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>UX/UI Demo • Banking Webapp + Dashboard</title>

    <!-- Tailwind (CDN) -->
    <!-- 0) Aplica tema antes do paint -->
    <script>
        (function () {
            try {
                const saved = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const isDark = saved ? saved === 'dark' : prefersDark;
                document.documentElement.classList.toggle('dark', isDark);
            } catch (e) {}
        })();
    </script>

    <!-- 1) Carrega Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- 2) Configura Tailwind (AGORA com tailwind.config, após o CDN) -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50:  '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a'
                        }
                    },
                    boxShadow: {
                        soft: '0 2px 10px rgba(0,0,0,.06)',
                        softDark: '0 6px 24px rgba(0,0,0,.35)'
                    }
                }
            }
        };
    </script>

    <meta name="color-scheme" content="light dark" />
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cpath fill='%233b82f6' d='M16 2l14 8v12l-14 8-14-8V10z'/%3E%3Cpath fill='%23fff' d='M16 7l9 5v8l-9 5-9-5v-8z'/%3E%3C/svg%3E"/>
    <style>
        /* rolagem suave e redução de animações para acessibilidade */
        html { scroll-behavior: smooth; }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            * { animation: none !important; transition: none !important; }
        }
    </style>
</head>

<body class="min-h-screen text-neutral-900 dark:text-neutral-100 bg-gradient-to-b from-brand-50/60 to-white dark:bg-gradient-to-b dark:from-neutral-950 dark:to-neutral-900 selection:bg-brand-200 selection:text-neutral-900">

<a href="#conteudo" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 bg-white dark:bg-neutral-800 text-sm px-3 py-2 rounded-lg shadow-soft dark:shadow-softDark">Pular para o conteúdo</a>

<!-- Layout geral -->
<div class="md:grid md:grid-cols-[260px_1fr] md:min-h-screen">
    <!-- Sidebar (desktop >=768px) -->
    <aside class="hidden md:flex md:flex-col md:gap-4 md:px-4 md:py-6 bg-gradient-to-b from-neutral-50/80 to-white dark:from-neutral-950/80 dark:to-neutral-900/60 border-r border-neutral-200/70 dark:border-neutral-800/70 backdrop-blur supports-[backdrop-filter]:bg-neutral-50/40 supports-[backdrop-filter]:dark:bg-neutral-900/40">
        <div class="flex items-center gap-2 px-2">
            <div class="size-9 grid place-items-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-white shadow-soft">
                <!-- Logo -->
                <svg viewBox="0 0 24 24" class="size-5" aria-hidden="true"><path fill="currentColor" d="M12 2l9 5v10l-9 5-9-5V7z"/></svg>
            </div>
            <div>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">Painel</p>
                <p class="font-semibold">BlueBank UI</p>
            </div>
        </div>

        <nav aria-label="Principal" class="mt-4 space-y-1">
            <a href="#saldo" data-nav="home" aria-current="page" class="group flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium bg-brand-50 dark:bg-neutral-800 text-brand-700 dark:text-neutral-100 hover:bg-brand-100/70 dark:hover:bg-neutral-800/70 transition">
            <span class="grid place-items-center size-8 rounded-lg bg-white/70 dark:bg-neutral-900/70 text-brand-600 dark:text-neutral-100 shadow-soft">
              <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12l2-2 4 4 8-8 4 4"></path></svg>
            </span>
                Visão geral
            </a>
            <a href="#transacoes-page" data-nav class="group flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition">
            <span class="grid place-items-center size-8 rounded-lg bg-white/70 dark:bg-neutral-900/70 shadow-soft">
              <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6"></path><rect x="2" y="13" width="20" height="8" rx="2"></rect></svg>
            </span>
                Transações
            </a>
            <a href="#atalhos" data-nav class="group flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition">
            <span class="grid place-items-center size-8 rounded-lg bg-white/70 dark:bg-neutral-900/70 shadow-soft">
              <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            </span>
                Atalhos
            </a>
            <a href="#cartoes" data-nav class="group flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition">
            <span class="grid place-items-center size-8 rounded-lg bg-white/70 dark:bg-neutral-900/70 shadow-soft">
              <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
            </span>
                Cartões
            </a>
            <a href="#perfil" data-nav class="group flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition">
            <span class="grid place-items-center size-8 rounded-lg bg-white/70 dark:bg-neutral-900/70 shadow-soft">
              <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a8.38 8.38 0 0 1 13 0"/></svg>
            </span>
                Perfil
            </a>
        </nav>

        <div class="mt-auto p-3 rounded-xl bg-gradient-to-br from-brand-50 to-white dark:from-neutral-800 dark:to-neutral-900 border border-neutral-200/70 dark:border-neutral-800/70">
            <p class="text-sm font-medium">Meta do mês</p>
            <p class="text-xs text-neutral-500 dark:text-neutral-400">Economizar R$ 1.500</p>
            <div class="mt-3 h-2 rounded-full bg-neutral-200 dark:bg-neutral-800 overflow-hidden">
                <div class="h-full w-[62%] bg-brand-500 rounded-full"></div>
            </div>
            <p class="mt-2 text-xs text-neutral-600 dark:text-neutral-400"><span class="font-semibold">62%</span> atingido</p>
        </div>
    </aside>

    <!-- Coluna principal -->
    <div class="relative flex flex-col min-h-screen md:min-h-0">
        <!-- App Bar (mobile) / Topbar (desktop) -->
        <header class="sticky top-0 z-40 border-b border-neutral-200/70 dark:border-neutral-800/70 bg-white/70 dark:bg-neutral-950/60 backdrop-blur supports-[backdrop-filter]:bg-white/50 supports-[backdrop-filter]:dark:bg-neutral-950/50">
            <div class="max-w-7xl mx-auto flex items-center justify-between gap-3 px-4 py-3 md:py-4">
                <div class="flex items-center gap-3">
                    <button id="btnMenu" class="md:hidden grid place-items-center size-10 rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-900 transition" aria-label="Abrir menu">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    </button>
                    <div class="hidden md:flex items-center gap-2">
                        <div class="size-9 grid place-items-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-white shadow-soft">
                            <svg viewBox="0 0 24 24" class="size-5" aria-hidden="true"><path fill="currentColor" d="M12 2l9 5v10l-9 5-9-5V7z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">Bem-vindo(a),</p>
                            <p class="font-semibold">Marina</p>
                        </div>
                    </div>
                    <div class="md:ml-4 md:w-96 relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-5 text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <input type="search" placeholder="Buscar" class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/80 dark:bg-neutral-900/60 focus:outline-none focus:ring-4 focus:ring-brand-500/20"/>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button id="btnTheme" class="grid place-items-center size-10 rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-900 transition" aria-label="Alternar tema" aria-pressed="false">
                        <svg id="iconSun" class="size-5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                        <svg id="iconMoon" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
                    </button>
                    <button class="relative grid place-items-center size-10 rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-900 transition" aria-label="Notificações">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 5 3 9H3c0-4 3-2 3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <span class="absolute -top-1 -right-1 size-4 rounded-full bg-brand-500 text-white text-[10px] grid place-items-center">3</span>
                    </button>
                    <button class="hidden md:flex items-center gap-2 pl-1 pr-3 py-1.5 rounded-full border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-900 transition" aria-label="Abrir perfil">
                        <img src="https://api.dicebear.com/9.x/thumbs/svg?seed=Marina" alt="Avatar" class="size-8 rounded-full"/>
                        <span class="text-sm">Marina</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Conteúdo -->
        <main id="conteudo" class="flex-1 max-w-7xl mx-auto w-full px-4 pb-28 md:pb-8 md:pt-6">
            @yield('content')
        </main>

        <!-- Bottom Nav (mobile <768px) → oculta no desktop -->
        <nav aria-label="Navegação inferior" class="md:hidden fixed bottom-0 left-0 right-0 z-40 border-t border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-950/80 backdrop-blur">
            <ul class="grid grid-cols-4">
                <li>
                    <a href="#saldo" data-tab class="group flex flex-col items-center gap-1 py-3 text-xs font-medium aria-[current=page]:text-brand-600">
                        <svg class="size-5 opacity-70 group-aria-[current=page]:opacity-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12l2-2 4 4 8-8 4 4"></path></svg>
                        Início
                    </a>
                </li>
                <li>
                    <a href="#transacoes-page" data-tab class="group flex flex-col items-center gap-1 py-3 text-xs font-medium">
                        <svg class="size-5 opacity-70 group-aria-[current=page]:opacity-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6"/><rect x="2" y="13" width="20" height="8" rx="2"/></svg>
                        Transações
                    </a>
                </li>
                <li>
                    <a href="#cartoes" data-tab class="group flex flex-col items-center gap-1 py-3 text-xs font-medium">
                        <svg class="size-5 opacity-70 group-aria-[current=page]:opacity-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                        Cartões
                    </a>
                </li>
                <li>
                    <a href="#perfil" data-tab class="group flex flex-col items-center gap-1 py-3 text-xs font-medium">
                        <svg class="size-5 opacity-70 group-aria-[current=page]:opacity-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a8.38 8.38 0 0 1 13 0"/></svg>
                        Perfil
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Speed Dial / FAB (mobile) -->
        <div id="speedDial" class="md:hidden fixed bottom-20 right-4 z-50">
            <div class="relative">
                <!-- Ações -->
                <div id="speedDialActions" class="absolute -top-2 right-1 flex flex-col items-end gap-2 opacity-0 translate-y-3 pointer-events-none transition">
                    <button data-open-modal="tx" class="size-12 grid place-items-center rounded-full shadow-lg bg-emerald-500 text-white active:scale-95" aria-label="Nova receita">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    </button>
                    <button class="size-12 grid place-items-center rounded-full shadow-lg bg-amber-400 text-white active:scale-95" aria-label="Novo pagamento">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7H4"/><path d="M20 11H8"/><path d="M20 15H4"/><path d="M20 19H8"/></svg>
                    </button>
                    <button class="size-12 grid place-items-center rounded-full shadow-lg bg-rose-500 text-white active:scale-95" aria-label="Nova despesa">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12H2"/></svg>
                    </button>
                </div>
                <!-- Botão principal -->
                <button id="speedDialMain" class="size-14 rounded-2xl grid place-items-center text-white shadow-lg shadow-brand-600/30 bg-gradient-to-br from-brand-500 to-brand-700 active:scale-95 transition" aria-label="Ações rápidas" aria-expanded="false">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts de interação -->
<script>
    // Tema: alternância e sincronização de ícones
    const btnTheme = document.getElementById('btnTheme');
    const iconSun = document.getElementById('iconSun');
    const iconMoon = document.getElementById('iconMoon');

    function syncThemeUI() {
        const isDark = document.documentElement.classList.contains('dark');
        if (iconSun) iconSun.classList.toggle('hidden', !isDark);
        if (iconMoon) iconMoon.classList.toggle('hidden', isDark);
        if (btnTheme) btnTheme.setAttribute('aria-pressed', String(isDark));
    }

    if (btnTheme) {
        btnTheme.addEventListener('click', () => {
            const nowDark = document.documentElement.classList.toggle('dark');
            try { localStorage.setItem('theme', nowDark ? 'dark' : 'light'); } catch (e) {}
            syncThemeUI();
        });
    }

    document.addEventListener('DOMContentLoaded', syncThemeUI);

    // Marca item ativo no bottom nav ao rolar (mobile)
    const tabs = document.querySelectorAll('[data-tab]');
    const sections = Array.from(tabs).map(a => document.querySelector(a.getAttribute('href')));
    const io = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            const i = sections.indexOf(e.target);
            if (i >= 0 && e.isIntersecting) {
                tabs.forEach(t => t.removeAttribute('aria-current'));
                tabs[i].setAttribute('aria-current','page');
            }
        });
    }, { rootMargin: '-40% 0px -55% 0px', threshold: 0 });
    sections.forEach(s => s && io.observe(s));

    // Animação simples do saldo (contagem)
    const saldoEl = document.getElementById('saldoValor');

    if (saldoEl) {
        const target = 12450.27; // valor de exemplo
        let start = null;
        const fmt = v => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        function step(ts) {
            if (!start) start = ts;
            const p = Math.min(1, (ts - start) / 900);
            const val = target * (0.7 + 0.3 * p); // começa em 70% e vai até 100%
            saldoEl.textContent = fmt(val);
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    // Ação de menu (apenas demo)
    document.getElementById('btnMenu')?.addEventListener('click', () => {
        alert('Menu lateral disponível no desktop.');
    });

    // Speed Dial
    const sd = document.getElementById('speedDial');
    const sdMain = document.getElementById('speedDialMain');
    const sdActions = document.getElementById('speedDialActions');
    function toggleDial(force) {
        const open = force ?? sdMain.getAttribute('aria-expanded') === 'false';
        sdMain.setAttribute('aria-expanded', String(open));
        sdActions.style.pointerEvents = open ? 'auto' : 'none';
        sdActions.style.opacity = open ? '1' : '0';
        sdActions.style.transform = open ? 'translateY(0)' : 'translateY(12px)';
    }
    sdMain?.addEventListener('click', () => toggleDial());
    document.addEventListener('click', (e) => {
        if (!sd) return;
        if (!sd.contains(e.target)) toggleDial(false);
    });

    // Modal de transação
    const txModal = document.getElementById('txModal');
    const txOverlay = document.getElementById('txModalOverlay');
    const txClose = document.getElementById('txClose');
    const txCancel = document.getElementById('txCancel');
    const txForm = document.getElementById('txForm');

    function openTxModal() {
        txModal?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        const first = txForm?.querySelector('input, select, textarea, button');
        first?.focus();
    }
    function closeTxModal() {
        txModal?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        sdMain?.focus();
    }

    document.querySelectorAll('[data-open-modal="tx"]').forEach(btn =>
        btn.addEventListener('click', openTxModal)
    );
    txOverlay?.addEventListener('click', closeTxModal);
    txClose?.addEventListener('click', closeTxModal);
    txCancel?.addEventListener('click', closeTxModal);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !txModal?.classList.contains('hidden')) closeTxModal();
    });

    txForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        alert('Transação salva! (exemplo)');
        closeTxModal();
    });
</script>

@stack('scripts')
</body>
</html>
