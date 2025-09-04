@extends('layouts.templates.new_layout')


@section('new-content')
    <!-- Hero/Saldo -->
    <section id="saldo" class="mt-4 md:mt-0">
        <div
            class="relative overflow-hidden rounded-2xl p-5 md:p-6 bg-gradient-to-br from-brand-400 to-brand-600 dark:from-neutral-900 dark:to-neutral-800 text-white shadow-soft dark:shadow-softDark">
            <div
                class="absolute -top-24 -right-24 w-72 h-72 rounded-full bg-white/15 dark:bg-neutral-800/80 blur-2xl"></div>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-white/80 text-sm">Saldo total</p>
                    <p id="saldoValor" class="mt-1 text-3xl md:text-4xl font-semibold tracking-tight"
                       aria-live="polite">R$ 12.450,27</p>
                </div>
                <div class="grid gap-2 text-right">
                    <p class="text-xs/none text-white/80">Última atualização agora</p>
                    <button
                        class="self-end inline-flex items-center gap-2 bg-white/15 hover:bg-white/25 active:bg-white/15 dark:bg-neutral-800/80 transition px-3 py-1.5 rounded-xl backdrop-blur-md">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 12a9 9 0 1 1-9-9"/>
                            <path d="M21 3v6h-6"/>
                        </svg>
                        Atualizar
                    </button>
                </div>
            </div>

            <!-- sparkline -->
            <svg class="mt-6 w-full h-16 md:h-20 opacity-90" viewBox="0 0 400 80" aria-hidden="true">
                <defs>
                    <linearGradient id="grad" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0%" stop-color="rgba(255,255,255,0.9)"/>
                        <stop offset="100%" stop-color="rgba(255,255,255,0.1)"/>
                    </linearGradient>
                </defs>
                <path d="M0 50 C 40 20, 80 70, 120 40 S 200 20, 240 45 S 320 60, 360 35 S 400 70, 400 70"
                      stroke="white" stroke-width="2" fill="url(#grad)"></path>
            </svg>

            <!-- Atalhos (também usados no desktop) -->
            <div id="atalhos" class="mt-5 grid grid-cols-4 gap-2 md:gap-3">
                <button
                    class="group flex flex-col items-center gap-2 p-3 rounded-2xl bg-white/15 dark:bg-neutral-800/80 hover:bg-white/25 dark:hover:bg-neutral-800 active:bg-white/15 dark:bg-neutral-800/80 transition focus:outline-none focus:ring-4 focus:ring-white/30">
                  <span class="grid place-items-center size-10 rounded-xl bg-white/80 text-brand-600">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                  </span>
                    <span class="text-xs">Adicionar</span>
                </button>
                <button
                    class="group flex flex-col items-center gap-2 p-3 rounded-2xl bg-white/15 dark:bg-neutral-800/80 hover:bg-white/25 dark:hover:bg-neutral-800 active:bg-white/15 dark:bg-neutral-800/80 transition focus:outline-none focus:ring-4 focus:ring-white/30">
                  <span class="grid place-items-center size-10 rounded-xl bg-white/80 text-brand-600">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round"><path d="M22 12H2"/><path
                            d="m15 5-7 7 7 7"/></svg>
                  </span>
                    <span class="text-xs">Transferir</span>
                </button>
                <button
                    class="group flex flex-col items-center gap-2 p-3 rounded-2xl bg-white/15 dark:bg-neutral-800/80 hover:bg-white/25 dark:hover:bg-neutral-800 active:bg-white/15 dark:bg-neutral-800/80 transition focus:outline-none focus:ring-4 focus:ring-white/30">
                  <span class="grid place-items-center size-10 rounded-xl bg-white/80 text-brand-600">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14"
                                                                              rx="2"/><path d="M2 10h20"/></svg>
                  </span>
                    <span class="text-xs">Pagar</span>
                </button>
                <button
                    class="group flex flex-col items-center gap-2 p-3 rounded-2xl bg-white/15 dark:bg-neutral-800/80 hover:bg-white/25 dark:hover:bg-neutral-800 active:bg-white/15 dark:bg-neutral-800/80 transition focus:outline-none focus:ring-4 focus:ring-white/30">
                  <span class="grid place-items-center size-10 rounded-xl bg-white/80 text-brand-600">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round"><path d="M20 7H4"/><path d="M20 11H8"/><path
                            d="M20 15H4"/><path d="M20 19H8"/></svg>
                  </span>
                    <span class="text-xs">Extrato</span>
                </button>
            </div>
        </div>
    </section>

    <!-- Grid Desktop: cards + transações -->
    <section class="mt-6 grid grid-cols-1 md:grid-cols-12 md:gap-6">
        <!-- Cartões → em desktop viram cards menores -->
        <div id="cartoes" class="md:col-span-4 space-y-4 order-2 md:order-1 mt-4 md:mt-0">
            <div
                class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4 shadow-soft dark:shadow-softDark">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium">Cartão físico</p>
                    <button
                        class="text-xs px-2 py-1 rounded-lg border border-brand-200/70 text-brand-700 hover:bg-brand-50 dark:border-brand-800/70 dark:text-brand-300 dark:hover:bg-brand-900/30">
                        Gerenciar
                    </button>
                </div>
                <div
                    class="mt-3 aspect-[16/10] rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 p-4 text-white flex flex-col justify-between">
                    <div class="flex items-center justify-between text-white/90 text-xs">
                        <span>BlueBank • Visa</span>
                        <span>Crédito</span>
                    </div>
                    <p class="text-lg tracking-widest">•••• •••• •••• 2841</p>
                    <div class="flex items-center justify-between text-white/90 text-xs">
                        <span>Marina Duarte</span>
                        <span>12/29</span>
                    </div>
                </div>
            </div>

            <div
                class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4 shadow-soft dark:shadow-softDark">
                <p class="text-sm font-medium">Assinaturas</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                                    <span
                                        class="size-9 grid place-items-center rounded-xl bg-neutral-100 dark:bg-neutral-800"><svg
                                            class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path
                                                d="M3 12h18"/><path d="M12 3v18"/></svg></span>
                            <span>Cloud Storage</span></div>
                        <span class="font-medium">R$ 9,90</span>
                    </li>
                    <li class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                                    <span
                                        class="size-9 grid place-items-center rounded-xl bg-neutral-100 dark:bg-neutral-800"><svg
                                            class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle
                                                cx="12" cy="12" r="9"/></svg></span>
                            <span>Streaming Plus</span></div>
                        <span class="font-medium">R$ 29,90</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Transações (mesma fonte de dados para mobile/desktop) -->
        <div id="transacoes" class="md:col-span-8 order-1 md:order-2">
            <div
                class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4 md:p-5 shadow-soft dark:shadow-softDark">
                <div class="flex items-center justify-between">
                    <p class="font-medium">Transações recentes</p>
                    <div class="flex items-center gap-2">
                        <select
                            class="text-sm rounded-lg border border-neutral-200/70 dark:border-neutral-800/70 bg-transparent px-2 py-1.5">
                            <option>Últimos 7 dias</option>
                            <option>Últimos 30 dias</option>
                            <option>Este mês</option>
                        </select>
                        <button
                            class="text-sm px-2 py-1.5 rounded-lg border border-brand-200/70 text-brand-700 hover:bg-brand-50 dark:border-brand-800/70 dark:text-brand-300 dark:hover:bg-brand-900/30">
                            Exportar
                        </button>
                    </div>
                </div>

                <!-- Lista → no desktop vira tabela responsiva com CSS grid -->
                <ul class="mt-3 divide-y divide-neutral-200/70 dark:divide-neutral-800/70">
                    <!-- item -->
                    <li class="group grid grid-cols-[auto_1fr_auto] items-center gap-3 py-3">
                    <span class="size-10 grid place-items-center rounded-xl bg-neutral-100 dark:bg-neutral-800">
                      <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                           stroke-linecap="round" stroke-linejoin="round"><path d="M20 7H4"/><path d="M20 11H8"/><path
                              d="M20 15H4"/><path d="M20 19H8"/></svg>
                    </span>
                        <div>
                            <p class="text-sm font-medium">Pagamento de boleto</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">02 Set 2025 • 14:20</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-red-600 dark:text-red-400">- R$ 280,00</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">Conta de luz</p>
                        </div>
                    </li>

                    <!-- item -->
                    <li class="group grid grid-cols-[auto_1fr_auto] items-center gap-3 py-3">
                    <span class="size-10 grid place-items-center rounded-xl bg-neutral-100 dark:bg-neutral-800">
                      <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                           stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path
                              d="M16 8V4M8 8V4M16 20v-4M8 20v-4M20 16h4M0 16h4M20 8h4M0 8h4"/></svg>
                    </span>
                        <div>
                            <p class="text-sm font-medium">Pix recebido</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">01 Set 2025 • 18:07</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">+ R$
                                1.250,00</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">Freela UX/UI</p>
                        </div>
                    </li>

                    <!-- item -->
                    <li class="group grid grid-cols-[auto_1fr_auto] items-center gap-3 py-3">
                    <span class="size-10 grid place-items-center rounded-xl bg-neutral-100 dark:bg-neutral-800">
                      <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                           stroke-linecap="round" stroke-linejoin="round"><path
                              d="M20 13V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6"/><rect x="2" y="13" width="20" height="8"
                                                                                  rx="2"/></svg>
                    </span>
                        <div>
                            <p class="text-sm font-medium">Assinatura Streaming</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">28 Ago 2025 • 22:11</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-red-600 dark:text-red-400">- R$ 29,90</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">Plano mensal</p>
                        </div>
                    </li>

                    <!-- item -->
                    <li class="group grid grid-cols-[auto_1fr_auto] items-center gap-3 py-3">
                    <span class="size-10 grid place-items-center rounded-xl bg-neutral-100 dark:bg-neutral-800">
                      <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                           stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-8 0v2"/><circle
                              cx="12" cy="7" r="4"/></svg>
                    </span>
                        <div>
                            <p class="text-sm font-medium">Transferência enviada</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">27 Ago 2025 • 09:33</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-red-600 dark:text-red-400">- R$ 450,00</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">Aluguel</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Perfil (exemplo simples, reutilizado no mobile/desktop) -->
    <section id="perfil" class="mt-6 grid md:grid-cols-12 md:gap-6">
        <div
            class="md:col-span-8 rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4 md:p-5 shadow-soft dark:shadow-softDark">
            <div class="flex items-center gap-4">
                <img src="https://api.dicebear.com/9.x/thumbs/svg?seed=Marina" class="size-14 rounded-2xl"
                     alt="Avatar Marina"/>
                <div>
                    <p class="font-medium">Marina Duarte</p>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Conta • Agência 0001 •
                        ****-2841</p>
                </div>
                <div class="ml-auto">
                    <button
                        class="text-sm px-3 py-2 rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-800">
                        Editar perfil
                    </button>
                </div>
            </div>
        </div>
        <div
            class="md:col-span-4 mt-4 md:mt-0 rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4 shadow-soft dark:shadow-softDark">
            <p class="text-sm font-medium">Segurança</p>
            <ul class="mt-3 space-y-2 text-sm">
                <li class="flex items-center justify-between"><span>2FA</span><span
                        class="px-2 py-0.5 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">Ativo</span>
                </li>
                <li class="flex items-center justify-between"><span>Biometria</span><span
                        class="px-2 py-0.5 rounded-lg bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200">Habilitada</span>
                </li>
            </ul>
        </div>
    </section>

    <!-- Página: Transações (lista + filtros) -->
    <section id="transacoes-page" class="mt-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold">Transações</h2>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">Acompanhe e filtre suas transações por
                    período, tipo e categoria.</p>
            </div>
            <div class="hidden md:flex items-center gap-2">
                <span
                    class="size-10 grid place-items-center rounded-xl bg-brand-50 text-brand-700 dark:bg-neutral-800 dark:text-neutral-100">
                  <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                       stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path
                          d="M17 5H9.5a2.5 2.5 0 0 0 0 5H15a2.5 2.5 0 0 1 0 5H7"/></svg>
                </span>
            </div>
        </div>

        <!-- Filtros -->
        <div
            class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4 md:p-5 shadow-soft dark:shadow-softDark">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="relative block">
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">Início</span>
                    <input type="date"
                           class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2 focus:outline-none focus:ring-4 focus:ring-brand-500/20"/>
                </label>
                <label class="relative block">
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">Fim</span>
                    <input type="date"
                           class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2 focus:outline-none focus:ring-4 focus:ring-brand-500/20"/>
                </label>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                <div
                    class="inline-flex rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-neutral-50 dark:bg-neutral-800 p-1">
                    <button data-chip="all"
                            class="px-3 py-1.5 text-sm rounded-lg bg-white dark:bg-neutral-900 shadow-sm">Todos
                    </button>
                    <button data-chip="in"
                            class="px-3 py-1.5 text-sm rounded-lg hover:bg-white/70 dark:hover:bg-neutral-900/70">
                        Entradas
                    </button>
                    <button data-chip="out"
                            class="px-3 py-1.5 text-sm rounded-lg hover:bg-white/70 dark:hover:bg-neutral-900/70">
                        Despesas
                    </button>
                    <button data-chip="inv"
                            class="px-3 py-1.5 text-sm rounded-lg hover:bg-white/70 dark:hover:bg-neutral-900/70">
                        Investimentos
                    </button>
                </div>
                <button id="btnApplyFilters"
                        class="ml-auto inline-flex items-center gap-2 rounded-xl border border-brand-200/70 text-brand-700 hover:bg-brand-50 dark:border-brand-800/70 dark:text-brand-300 dark:hover:bg-brand-900/30 px-3 py-2">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    Aplicar
                </button>
            </div>
        </div>

        <!-- Listagem -->
        <ul class="mt-4 space-y-3">
            <!-- card -->
            <li class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4 shadow-soft dark:shadow-softDark">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-medium">Condomínio</p>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Despesa • 28 Ago 2025</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-red-600 dark:text-red-400">- R$ 515,00</p>
                        <span
                            class="inline-flex items-center text-[11px] px-2 py-0.5 rounded-full bg-neutral-100 dark:bg-neutral-800">Boleto</span>
                    </div>
                </div>
            </li>
            <li class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4 shadow-soft dark:shadow-softDark">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-medium">teste c t</p>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Entrada • 02 Set 2025</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-emerald-600 dark:text-emerald-400">+ R$ 100,00</p>
                        <span
                            class="inline-flex items-center text-[11px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">Pix</span>
                    </div>
                </div>
            </li>
            <li class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4 shadow-soft dark:shadow-softDark">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-medium">Salário Adriana</p>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Entrada • 27 Ago 2025</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-emerald-600 dark:text-emerald-400">+ R$ 4.500,00</p>
                        <span
                            class="inline-flex items-center text-[11px] px-2 py-0.5 rounded-full bg-neutral-100 dark:bg-neutral-800">Transferência</span>
                    </div>
                </div>
            </li>
        </ul>

        <!-- Modal (form) -->
        <div id="txModal" class="fixed inset-0 z-[60] hidden" role="dialog" aria-modal="true"
             aria-labelledby="txModalTitle">
            <div id="txModalOverlay" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
            <div
                class="absolute inset-x-0 bottom-0 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-[560px]">
                <div
                    class="rounded-t-3xl md:rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 shadow-soft dark:shadow-softDark p-4 md:p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 id="txModalTitle" class="text-lg font-semibold">Nova transação</h3>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">Preencha os dados abaixo e
                                salve.</p>
                        </div>
                        <button id="txClose"
                                class="size-10 grid place-items-center rounded-xl hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                aria-label="Fechar">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 6 6 18"/>
                                <path d="M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form id="txForm" class="mt-4 grid gap-3">
                        <div class="grid grid-cols-2 gap-2">
                            <label class="block">
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">Tipo</span>
                                <div
                                    class="mt-1 inline-flex w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-neutral-50 dark:bg-neutral-800 p-1">
                                    <input type="radio" name="tipo" value="entrada" id="tipoIn"
                                           class="peer/tin hidden" checked>
                                    <label for="tipoIn"
                                           class="flex-1 text-center px-3 py-1.5 rounded-lg bg-white dark:bg-neutral-900 shadow-sm cursor-pointer peer-checked/tin:font-medium">Entrada</label>
                                    <input type="radio" name="tipo" value="despesa" id="tipoOut"
                                           class="peer/tout hidden">
                                    <label for="tipoOut"
                                           class="flex-1 text-center px-3 py-1.5 rounded-lg cursor-pointer hover:bg-white/70 dark:hover:bg-neutral-900/70">Despesa</label>
                                </div>
                            </label>
                            <label class="block">
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">Categoria</span>
                                <select
                                    class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2">
                                    <option>Salário</option>
                                    <option>Aluguel</option>
                                    <option>Mercado</option>
                                    <option>Transporte</option>
                                </select>
                            </label>
                        </div>

                        <label class="block">
                            <span class="text-xs text-neutral-500 dark:text-neutral-400">Descrição</span>
                            <input type="text" placeholder="Ex: Pagamento de projeto"
                                   class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"/>
                        </label>

                        <div class="grid grid-cols-2 gap-2">
                            <label class="block">
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">Valor</span>
                                <input type="number" step="0.01" placeholder="0,00"
                                       class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"/>
                            </label>
                            <label class="block">
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">Data</span>
                                <input type="date"
                                       class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"/>
                            </label>
                        </div>

                        <label class="block">
                            <span class="text-xs text-neutral-500 dark:text-neutral-400">Método</span>
                            <div
                                class="mt-1 inline-flex rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-neutral-50 dark:bg-neutral-800 p-1">
                                <button type="button" data-method
                                        class="px-3 py-1.5 text-sm rounded-lg bg-white dark:bg-neutral-900 shadow-sm">
                                    Pix
                                </button>
                                <button type="button" data-method
                                        class="px-3 py-1.5 text-sm rounded-lg hover:bg-white/70 dark:hover:bg-neutral-900/70">
                                    Cartão
                                </button>
                                <button type="button" data-method
                                        class="px-3 py-1.5 text-sm rounded-lg hover:bg-white/70 dark:hover:bg-neutral-900/70">
                                    Dinheiro
                                </button>
                            </div>
                        </label>

                        <label class="block">
                            <span class="text-xs text-neutral-500 dark:text-neutral-400">Observações</span>
                            <textarea rows="3"
                                      class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"
                                      placeholder="Detalhes adicionais"></textarea>
                        </label>

                        <div class="mt-2 flex items-center justify-end gap-2">
                            <button type="button" id="txCancel"
                                    class="px-3 py-2 rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white shadow-soft">
                                Salvar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Página: Categorias -->
    <section id="categorias-page" class="mt-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold">Categorias</h2>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">Gerencie categorias por tipo e defina
                    um limite para cada uma.</p>
            </div>
            <div class="hidden md:flex items-center gap-2">
                <button data-open-modal="cat"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white shadow-soft">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Nova categoria
                </button>
            </div>
        </div>

        <!-- Listagem de categorias -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
            <!-- item -->
            <article
                class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4 shadow-soft dark:shadow-softDark flex items-center gap-3">
                <span
                    class="size-12 grid place-items-center rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400">
                  <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                       stroke-linecap="round" stroke-linejoin="round"><path d="M20 7H4"/><path d="M20 11H8"/><path
                          d="M20 15H4"/><path d="M20 19H8"/></svg>
                </span>
                <div class="flex-1">
                    <p class="font-medium">Mercado</p>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Despesa</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Limite</p>
                    <p class="font-semibold">R$ 800,00</p>
                </div>
            </article>

            <article
                class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4 shadow-soft dark:shadow-softDark flex items-center gap-3">
                <span
                    class="size-12 grid place-items-center rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                  <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                       stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path
                          d="M17 5H9.5a2.5 2.5 0 0 0 0 5H15a2.5 2.5 0 0 1 0 5H7"/></svg>
                </span>
                <div class="flex-1">
                    <p class="font-medium">Salário</p>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Entrada</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Limite</p>
                    <p class="font-semibold">R$ 5.000,00</p>
                </div>
            </article>

            <article
                class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4 shadow-soft dark:shadow-softDark flex items-center gap-3">
                <span
                    class="size-12 grid place-items-center rounded-xl bg-blue-500/15 text-blue-600 dark:text-blue-400">
                  <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                       stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path
                          d="M19 15l-5-5-4 4-3-3"/></svg>
                </span>
                <div class="flex-1">
                    <p class="font-medium">Investimentos</p>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Investimento</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Limite</p>
                    <p class="font-semibold">R$ 1.500,00</p>
                </div>
            </article>

            <article
                class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4 shadow-soft dark:shadow-softDark flex items-center gap-3">
                <span
                    class="size-12 grid place-items-center rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400">
                  <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                       stroke-linecap="round" stroke-linejoin="round"><path d="M3 13h18"/><path
                          d="M5 21h14a2 2 0 0 0 2-2v-6H3v6a2 2 0 0 0 2 2Z"/><path d="M14 13V5a2 2 0 1 0-4 0v8"/></svg>
                </span>
                <div class="flex-1">
                    <p class="font-medium">Transporte</p>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Despesa</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Limite</p>
                    <p class="font-semibold">R$ 400,00</p>
                </div>
            </article>
        </div>

        <!-- FAB específico desta página (mobile) -->
        <button id="catFab" data-open-modal="cat"
                class="md:hidden fixed bottom-20 right-4 size-14 rounded-2xl grid place-items-center text-white shadow-lg bg-emerald-600 hover:bg-emerald-700 active:scale-95 transition hidden"
                aria-label="Nova categoria">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
        </button>

        <!-- Modal: Nova categoria -->
        <div id="catModal" class="fixed inset-0 z-[60] hidden" role="dialog" aria-modal="true"
             aria-labelledby="catModalTitle">
            <div id="catOverlay" data-overlay class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
            <div
                class="absolute inset-x-0 bottom-0 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-[560px]">
                <div
                    class="rounded-t-3xl md:rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 shadow-soft dark:shadow-softDark p-4 md:p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 id="catModalTitle" class="text-lg font-semibold">Nova categoria</h3>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">Defina título, tipo,
                                ícone, cor e limite.</p>
                        </div>
                        <button id="catClose" data-close
                                class="size-10 grid place-items-center rounded-xl hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                aria-label="Fechar">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 6 6 18"/>
                                <path d="M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form id="catForm" class="mt-4 grid gap-3">
                        <label class="block">
                            <span class="text-xs text-neutral-500 dark:text-neutral-400">Título</span>
                            <input type="text" placeholder="Ex: Mercado"
                                   class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"
                                   required/>
                        </label>

                        <label class="block">
                            <span class="text-xs text-neutral-500 dark:text-neutral-400">Tipo</span>
                            <div
                                class="mt-1 inline-flex w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-neutral-50 dark:bg-neutral-800 p-1">
                                <input type="radio" name="tipoCat" value="entrada" id="catIn"
                                       class="peer/catin hidden" checked>
                                <label for="catIn"
                                       class="flex-1 text-center px-3 py-1.5 rounded-lg bg-white dark:bg-neutral-900 shadow-sm cursor-pointer peer-checked/catin:font-medium">Entrada</label>
                                <input type="radio" name="tipoCat" value="despesa" id="catOut"
                                       class="peer/catout hidden">
                                <label for="catOut"
                                       class="flex-1 text-center px-3 py-1.5 rounded-lg cursor-pointer hover:bg-white/70 dark:hover:bg-neutral-900/70">Despesa</label>
                                <input type="radio" name="tipoCat" value="invest" id="catInv"
                                       class="peer/catinv hidden">
                                <label for="catInv"
                                       class="flex-1 text-center px-3 py-1.5 rounded-lg cursor-pointer hover:bg-white/70 dark:hover:bg-neutral-900/70">Investimento</label>
                            </div>
                        </label>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <label class="block">
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">Ícone</span>
                                <div class="mt-2 grid grid-cols-6 gap-2">
                                    <!-- radios de ícones -->
                                    <input type="radio" name="icon" id="i-bag" class="peer hidden" checked>
                                    <label for="i-bag"
                                           class="group grid place-items-center aspect-square rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-800 peer-checked:ring-2 peer-checked:ring-brand-500">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                             stroke-linejoin="round">
                                            <path d="M3 13h18"/>
                                            <path d="M5 21h14a2 2 0 0 0 2-2v-6H3v6a2 2 0 0 0 2 2Z"/>
                                            <path d="M14 13V5a2 2 0 1 0-4 0v8"/>
                                        </svg>
                                    </label>
                                    <input type="radio" name="icon" id="i-home" class="peer hidden">
                                    <label for="i-home"
                                           class="group grid place-items-center aspect-square rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-800 peer-checked:ring-2 peer-checked:ring-brand-500">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                             stroke-linejoin="round">
                                            <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Z"/>
                                            <path d="M9 22V12h6v10"/>
                                        </svg>
                                    </label>
                                    <input type="radio" name="icon" id="i-car" class="peer hidden">
                                    <label for="i-car"
                                           class="group grid place-items-center aspect-square rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-800 peer-checked:ring-2 peer-checked:ring-brand-500">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                             stroke-linejoin="round">
                                            <path d="M3 13l2-5a3 3 0 0 1 3-2h8a3 3 0 0 1 3 2l2 5"/>
                                            <circle cx="7.5" cy="17.5" r="1.5"/>
                                            <circle cx="16.5" cy="17.5" r="1.5"/>
                                        </svg>
                                    </label>
                                    <input type="radio" name="icon" id="i-chart" class="peer hidden">
                                    <label for="i-chart"
                                           class="group grid place-items-center aspect-square rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-800 peer-checked:ring-2 peer-checked:ring-brand-500">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                             stroke-linejoin="round">
                                            <path d="M3 3v18h18"/>
                                            <path d="M19 15l-5-5-4 4-3-3"/>
                                        </svg>
                                    </label>
                                    <input type="radio" name="icon" id="i-pig" class="peer hidden">
                                    <label for="i-pig"
                                           class="group grid place-items-center aspect-square rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-800 peer-checked:ring-2 peer-checked:ring-brand-500">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                             stroke-linejoin="round">
                                            <path
                                                d="M5 11a7 6 0 1 0 14 0 5 4 0 0 0-5-4h-1l-1-2-2 2H8a5 4 0 0 0-3 4Z"/>
                                            <path d="M7 11h3"/>
                                            <circle cx="17" cy="11" r="1"/>
                                        </svg>
                                    </label>
                                    <input type="radio" name="icon" id="i-cafe" class="peer hidden">
                                    <label for="i-cafe"
                                           class="group grid place-items-center aspect-square rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-800 peer-checked:ring-2 peer-checked:ring-brand-500">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                             stroke-linejoin="round">
                                            <path d="M3 8h13v6a5 5 0 0 1-5 5H8a5 5 0 0 1-5-5V8Z"/>
                                            <path d="M16 8h3a2 2 0 0 1 0 4h-3"/>
                                            <path d="M6 1v3M10 1v3M14 1v3"/>
                                        </svg>
                                    </label>
                                </div>
                            </label>

                            <label class="block">
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">Cor</span>
                                <div class="mt-2 flex items-center gap-2">
                                    <input type="color" value="#3b82f6"
                                           class="h-10 w-16 rounded-lg border border-neutral-200/70 dark:border-neutral-800/70 bg-transparent"/>
                                    <div class="flex items-center gap-1">
                                        <button type="button" data-color="#3b82f6"
                                                class="size-8 rounded-full bg-brand-500 ring-2 ring-transparent"></button>
                                        <button type="button" data-color="#10b981"
                                                class="size-8 rounded-full bg-emerald-500 ring-2 ring-transparent"></button>
                                        <button type="button" data-color="#f59e0b"
                                                class="size-8 rounded-full bg-amber-500 ring-2 ring-transparent"></button>
                                        <button type="button" data-color="#ef4444"
                                                class="size-8 rounded-full bg-rose-500 ring-2 ring-transparent"></button>
                                        <button type="button" data-color="#8b5cf6"
                                                class="size-8 rounded-full bg-violet-500 ring-2 ring-transparent"></button>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <label class="block">
                            <span class="text-xs text-neutral-500 dark:text-neutral-400">Limite (R$)</span>
                            <input type="number" step="0.01" placeholder="0,00"
                                   class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"
                                   required/>
                        </label>

                        <div class="mt-2 flex items-center justify-end gap-2">
                            <button type="button" data-cancel
                                    class="px-3 py-2 rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white shadow-soft">
                                Salvar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Página: Contas Bancárias -->
    <section id="contas-page" class="mt-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold">Contas bancárias</h2>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">Acompanhe saldos por banco e acesse o
                    extrato de cada conta.</p>
            </div>
            <div class="hidden md:flex items-center gap-2">
                <button data-open-modal="acc"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white shadow-soft">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Nova conta
                </button>
            </div>
        </div>

        <!-- Listagem: cards maiores -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- item -->
            <article
                class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-5 shadow-soft dark:shadow-softDark">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                    <span
                        class="size-12 grid place-items-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-white shadow-soft">
                      <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                           stroke-linecap="round" stroke-linejoin="round"><path
                              d="M3 10l9-6 9 6v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-8Z"/><path d="M9 22V12h6v10"/></svg>
                    </span>
                        <div>
                            <p class="font-semibold">Banco do Norte</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">Conta corrente</p>
                        </div>
                    </div>
                    <button
                        class="inline-flex items-center gap-2 text-sm px-3 py-1.5 rounded-lg border border-brand-200/70 text-brand-700 hover:bg-brand-50 dark:border-brand-800/70 dark:text-brand-300 dark:hover:bg-brand-900/30"
                        onclick="location.hash='#transacoes-page'">Ver extrato
                    </button>
                </div>

                <!-- Total destacado -->
                <div class="mt-4">
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Saldo total</p>
                    <p class="text-3xl font-semibold tracking-tight">R$ 8.750,00</p>
                </div>

                <!-- Breakdown -->
                <div class="mt-3 grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 p-3">
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Em conta</p>
                        <p class="text-lg font-medium">R$ 6.200,00</p>
                    </div>
                    <div class="rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 p-3">
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Cofrinhos</p>
                        <p class="text-lg font-medium">R$ 2.550,00</p>
                    </div>
                </div>
            </article>

            <!-- item -->
            <article
                class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-5 shadow-soft dark:shadow-softDark">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                    <span
                        class="size-12 grid place-items-center rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-soft">
                      <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                           stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path
                              d="M17 5H9.5a2.5 2.5 0 0 0 0 5H15a2.5 2.5 0 0 1 0 5H7"/></svg>
                    </span>
                        <div>
                            <p class="font-semibold">Banco Aurora</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">Poupança</p>
                        </div>
                    </div>
                    <button
                        class="inline-flex items-center gap-2 text-sm px-3 py-1.5 rounded-lg border border-brand-200/70 text-brand-700 hover:bg-brand-50 dark:border-brand-800/70 dark:text-brand-300 dark:hover:bg-brand-900/30"
                        onclick="location.hash='#transacoes-page'">Ver extrato
                    </button>
                </div>
                <div class="mt-4">
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Saldo total</p>
                    <p class="text-3xl font-semibold tracking-tight">R$ 15.420,00</p>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 p-3">
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Em conta</p>
                        <p class="text-lg font-medium">R$ 9.900,00</p>
                    </div>
                    <div class="rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 p-3">
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Cofrinhos</p>
                        <p class="text-lg font-medium">R$ 5.520,00</p>
                    </div>
                </div>
            </article>

            <!-- item -->
            <article
                class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-5 shadow-soft dark:shadow-softDark">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                    <span
                        class="size-12 grid place-items-center rounded-xl bg-gradient-to-br from-violet-400 to-violet-600 text-white shadow-soft">
                      <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                           stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v6H3z"/><path
                              d="M5 9v11h14V9"/></svg>
                    </span>
                        <div>
                            <p class="font-semibold">Banco Solar</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">Investimento</p>
                        </div>
                    </div>
                    <button
                        class="inline-flex items-center gap-2 text-sm px-3 py-1.5 rounded-lg border border-brand-200/70 text-brand-700 hover:bg-brand-50 dark:border-brand-800/70 dark:text-brand-300 dark:hover:bg-brand-900/30"
                        onclick="location.hash='#transacoes-page'">Ver extrato
                    </button>
                </div>
                <div class="mt-4">
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Saldo total</p>
                    <p class="text-3xl font-semibold tracking-tight">R$ 21.300,00</p>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 p-3">
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Em conta</p>
                        <p class="text-lg font-medium">R$ 12.000,00</p>
                    </div>
                    <div class="rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 p-3">
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Cofrinhos</p>
                        <p class="text-lg font-medium">R$ 9.300,00</p>
                    </div>
                </div>
            </article>
        </div>

        <!-- FAB desta página (mobile) -->
        <button id="accFab" data-open-modal="acc"
                class="md:hidden fixed bottom-20 right-4 size-14 rounded-2xl grid place-items-center text-white shadow-lg bg-brand-600 hover:bg-brand-700 active:scale-95 transition hidden"
                aria-label="Nova conta">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
        </button>

        <!-- Modal: Nova conta -->
        <div id="accModal" class="fixed inset-0 z-[60] hidden" role="dialog" aria-modal="true"
             aria-labelledby="accModalTitle">
            <div id="accOverlay" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
            <div
                class="absolute inset-x-0 bottom-0 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-[560px]">
                <div
                    class="rounded-t-3xl md:rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 shadow-soft dark:shadow-softDark p-4 md:p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 id="accModalTitle" class="text-lg font-semibold">Nova conta bancária</h3>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">Informe os detalhes da
                                conta para adicioná-la.</p>
                        </div>
                        <button id="accClose"
                                class="size-10 grid place-items-center rounded-xl hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                aria-label="Fechar">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 6 6 18"/>
                                <path d="M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form id="accForm" class="mt-4 grid gap-3">
                        <label class="block">
                            <span class="text-xs text-neutral-500 dark:text-neutral-400">Nome do banco</span>
                            <input type="text" placeholder="Ex: Banco do Norte"
                                   class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"
                                   required/>
                        </label>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <label class="block">
                                        <span
                                            class="text-xs text-neutral-500 dark:text-neutral-400">Valor em conta (R$)</span>
                                <input type="number" step="0.01" placeholder="0,00"
                                       class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"
                                       required/>
                            </label>
                            <label class="block">
                                        <span
                                            class="text-xs text-neutral-500 dark:text-neutral-400">Tipo de conta</span>
                                <div
                                    class="mt-1 inline-flex w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-neutral-50 dark:bg-neutral-800 p-1">
                                    <input type="radio" name="tipoConta" value="corrente" id="accCorr"
                                           class="peer/acc1 hidden" checked>
                                    <label for="accCorr"
                                           class="flex-1 text-center px-3 py-1.5 rounded-lg bg-white dark:bg-neutral-900 shadow-sm cursor-pointer peer-checked/acc1:font-medium">Corrente</label>
                                    <input type="radio" name="tipoConta" value="poupanca" id="accPoup"
                                           class="peer/acc2 hidden">
                                    <label for="accPoup"
                                           class="flex-1 text-center px-3 py-1.5 rounded-lg cursor-pointer hover:bg-white/70 dark:hover:bg-neutral-900/70">Poupança</label>
                                    <input type="radio" name="tipoConta" value="investimento" id="accInv"
                                           class="peer/acc3 hidden">
                                    <label for="accInv"
                                           class="flex-1 text-center px-3 py-1.5 rounded-lg cursor-pointer hover:bg-white/70 dark:hover:bg-neutral-900/70">Investimento</label>
                                </div>
                            </label>
                        </div>

                        <div class="mt-2 flex items-center justify-end gap-2">
                            <button type="button" id="accCancel"
                                    class="px-3 py-2 rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white shadow-soft">
                                Salvar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Página: Projeções -->
    <section id="projecoes-page" class="mt-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold">Projeções</h2>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">Defina um período e visualize o
                    cenário projetado mês a mês.</p>
            </div>
        </div>

        <!-- Filtros -->
        <div
            class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4 md:p-5 shadow-soft dark:shadow-softDark">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="block">
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">Início</span>
                    <input type="date"
                           class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2 focus:outline-none focus:ring-4 focus:ring-brand-500/20"/>
                </label>
                <label class="block">
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">Fim</span>
                    <input type="date"
                           class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2 focus:outline-none focus:ring-4 focus:ring-brand-500/20"/>
                </label>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <div
                    class="inline-flex rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-neutral-50 dark:bg-neutral-800 p-1">
                    <button
                        class="px-3 py-1.5 text-sm rounded-lg hover:bg-white/70 dark:hover:bg-neutral-900/70">+1
                        mês
                    </button>
                    <button class="px-3 py-1.5 text-sm rounded-lg bg-white dark:bg-neutral-900 shadow-sm">+3
                        meses
                    </button>
                    <button
                        class="px-3 py-1.5 text-sm rounded-lg hover:bg-white/70 dark:hover:bg-neutral-900/70">+6
                        meses
                    </button>
                    <button
                        class="px-3 py-1.5 text-sm rounded-lg hover:bg-white/70 dark:hover:bg-neutral-900/70">
                        +12 meses
                    </button>
                    <button
                        class="px-3 py-1.5 text-sm rounded-lg hover:bg-white/70 dark:hover:bg-neutral-900/70">
                        +15 meses
                    </button>
                </div>
                <button
                    class="ml-auto inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 shadow-soft">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    Fazer projeção
                </button>
            </div>
        </div>

        <!-- Resumo do período -->
        <div
            class="mt-4 rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4 md:p-5 shadow-soft dark:shadow-softDark">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 p-3">
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Saldo em contas</p>
                    <p class="text-lg font-semibold text-emerald-600 dark:text-emerald-400">R$ 3.450,00</p>
                </div>
                <div class="rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 p-3">
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Total entradas</p>
                    <p class="text-lg font-semibold text-emerald-600 dark:text-emerald-400">R$ 16.652,00</p>
                </div>
                <div class="rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 p-3">
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Total saídas</p>
                    <p class="text-lg font-semibold text-rose-600 dark:text-rose-400">R$ 21.833,12</p>
                </div>
                <div class="rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 p-3">
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Saldo fim da projeção</p>
                    <p class="text-lg font-semibold text-rose-600 dark:text-rose-400">- R$ 5.031,12</p>
                </div>
            </div>
        </div>

        <!-- Cartões mensais -->
        <div class="mt-4 space-y-4">
            <!-- Mês -->
            <article
                class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 shadow-soft dark:shadow-softDark">
                <header
                    class="flex items-center justify-between p-4 md:p-5 border-b border-neutral-200/70 dark:border-neutral-800/70">
                    <h3 class="font-semibold">Setembro 2025</h3>
                    <div class="text-sm flex items-center gap-4">
                                <span
                                    class="text-emerald-600 dark:text-emerald-400 font-medium">Entradas: R$ 6.884,00</span>
                        <span class="text-rose-600 dark:text-rose-400 font-medium">Saídas: R$ 9.154,89</span>
                        <span class="font-semibold">Saldo mês: <span class="text-rose-600 dark:text-rose-400">- R$ 2.270,89</span></span>
                    </div>
                </header>

                <!-- Lista diária -->
                <ul class="divide-y divide-neutral-200/70 dark:divide-neutral-800/70">
                    <li class="grid grid-cols-[auto_1fr_auto] items-center gap-3 p-4">
                                <span
                                    class="size-10 grid place-items-center rounded-xl bg-neutral-100 dark:bg-neutral-800"><svg
                                        class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path
                                            d="M12 1v22"/><path d="M17 5H9.5a2.5 2.5 0 0 0 0 5H15a2.5 2.5 0 0 1 0 5H7"/></svg></span>
                        <div>
                            <p class="font-medium">Salário</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">05/09/2025</p>
                        </div>
                        <p class="font-semibold text-emerald-600 dark:text-emerald-400">R$ 2.215,00</p>
                    </li>
                    <li class="grid grid-cols-[auto_1fr_auto] items-center gap-3 p-4">
                                <span
                                    class="size-10 grid place-items-center rounded-xl bg-neutral-100 dark:bg-neutral-800"><svg
                                        class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path
                                            d="M22 12H2"/></svg></span>
                        <div>
                            <p class="font-medium">Bilhete único</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">05/09/2025</p>
                        </div>
                        <p class="font-semibold text-rose-600 dark:text-rose-400">- R$ 75,00</p>
                    </li>
                    <li class="grid grid-cols-[auto_1fr_auto] items-center gap-3 p-4">
                                <span
                                    class="size-10 grid place-items-center rounded-xl bg-neutral-100 dark:bg-neutral-800"><svg
                                        class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2"
                                                                                                              y="5"
                                                                                                              width="20"
                                                                                                              height="14"
                                                                                                              rx="2"/><path
                                            d="M2 10h20"/></svg></span>
                        <div>
                            <p class="font-medium">Assinatura Streaming</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">06/09/2025</p>
                        </div>
                        <p class="font-semibold text-rose-600 dark:text-rose-400">- R$ 29,90</p>
                    </li>
                </ul>
            </article>

            <!-- Outro mês -->
            <article
                class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 shadow-soft dark:shadow-softDark">
                <header
                    class="flex items-center justify-between p-4 md:p-5 border-b border-neutral-200/70 dark:border-neutral-800/70">
                    <h3 class="font-semibold">Outubro 2025</h3>
                    <div class="text-sm flex items-center gap-4">
                                <span
                                    class="text-emerald-600 dark:text-emerald-400 font-medium">Entradas: R$ 8.200,00</span>
                        <span class="text-rose-600 dark:text-rose-400 font-medium">Saídas: R$ 7.980,00</span>
                        <span class="font-semibold">Saldo mês: <span
                                class="text-emerald-600 dark:text-emerald-400">R$ 220,00</span></span>
                    </div>
                </header>
                <ul class="divide-y divide-neutral-200/70 dark:divide-neutral-800/70">
                    <li class="grid grid-cols-[auto_1fr_auto] items-center gap-3 p-4">
                                <span
                                    class="size-10 grid place-items-center rounded-xl bg-neutral-100 dark:bg-neutral-800"><svg
                                        class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path
                                            d="M12 5v14M5 12h14"/></svg></span>
                        <div>
                            <p class="font-medium">Freela UX/UI</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">04/10/2025</p>
                        </div>
                        <p class="font-semibold text-emerald-600 dark:text-emerald-400">R$ 1.600,00</p>
                    </li>
                    <li class="grid grid-cols-[auto_1fr_auto] items-center gap-3 p-4">
                                <span
                                    class="size-10 grid place-items-center rounded-xl bg-neutral-100 dark:bg-neutral-800"><svg
                                        class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path
                                            d="M20 7H4"/><path d="M20 11H8"/><path d="M20 15H4"/><path
                                            d="M20 19H8"/></svg></span>
                        <div>
                            <p class="font-medium">Pagamento de boleto</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">07/10/2025</p>
                        </div>
                        <p class="font-semibold text-rose-600 dark:text-rose-400">- R$ 320,00</p>
                    </li>
                    <li class="grid grid-cols-[auto_1fr_auto] items-center gap-3 p-4">
                                <span
                                    class="size-10 grid place-items-center rounded-xl bg-neutral-100 dark:bg-neutral-800"><svg
                                        class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path
                                            d="M16 21v-2a4 4 0 0 0-8 0v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                        <div>
                            <p class="font-medium">Transferência recebida</p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">10/10/2025</p>
                        </div>
                        <p class="font-semibold text-emerald-600 dark:text-emerald-400">R$ 350,00</p>
                    </li>
                </ul>
            </article>
        </div>
    </section>
@endsection
