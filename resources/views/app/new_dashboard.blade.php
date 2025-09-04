@extends('layouts.templates.new_app')

@section('content')
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    @endpush

    <div class="header">
        <form id="monthForm" class="d-flex align-items-center justify-content-between w-100 mb-3">
            <button type="button" class="btn btn-light rounded-circle shadow-sm px-3" onclick="changeMonth(-1)">
                <i class="fa fa-chevron-left"></i>
            </button>

            <input type="month" name="month" id="monthPicker"
                   class="form-control text-center fw-bold mx-2"
                   value="{{ $startOfMonth->format('Y-m') }}"
                   style="max-width:250px;flex:1;">

            <button type="button" class="btn btn-light rounded-circle shadow-sm px-3" onclick="changeMonth(1)">
                <i class="fa fa-chevron-right"></i>
            </button>
        </form>
    </div>

    <!-- Hero/Saldo -->
    <section id="saldo" class="mt-4 md:mt-0">
        <div class="relative overflow-hidden rounded-2xl p-5 md:p-6 bg-gradient-to-br from-brand-400 to-brand-600 dark:from-neutral-900 dark:to-neutral-800 text-white shadow-soft dark:shadow-softDark">
            <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full bg-white/15 dark:bg-neutral-800/80 blur-2xl"></div>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-white/80 text-sm">Saldo total</p>
                    <div class="shimmer is-loading shimmer--xl">
                    <p id="kpi-balanco" class="mt-1 text-3xl md:text-4xl font-semibold tracking-tight" aria-live="polite"></p>
                    </div>
                </div>
                <div class="grid gap-2 text-right">
                    <p class="text-xs/none text-white/80">Última atualização agora</p>
                    <button
                        class="self-end inline-flex items-center gap-2 bg-white/15 hover:bg-white/25 active:bg-white/15 dark:bg-neutral-800/80 transition px-3 py-1.5 rounded-xl backdrop-blur-md">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 12a9 9 0 1 1-9-9"/>
                            <path d="M21 3v6h-6"/>
                        </svg>
                        Atualizar
                    </button>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 text-sm/5">
                <div class="rounded-xl bg-white/10 p-3">
                    <p class="text-white/80">Contas</p>
                    <p class="font-medium" id="kpi-contas"></p>
                </div>
                <div class="rounded-xl bg-white/10 p-3">
                    <p class="text-white/80">Entradas</p>
                    <p class="font-medium" id="kpi-receber"></p>
                </div>
                <div class="rounded-xl bg-white/10 p-3">
                    <p class="text-white/80">Saídas</p>
                    <p class="font-medium" id="kpi-pagar"></p>
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
                <path d="M0 50 C 40 20, 80 70, 120 40 S 200 20, 240 45 S 320 60, 360 35 S 400 70, 400 70" stroke="white"
                      stroke-width="2" fill="url(#grad)"></path>
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

    <section class="mt-6 grid grid-cols-1 md:grid-cols-12 md:gap-6">
        <div id="" class="md:col-span-4 space-y-4 order-2 md:order-1 mt-4 md:mt-0">
            <div class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4 shadow-soft dark:shadow-softDark">
                <div class="">
                    <div id="calendar"></div>
                </div>

                <div class="py-3" id="calendar-results"></div>
            </div>
        </div>

    </section>

    <!-- Grid Desktop: cards + transações -->
    <section class="mt-6 grid grid-cols-1 md:grid-cols-12 md:gap-6">
        <!-- Cartões → em desktop viram cards menores -->
        <div id="cartoes" class="md:col-span-4 space-y-4 order-2 md:order-1 mt-4 md:mt-0">
            <div class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4 shadow-soft dark:shadow-softDark">
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
                            <span class="size-9 grid place-items-center rounded-xl bg-neutral-100 dark:bg-neutral-800"><svg
                                    class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18"/><path
                                        d="M12 3v18"/></svg></span>
                            <span>Cloud Storage</span></div>
                        <span class="font-medium">R$ 9,90</span>
                    </li>
                    <li class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="size-9 grid place-items-center rounded-xl bg-neutral-100 dark:bg-neutral-800"><svg
                                    class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12"
                                                                                                            cy="12"
                                                                                                            r="9"/></svg></span>
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
                            <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">+ R$ 1.250,00</p>
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
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Conta • Agência 0001 • ****-2841</p>
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
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
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
                                    <input type="radio" name="tipo" value="entrada" id="tipoIn" class="peer/tin hidden"
                                           checked>
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

    <script>
        function startLoading(...ids){
            ids.forEach(id=>{
                document.getElementById(id)?.closest('.shimmer')?.classList.add('is-loading');
                document.getElementById(id)?.closest('.shimmer')?.classList.remove('is-loaded');
            });
        }
        function finishLoading(...ids){
            ids.forEach(id=>{
                document.getElementById(id)?.closest('.shimmer')?.classList.remove('is-loading');
                document.getElementById(id)?.closest('.shimmer')?.classList.add('is-loaded');
            });
        }
    </script>

    <script>
        // === Pagar TRANSAÇÃO (abre modal) ===
        const PAY_TPL = @json(route('transaction-payment', ['transaction' => '__ID__']));
        const paymentModal = document.getElementById('paymentModal');
        const paymentForm = document.getElementById('paymentForm');
        const inAmount = document.getElementById('payment_amount');
        const inDate = document.getElementById('payment_date');

        let CURRENT_TX_CARD = null;

        function showPaymentModal() {
            paymentModal.classList.add('show');
        }

        function hidePaymentModal() {
            paymentModal.classList.remove('show');
        }


        // parser BR/EN robusto
        function parseMoneyBR(input) {
            if (typeof input === 'number') return input;
            let s = String(input || '').trim();
            s = s.replace(/[^\d.,-]/g, '');
            const lastComma = s.lastIndexOf(','), lastDot = s.lastIndexOf('.');
            if (lastComma > -1 && lastDot > -1) {
                if (lastComma > lastDot) s = s.replace(/\./g, '').replace(',', '.'); // 1.234,56
                else s = s.replace(/,/g, '');                                       // 1,234.56
            } else if (lastComma > -1) {
                s = s.replace(',', '.');
            }
            return Number(s || 0);
        }

        const formatBRL = v => Number(v || 0).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});

        // abrir modal com defaults
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-open-payment]');
            if (!btn) return;

            window.CURRENT_ID = btn.dataset.id;
            window.CURRENT_TITLE = btn.dataset.title || 'Pagamento';
            window.CURRENT_DUE_DATE = (btn.dataset.date || '').slice(0, 10);
            CURRENT_TX_CARD = btn.closest('.transaction-card') || null;

            paymentForm.action = PAY_TPL.replace('__ID__', window.CURRENT_ID);
            inAmount.value = btn.dataset.amount || '0';
            inDate.value = window.CURRENT_DUE_DATE;

            showPaymentModal();
        });

        function renderBreakdown(elId, k, root, label = 'atrasados') {
            const el = document.getElementById(elId);
            if (!el) return;
            const mes   = k[`${root}_mes_brl`];
            const extra = k[`${root}_atrasados_brl`]; // mantemos o campo; só muda o texto

            if (typeof mes === 'string' && typeof extra === 'string') {
                el.innerHTML =
                    `${mes}<span class="price-default mx-1"><span class="late"> + ${extra} ${label}</span></span>`;
            } else {
                const tot = k[`${root}_brl`];
                if (typeof tot === 'string') el.textContent = tot;
            }
        }

        function renderSaldoCofrinhos(elId, contasBRL, cofrinhosBRL){
            const el = document.getElementById(elId);
            if (!el) return;

            const contas = (typeof contasBRL === 'string') ? contasBRL : '';
            const cofr   = (typeof cofrinhosBRL === 'string') ? cofrinhosBRL : '';

            // se não veio o breakdown, mostra só o saldo das contas
            el.innerHTML = cofr
                ? `${contas}<span class="price-default mx-1"><span class="late"> +  ${cofr} cofrinhos</span></span>`
                : contas;
        }

        function applyKpis(k){
            const $ = id => document.getElementById(id);

            // antes: if (k.accountsBalance_brl) $('kpi-contas').textContent = k.accountsBalance_brl;
            renderSaldoCofrinhos('kpi-contas', k.accountsBalance_brl, k.savingsBalance_brl);

            if (k.savingsBalance_brl) $('kpi-cofrinhos').textContent = k.savingsBalance_brl; // só se não escondeu esse bloco
            renderBreakdown('kpi-receber', k, 'aReceber', 'pendentes');
            renderBreakdown('kpi-pagar',   k, 'aPagar',   'atrasados');
            $('kpi-balanco').textContent = (k.saldoPrevisto_brl || k.saldoMes_brl || k.saldoReal_brl || '');
        }

        // submit do modal
        paymentForm?.addEventListener('submit', async (e) => {
            e.preventDefault();

            const fd = new FormData(paymentForm);
            // envia o mês visível na UI para o controller recalcular os KPIs do mesmo período
            const currentMonth = document.getElementById('monthPicker')?.value || '';
            if (currentMonth) fd.set('month', currentMonth);

            try {
                const resp = await fetch(paymentForm.action, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': "{{ csrf_token() }}", 'Accept': 'application/json'},
                    body: fd,
                    credentials: 'same-origin'
                });

                if (resp.status === 422) {
                    const j = await resp.json();
                    throw new Error(Object.values(j.errors || {})[0]?.[0] || 'Dados inválidos.');
                }
                if (!resp.ok) throw new Error(await resp.text());


                // datas
                const payDate = (inDate.value || '').slice(0, 10);
                const dueDate = (window.CURRENT_DUE_DATE || payDate);
                const payAmount = parseMoneyBR(inAmount.value);

                // tenta aplicar KPIs vindos do controller; se não vier JSON, busca do endpoint
                let data = null;
                try {
                    data = await resp.json();
                } catch (_) {
                }
                if (data && data.ok) {
                    applyKpis(data);
                } else if (currentMonth) {
                    const r = await fetch(`{{ route('dashboard.kpis') }}?month=${currentMonth}`, {headers: {Accept: 'application/json'}});
                    if (r.ok) applyKpis(await r.json());
                }

                hidePaymentModal();

                // remove card "Próximos pagamentos"
                if (CURRENT_TX_CARD) {
                    CURRENT_TX_CARD.remove();
                    CURRENT_TX_CARD = null;
                }
                paymentForm.reset();

                // Atualiza o calendário em memória (remove ponto vermelho do vencimento e cria azul no dia do pagamento)
                const cal = window.__cal;
                if (cal && cal.eventosCache) {
                    const mapDue = cal.eventosCache[dueDate];
                    if (mapDue) {
                        for (const [k, ev] of mapDue.entries()) {
                            if (ev.tx_id === window.CURRENT_ID && (ev.tipo === 'despesa' || ev.tipo === 'entrada')) {
                                mapDue.delete(k);
                            }
                        }
                    }

                    if (payDate !== dueDate) {
                        const mapPayRed = cal.eventosCache[payDate];
                        if (mapPayRed) {
                            for (const [k, ev] of mapPayRed.entries()) {
                                if (ev.tx_id === window.CURRENT_ID && (ev.tipo === 'despesa' || ev.tipo === 'entrada')) {
                                    mapPayRed.delete(k);
                                }
                            }
                        }
                    }

                    // adiciona o evento azul no dia do pagamento (mantém como está)
                    const mapPay = cal.eventosCache[payDate] ?? (cal.eventosCache[payDate] = new Map());
                    const pid = `localpay_${window.CURRENT_ID}_${payDate}`;
                    mapPay.set(pid, {
                        id: pid,
                        tipo: 'payment',
                        color: '#0ea5e9',
                        icon: 'fa-regular fa-circle-check',
                        descricao: (window.CURRENT_TITLE || 'Pagamento'),
                        valor: Math.abs(payAmount),
                        valor_brl: formatBRL(Math.abs(payAmount)),
                        is_invoice: false,
                        paid: true,
                        card_id: null,
                        current_month: null,
                        invoice_id: null,
                        tx_id: window.CURRENT_ID
                    });

                    cal.fp.redraw();
                    const sel = cal.fp.selectedDates?.[0] ? cal.iso(cal.fp.selectedDates[0]) : payDate;
                    cal.exibirEventos(sel);
                }

                // limpa estado
                window.CURRENT_ID = null;
                window.CURRENT_TITLE = null;
                window.CURRENT_DUE_DATE = null;

            } catch (err) {
                alert(err.message || 'Erro ao registrar pagamento.');
            }
        });

        // fechar modal por [data-close="paymentModal"]
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-close="paymentModal"]')) hidePaymentModal();
        });
    </script>

    <script>
        const INVOICE_PAY_TPL = @json(route('invoice-payment.update', ['cardId' => '__CARD__', 'ym' => '__YM__']));

        function renderBreakdown(elId, k, root, label = 'atrasados') {
            const el = document.getElementById(elId);
            if (!el) return;
            const mes   = k[`${root}_mes_brl`];
            const extra = k[`${root}_atrasados_brl`]; // mantemos o campo; só muda o texto

            if (typeof mes === 'string' && typeof extra === 'string') {
                el.innerHTML =
                    `${mes}<span class="price-default mx-1"><span class="late"> + ${extra} ${label}</span></span>`;
            } else {
                const tot = k[`${root}_brl`];
                if (typeof tot === 'string') el.textContent = tot;
            }
        }

        function renderSaldoCofrinhos(elId, contasBRL, cofrinhosBRL){
            const el = document.getElementById(elId);
            if (!el) return;

            const contas = (typeof contasBRL === 'string') ? contasBRL : '';
            const cofr   = (typeof cofrinhosBRL === 'string') ? cofrinhosBRL : '';

            el.innerHTML = cofr
                ? `${contas}<span class="price-default mx-1"><span class="late"> +  ${cofr} cofrinhos</span></span>`
                : contas;
        }

        function applyKpis(k){
            const $ = id => document.getElementById(id);

            renderSaldoCofrinhos('kpi-contas', k.accountsBalance_brl, k.savingsBalance_brl);

            renderBreakdown('kpi-receber', k, 'aReceber', 'pendentes');
            renderBreakdown('kpi-pagar',   k, 'aPagar',   'atrasados');

            if ($('kpi-balanco') && (k.saldoPrevisto_brl || k.saldoMes_brl))
                $('kpi-balanco').textContent = (k.saldoPrevisto_brl || k.saldoMes_brl);
        }

        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-pay-invoice]');
            if (!btn) return;

            const cardEl = btn.closest('.transaction-card');
            const cardId = btn.dataset.card;
            const ym = btn.dataset.month;
            const amt = Number(btn.dataset.amount || 0);
            const title = btn.dataset.title || 'Fatura paga';

            const url = INVOICE_PAY_TPL.replace('__CARD__', cardId).replace('__YM__', ym);

            btn.disabled = true;
            try {
                const resp = await fetch(url, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': "{{ csrf_token() }}", 'Accept': 'application/json'},
                    credentials: 'same-origin'
                });
                if (!resp.ok) throw new Error(await resp.text());

                // KPIs após pagar fatura
                const currentMonth = document.getElementById('monthPicker')?.value || '';
                if (currentMonth) {
                    const r = await fetch(`{{ route('dashboard.kpis') }}?month=${currentMonth}`, {headers: {Accept: 'application/json'}});
                    if (r.ok) applyKpis(await r.json());
                }

                // remove da lista "Próximos pagamentos"
                if (cardEl) cardEl.remove();

                // === Atualiza o CALENDÁRIO dinâmico ===
                const cal = window.__cal;
                if (cal) {
                    // 1) remove eventos de fatura em aberto (ponto vermelho) do dia do vencimento
                    Object.keys(cal.eventosCache).forEach(day => {
                        const map = cal.eventosCache[day];
                        if (!map) return;
                        const toDel = [];
                        map.forEach((ev, key) => {
                            if (ev.is_invoice && !ev.paid && ev.card_id == cardId && ev.current_month == ym) {
                                toDel.push(key);
                            }
                        });
                        toDel.forEach(k => map.delete(k));
                    });

                    // 2) adiciona lançamento azul no DIA DO PAGAMENTO (hoje)
                    const todayIso = cal.iso(new Date());
                    const mapToday = cal.eventosCache[todayIso] ?? (cal.eventosCache[todayIso] = new Map());
                    const id = `invpay_local_${cardId}_${ym}_${Date.now()}`;
                    mapToday.set(id, {
                        id,
                        tipo: 'payment',
                        color: '#0ea5e9',
                        icon: 'fa-regular fa-circle-check',
                        descricao: title,
                        valor: Math.abs(amt),
                        valor_brl: Number(Math.abs(amt)).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'}),
                        is_invoice: true,
                        paid: true,
                        card_id: cardId,
                        current_month: ym
                    });

                    cal.fp.redraw();
                    const sel = cal.fp.selectedDates?.[0] ? cal.iso(cal.fp.selectedDates[0]) : todayIso;
                    cal.exibirEventos(sel);
                }
            } catch (err) {
                alert('Erro ao pagar fatura: ' + (err.message || ''));
                btn.disabled = false;
            }
        });
    </script>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>

        <script>
            (() => {
                const routeUrl = "{{ route('calendar.events') }}";

                const ym = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
                const iso = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                const br = s => {
                    const [y, m, d] = String(s).slice(0, 10).split('-');
                    return `${d}/${m}/${y}`;
                };
                const brl = n => Number(n || 0).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
                const escAttr = s => String(s ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

                const eventosCache = {};
                const loadedWindows = new Set();

                function addEventToCache(ev) {
                    const day = String(ev.start).slice(0, 10);
                    const id = ev.id ?? `${ev.title}-${ev.start}`;
                    const xp = ev.extendedProps || {};
                    const item = {
                        id,
                        tipo: (xp.type || '').toLowerCase().trim(),
                        color: ev.bg,
                        icon: ev.icon,
                        descricao: ev.title ?? xp.category_name ?? 'Sem descrição',
                        valor: Number(xp.amount ?? 0),
                        valor_brl: xp.amount_brl,
                        is_invoice: !!xp.is_invoice,
                        paid: !!xp.paid,
                        card_id: xp.card_id || null,
                        current_month: xp.current_month || null,
                        invoice_id: xp.invoice_id || null,
                        tx_id: xp.transaction_id || null,
                    };
                    const map = eventosCache[day] ?? (eventosCache[day] = new Map());
                    if (!map.has(id)) map.set(id, item);
                }

                async function loadWindow(ymStr, months = 2) {
                    const key = `${ymStr}:${months}`;
                    if (loadedWindows.has(key)) return;
                    const resp = await fetch(`${routeUrl}?start=${ymStr}&months=${months}`, {headers: {'Accept': 'application/json'}});
                    if (!resp.ok) return;
                    (await resp.json()).forEach(addEventToCache);
                    loadedWindows.add(key);
                }

                const eventosDoDia = dateStr => {
                    const map = eventosCache[dateStr];
                    return map ? Array.from(map.values()) : [];
                };

                function diasDoMes(y, m) {
                    const out = [], d = new Date(y, m - 1, 1);
                    while (d.getMonth() === m - 1) {
                        out.push(iso(d));
                        d.setDate(d.getDate() + 1);
                    }
                    return out;
                }

                async function atualizarKpisDoMes(ymStr) {
                    startLoading('kpi-contas', 'kpi-cofrinhos', 'kpi-receber', 'kpi-pagar', 'kpi-balanco');
                    try {
                        const url = `{{ route('dashboard.kpis') }}?month=${encodeURIComponent(ymStr)}&cumulative=1`;
                        const r = await fetch(url, {headers: {Accept: 'application/json'}});
                        if (!r.ok) throw new Error('Falha ao carregar KPIs');
                        const k = await r.json();

                        //if (k.accountsBalance_brl) document.getElementById('kpi-contas').textContent = k.accountsBalance_brl;
                        //if (k.savingsBalance_brl) document.getElementById('kpi-cofrinhos').textContent = k.savingsBalance_brl;

                        renderSaldoCofrinhos('kpi-contas', k.accountsBalance_brl, k.savingsBalance_brl);

                        // mostra os valores
                        renderBreakdown('kpi-receber', k, 'aReceber', 'pendentes');
                        renderBreakdown('kpi-pagar',   k, 'aPagar',   'atrasados');
                        document.getElementById('kpi-balanco').textContent = k.saldoPrevisto_brl;
                    } catch (e) {
                        console.error(e);
                    } finally {
                        finishLoading('kpi-contas', 'kpi-cofrinhos', 'kpi-receber', 'kpi-pagar', 'kpi-balanco');
                    }
                }

                function exibirEventos(dateStr) {
                    const c = document.getElementById('calendar-results');
                    const eventos = eventosDoDia(dateStr);

                    let html = `<h2 class="mt-3">Lançamentos do dia ${br(dateStr)}</h2>`;
                    if (!eventos.length) {
                        html += `<div class="transaction-card"><div class="transaction-info"><div class="icon"><i class="fa-solid fa-sack-dollar"></i></div><div class="details">Nenhum lançamento.</div></div></div>`;
                        c.innerHTML = html;
                        return;
                    }

                    for (const ev of eventos) {
                        const isPaidInv = ev.is_invoice && ev.paid === true;
                        const iconCls = isPaidInv ? 'fa-regular fa-circle-check' : (ev.icon || 'fa-solid fa-file-invoice-dollar');
                        const bgColor = isPaidInv ? '#0ea5e9' : (ev.color || '#999');

                        let amountHtml = ev.valor_brl, sinal = '';
                        if (!isPaidInv) {
                            sinal = ev.tipo === 'despesa' ? '-' : (ev.tipo === 'entrada' ? '+' : '');
                        } else {
                            amountHtml = brl(Math.abs(ev.valor || 0));
                        }

                        let action = '';
                        if (ev.is_invoice && ev.card_id && ev.current_month && !ev.paid) {
                            action = `<button type="button" class="bg-transparent border-0"
                  data-pay-invoice data-card="${ev.card_id}" data-month="${ev.current_month}"
                  data-amount="${Math.abs(ev.valor || 0)}" data-title="${escAttr(ev.descricao)}">
                  <i class="fa-solid fa-check text-success"></i></button>`;
                        } else if ((ev.tipo === 'despesa' || ev.tipo === 'entrada') && ev.tx_id && !ev.paid) {
                            action = `<button type="button" class="bg-transparent border-0"
                  data-open-payment data-id="${ev.tx_id}"
                  data-amount="${Math.abs(ev.valor)}" data-date="${dateStr}" data-title="${escAttr(ev.descricao)}">
                  <i class="fa-solid fa-check-to-slot text-success"></i></button>`;
                        }

                        html += `
      <div class="transaction-card">
        <div class="transaction-info">
          <div class="icon text-white" style="background-color:${bgColor}"><i class="${iconCls}"></i></div>
          <div class="details">${ev.descricao}<br><span>${br(dateStr)}</span></div>
        </div>
        <div class="d-flex align-items-center">
          <div class="transaction-amount price-default mx-3">${sinal} ${amountHtml}</div>
          ${action}
        </div>
      </div>`;
                    }
                    c.innerHTML = html;
                }

                let fp;

                document.addEventListener('DOMContentLoaded', () => {
                    fp = flatpickr("#calendar", {
                        locale: 'pt',
                        inline: true,
                        defaultDate: "today",
                        disableMobile: true,

                        onDayCreate: (_, _2, _3, dayElem) => {
                            const d = iso(dayElem.dateObj);
                            const evs = eventosDoDia(d);
                            if (!evs.length) return;

                            const hasGreen = evs.some(e => e.tipo === 'entrada');
                            const hasRed = evs.some(e => (e.tipo === 'despesa' && !e.is_invoice) || (e.is_invoice && !e.paid));
                            const hasBlue = evs.some(e => e.tipo === 'investimento' || e.tipo === 'payment' || (e.is_invoice && e.paid));

                            const wrap = document.createElement('div');
                            wrap.style.cssText = 'display:flex;justify-content:center;gap:2px;margin-top:-10px';
                            const dot = c => {
                                const s = document.createElement('span');
                                s.style.cssText = `width:6px;height:6px;background:${c};border-radius:50%`;
                                wrap.appendChild(s);
                            };

                            if (hasGreen) dot('green');
                            if (hasRed) dot('red');
                            if (hasBlue) dot('#0ea5e9');

                            if (wrap.childElementCount) dayElem.appendChild(wrap);
                        },

                        onMonthChange: async (_sd, _ds, inst) => {
                            const first = new Date(inst.currentYear, inst.currentMonth, 1);
                            const ymStr = ym(first);
                            await loadWindow(ymStr, 2);
                            inst.redraw();
                            const monthPicker = document.getElementById('monthPicker');
                            monthPicker.value = ymStr;
                            atualizarKpisDoMes(ymStr);
                        },

                        onYearChange: async (_sd, _ds, inst) => {
                            const first = new Date(inst.currentYear, inst.currentMonth, 1);
                            const ymStr = ym(first);
                            await loadWindow(ymStr, 2);
                            inst.redraw();
                            const monthPicker = document.getElementById('monthPicker');
                            monthPicker.value = ymStr;
                            atualizarKpisDoMes(ymStr);
                        },

                        onReady: async (sd, _ds, inst) => {
                            const first = new Date(inst.currentYear, inst.currentMonth, 1);
                            const ymStr = ym(first);
                            await loadWindow(ymStr, 2);
                            inst.redraw();
                            exibirEventos(iso(sd?.[0] ?? new Date()));
                            const initialYm = document.getElementById('monthPicker').value || ymStr;
                            atualizarKpisDoMes(initialYm);
                        },

                        onChange: sd => {
                            if (sd?.[0]) exibirEventos(iso(sd[0]));
                        }
                    });

                    document.getElementById('monthForm')?.addEventListener('submit', e => e.preventDefault());
                    document.getElementById('monthPicker')?.addEventListener('change', async (e) => {
                        await window.syncMonthUI(e.target.value);
                    });

                    // expõe para outros scripts
                    window.__cal = {fp, eventosCache, exibirEventos, iso};
                });

                async function syncMonthUI(ymStr) {
                    const [y, m] = ymStr.split('-').map(Number);
                    const first = new Date(y, m - 1, 1);

                    await loadWindow(ymStr, 2);

                    fp.jumpToDate(first, true);
                    fp.setDate(first, true);
                    fp.redraw();

                    exibirEventos(iso(first));
                    atualizarKpisDoMes(ymStr);
                }

                window.syncMonthUI = syncMonthUI;

                async function changeMonth(delta) {
                    const input = document.getElementById('monthPicker');
                    const [y, m] = input.value.split('-').map(Number);
                    const next = new Date(y, m - 1 + delta, 1);
                    const ymStr = ym(next);
                    input.value = ymStr;
                    await syncMonthUI(ymStr);
                }

                window.changeMonth = changeMonth;

            })();
        </script>
    @endpush
@endsection
