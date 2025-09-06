<!doctype html>
<html lang="pt-br" class="h-full antialiased">

<!-- Include:head -->
@include('layouts.partials.head')

<body class="min-h-screen text-neutral-900 dark:text-neutral-100 bg-white dark:bg-gradient-to-b dark:from-neutral-950 dark:to-neutral-900 selection:bg-brand-200 selection:text-neutral-900">

    <a href="#conteudo" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 bg-white dark:bg-neutral-800 text-sm px-3 py-2 rounded-lg shadow-soft dark:shadow-softDark">Pular para o conteúdo</a>

    <div class="md:grid md:grid-cols-[260px_1fr] md:min-h-screen">
        <!-- Include:sidebar -->
        @include('layouts.partials.sidenav')

        <div class="relative flex flex-col min-h-screen md:min-h-0">
            <!-- Include:navbar -->
            @include('layouts.partials.navbar')

            <main id="conteudo" class="flex-1 max-w-7xl mx-auto w-full px-4 pb-28 md:pb-8 md:pt-6">
                @yield('new-content')
            </main>

            <!-- Include:bottom_nav -->
            @include('layouts.partials.bottom_nav')

            <!-- Speed Dial / FAB (mobile) -->
            <div id="speedDial" class="md:hidden fixed bottom-20 right-4 z-50">
                <div class="relative">
                    <!-- Ações -->
                    <div id="speedDialActions" class="absolute -top-2 right-1 flex flex-col items-end gap-2 opacity-0 translate-y-3 pointer-events-none transition">
                        <button data-open-modal="tx"
                                class="size-12 grid place-items-center rounded-full shadow-lg bg-emerald-500 text-white active:scale-95"
                                aria-label="Nova receita">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                        </button>
                        <button
                            class="size-12 grid place-items-center rounded-full shadow-lg bg-amber-400 text-white active:scale-95"
                            aria-label="Novo pagamento">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 7H4"/>
                                <path d="M20 11H8"/>
                                <path d="M20 15H4"/>
                                <path d="M20 19H8"/>
                            </svg>
                        </button>
                        <button
                            class="size-12 grid place-items-center rounded-full shadow-lg bg-rose-500 text-white active:scale-95"
                            aria-label="Nova despesa">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 12H2"/>
                            </svg>
                        </button>
                    </div>
                    <!-- Botão principal -->
                    <button id="speedDialMain"
                            class="size-14 rounded-2xl grid place-items-center text-white shadow-lg shadow-brand-600/30 bg-gradient-to-br from-brand-500 to-brand-700 active:scale-95 transition"
                            aria-label="Ações rápidas" aria-expanded="false">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Include:scripts -->
    @include('layouts.partials.scripts')
</body>
</html>

