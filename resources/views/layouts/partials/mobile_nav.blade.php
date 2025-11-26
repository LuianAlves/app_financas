<!-- Menu de abas - mobile -->
<div
    id="mobile-app-menu"
    class="fixed inset-0 z-40 hidden md:hidden"
    role="dialog"
    aria-modal="true"
>
    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" data-menu-close></div>

    <!-- Painel -->
    <div
        data-menu-panel
        class="absolute inset-x-0 bottom-0 origin-bottom translate-y-full opacity-0 transition-all duration-200 ease-out"
    >
        <div
            class="mx-auto max-w-md rounded-t-3xl bg-white dark:bg-neutral-950 border-t border-neutral-200/80 dark:border-neutral-800/80 shadow-xl shadow-black/20">
            <div class="flex items-center justify-between px-5 pt-4 pb-2">
                <p class="text-sm font-medium text-neutral-800 dark:text-neutral-100">
                    Acessar áreas do app
                </p>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-full p-1.5 text-neutral-500 hover:text-neutral-800 dark:text-neutral-400 dark:hover:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-neutral-950"
                    data-menu-close
                >
                    <span class="sr-only">Fechar menu</span>
                    <svg viewBox="0 0 24 24" class="size-4" aria-hidden="true">
                        <path fill="currentColor"
                              d="M18.3 5.71 12 12l6.3 6.29-1.42 1.42L10.59 13.4 4.3 19.71 2.89 18.3 9.17 12 2.89 5.71 4.3 4.29 10.59 10.6 16.88 4.3z"/>
                    </svg>
                </button>
            </div>

            <div class="px-5 pb-5 space-y-5">
                {{-- ÁREAS PRINCIPAIS --}}
                <section>
                    <p class="text-[11px] font-medium tracking-wide text-neutral-400 uppercase">
                        Áreas principais
                    </p>

                    <div class="mt-3 grid grid-cols-4 gap-4 text-center text-xs font-medium text-neutral-700 dark:text-neutral-100">
                        {{-- Home --}}
                        <a href="{{ route('dashboard') }}" class="group flex flex-col items-center gap-1">
                <span class="grid place-items-center size-11 rounded-2xl bg-brand-50 text-brand-700 dark:bg-neutral-900 dark:text-brand-200 shadow-soft">
                    {{-- Heroicon home --}}
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 10.5 12 4l9 6.5" />
                        <path d="M5 10.5V20h14v-9.5" />
                    </svg>
                </span>
                            <span class="group-hover:text-brand-700 dark:group-hover:text-brand-200">Home</span>
                        </a>

                        {{-- Transações --}}
                        <a href="{{ route('transaction-view.index') }}" class="group flex flex-col items-center gap-1">
                <span class="grid place-items-center size-11 rounded-2xl bg-brand-50 text-brand-700 dark:bg-neutral-900 dark:text-brand-200 shadow-soft">
                    {{-- Heroicon banknotes --}}
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="6" width="18" height="12" rx="2" />
                        <circle cx="12" cy="12" r="2.7" />
                        <path d="M7 9h.01M17 15h.01" />
                    </svg>
                </span>
                            <span class="group-hover:text-brand-700 dark:group-hover:text-brand-200">Transações</span>
                        </a>

                        {{-- Projeções --}}
                        <a href="{{ route('projection-view.index') }}" class="group flex flex-col items-center gap-1">
                <span class="grid place-items-center size-11 rounded-2xl bg-brand-50 text-brand-700 dark:bg-neutral-900 dark:text-brand-200 shadow-soft">
                    {{-- Heroicon chart-bar --}}
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 20h16" />
                        <rect x="5" y="10" width="3" height="7" rx="1" />
                        <rect x="10.5" y="7" width="3" height="10" rx="1" />
                        <rect x="16" y="4" width="3" height="13" rx="1" />
                    </svg>
                </span>
                            <span class="group-hover:text-brand-700 dark:group-hover:text-brand-200">Projeções</span>
                        </a>

                        {{-- Meu perfil --}}
                        <a href="{{ route('user-view.index') }}" class="group flex flex-col items-center gap-1">
                <span class="grid place-items-center size-11 rounded-2xl bg-brand-50 text-brand-700 dark:bg-neutral-900 dark:text-brand-200 shadow-soft">
                    {{-- Heroicon user --}}
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="3.2" />
                        <path d="M6.2 19a6.1 6.1 0 0 1 11.6 0" />
                    </svg>
                </span>
                            <span class="group-hover:text-brand-700 dark:group-hover:text-brand-200">Perfil</span>
                        </a>
                    </div>
                </section>

                {{-- DIVISÓRIA --}}
                <div class="h-px bg-neutral-100 dark:bg-neutral-800"></div>

                {{-- FINANÇAS --}}
                <section>
                    <p class="text-[11px] font-medium tracking-wide text-neutral-400 uppercase">
                        Finanças
                    </p>

                    <div class="mt-3 grid grid-cols-4 gap-4 text-center text-xs font-medium text-neutral-700 dark:text-neutral-100">
                        {{-- Contas --}}
                        <a href="{{ route('account-view.index') }}" class="group flex flex-col items-center gap-1">
                <span class="grid place-items-center size-11 rounded-2xl bg-brand-50 text-brand-700 dark:bg-neutral-900 dark:text-brand-200 shadow-soft">
                    {{-- Heroicon building-bank --}}
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 10h16L12 4 4 10Z" />
                        <path d="M5 10v8M9 10v8M15 10v8M19 10v8" />
                        <path d="M4 18h16" />
                    </svg>
                </span>
                            <span class="group-hover:text-brand-700 dark:group-hover:text-brand-200">Contas</span>
                        </a>

                        {{-- Cartões --}}
                        <a href="{{ route('card-view.index') }}" class="group flex flex-col items-center gap-1">
                <span class="grid place-items-center size-11 rounded-2xl bg-brand-50 text-brand-700 dark:bg-neutral-900 dark:text-brand-200 shadow-soft">
                    {{-- Heroicon credit-card --}}
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="5" width="18" height="14" rx="2" />
                        <path d="M3 10h18" />
                        <path d="M8 15h3" />
                    </svg>
                </span>
                            <span class="group-hover:text-brand-700 dark:group-hover:text-brand-200">Cartões</span>
                        </a>

                        {{-- Categorias --}}
                        <a href="{{ route('transactionCategory-view.index') }}" class="group flex flex-col items-center gap-1">
                <span class="grid place-items-center size-11 rounded-2xl bg-brand-50 text-brand-700 dark:bg-neutral-900 dark:text-brand-200 shadow-soft">
                    {{-- Heroicon tag --}}
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 11.5 11.5 3 21 12.5 12.5 21 3 11.5Z" />
                        <circle cx="9" cy="9" r="1" />
                    </svg>
                </span>
                            <span class="group-hover:text-brand-700 dark:group-hover:text-brand-200">Categorias</span>
                        </a>

                        {{-- Cofrinhos / Savings --}}
                        <a href="{{ route('saving-view.index') }}" class="group flex flex-col items-center gap-1">
                <span class="grid place-items-center size-11 rounded-2xl bg-brand-50 text-brand-700 dark:bg-neutral-900 dark:text-brand-200 shadow-soft">
                    {{-- Heroicon piggy-bank-like --}}
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 11a6 6 0 0 1 10-3h2.5A1.5 1.5 0 0 1 19 9.5v1.4a2 2 0 0 1-.6 1.4l-.4.4v2.3a1 1 0 0 1-1 1h-1" />
                        <path d="M3 12h1.3A6 6 0 0 0 9 18h4" />
                        <circle cx="10" cy="10" r="0.8" />
                    </svg>
                </span>
                            <span class="group-hover:text-brand-700 dark:group-hover:text-brand-200">Cofrinhos</span>
                        </a>
                    </div>
                </section>

                {{-- DIVISÓRIA --}}
                <div class="h-px bg-neutral-100 dark:bg-neutral-800"></div>

                {{-- ATALHOS --}}
                <section>
                    <p class="text-[11px] font-medium tracking-wide text-neutral-400 uppercase">
                        Atalhos
                    </p>

                    <div class="mt-3 grid grid-cols-4 gap-4 text-center text-xs font-medium text-neutral-700 dark:text-neutral-100">
                        {{-- Lançamentos do dia --}}
                        <a href="{{ route('digest.index') }}" class="group flex flex-col items-center gap-1">
                <span class="grid place-items-center size-11 rounded-2xl bg-brand-50 text-brand-700 dark:bg-neutral-900 dark:text-brand-200 shadow-soft">
                    {{-- Heroicon calendar-days --}}
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="17" rx="2" />
                        <path d="M8 3v3M16 3v3M3 10h18" />
                        <path d="M8 15h.01M12 15h.01M16 15h.01" />
                    </svg>
                </span>
                            <span class="group-hover:text-brand-700 dark:group-hover:text-brand-200 text-[11px] leading-tight">
                    Lanç. do dia
                </span>
                        </a>

                        {{-- Adicione outros atalhos se quiser --}}
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
