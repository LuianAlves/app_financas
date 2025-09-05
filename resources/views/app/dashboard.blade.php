@extends('layouts.templates.new_layout')

@section('new-content')
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

        <style>
            .skel{position:relative;overflow:hidden;border-radius:.5rem;background:#e5e7eb}
            .dark .skel{background:#262626}
            .skel::after{content:"";position:absolute;inset:0;transform:translateX(-100%);
                background:linear-gradient(90deg,transparent,rgba(255,255,255,.55),transparent);animation:skel 1.1s infinite}
            @keyframes skel{100%{transform:translateX(100%)}}

            .shimmer.is-loading{position:relative}
            .shimmer.is-loading::after{content:"";position:absolute;inset:0;
                background:linear-gradient(90deg,transparent,rgba(255,255,255,.5),transparent);animation:skel 1.1s infinite;opacity:.35;pointer-events:none}
            .dark .shimmer.is-loading::after{background:linear-gradient(90deg,transparent,rgba(255,255,255,.08),transparent);opacity:.6}
            .shimmer.is-loaded::after{display:none}

            .nav-link-atalho{display:flex;flex-direction:column;align-items:center;gap:.35rem;text-decoration:none;color:inherit;font-size:.8rem;font-weight:600}
            .icons-carousel{display:flex;gap:1rem;overflow-x:auto;padding:.5rem .25rem}
            .icon-button{min-width:88px;display:flex;justify-content:center}
            .icon-button i{font-size:20px}

            .transaction-card{display:flex;align-items:center;justify-content:space-between;gap:.75rem;
                border:1px solid rgba(0,0,0,.08);border-radius:1rem;background:#fff;color:#0b0b0b;
                padding:.9rem 1rem}
            .dark .transaction-card{background:#0a0a0a;border-color:#272727;color:#e5e5e5}
            .transaction-info{display:flex;align-items:center;gap:.75rem}
            .transaction-info .icon{display:grid;place-items:center;width:36px;height:36px;border-radius:.75rem;background:#eef2ff;color:#1f2937}
            .transaction-amount{font-weight:600}
            .price-default{font-variant-numeric:tabular-nums}
            .late{color:#dc2626}

            .balance-box{border:1px solid rgba(0,0,0,.08);border-radius:1rem;background:#fff;padding:1rem}
            .dark .balance-box{background:#0a0a0a;border-color:#272727}
            .dash-amounts{font-size:.8rem}
        </style>
    @endpush

    <section class="mt-6 space-y-4">
        <div class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4">
            <button class="w-full mb-3 hidden items-center justify-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-3 py-2"
                    style="letter-spacing:.75px;font-size:12px;font-weight:600" data-install>
                <i class="fa fa-download"></i> Baixe o aplicativo
            </button>

            <div id="ios-a2hs" class="hidden rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-neutral-50 dark:bg-neutral-800 p-3 mb-2" role="alert">
                No iPhone: toque <strong>Compartilhar</strong> → <strong>Adicionar à Tela de Início</strong> para instalar o app.
            </div>

            <button id="ios-enable-push" class="hidden inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-3 py-2 text-sm">Ativar notificações</button>

            <div class="flex items-center justify-between mb-2">
                <h1 class="text-xl font-semibold m-0">Tela inicial</h1>
            </div>

            <form id="monthForm" class="flex items-center justify-between w-full gap-2">
                <button type="button" class="inline-flex items-center justify-center rounded-full border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 shadow-soft size-9"
                        onclick="changeMonth(-1)">
                    <i class="fa fa-chevron-left"></i>
                </button>

                <input type="month" name="month" id="monthPicker"
                       class="w-full max-w-xs text-center font-semibold rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"
                       value="{{ $startOfMonth->format('Y-m') }}">

                <button type="button" class="inline-flex items-center justify-center rounded-full border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 shadow-soft size-9"
                        onclick="changeMonth(1)">
                    <i class="fa fa-chevron-right"></i>
                </button>
            </form>
        </div>

        <div class="balance-box">
            <div class="flex items-center justify-between">
                <span>Projeção do mês</span>
                <i class="fa fa-eye"></i>
            </div>

            <div class="shimmer is-loading mt-1">
                <strong id="kpi-balanco" class="block" style="font-size:36px"></strong>
            </div>

            <div class="mt-3">
                <div class="grid grid-cols-1 gap-2">
                    <div>
                        <b class="text-blue-600 dash-amounts">Saldo contas</b>
                        <div class="h-[15px] flex items-center">
                            <div class="shimmer is-loading">
                                <span id="kpi-contas" class="price-default"></span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <b class="text-brand-600 dash-amounts">A receber</b>
                        <div class="h-[15px] flex items-center">
                            <div class="shimmer is-loading">
                                <span id="kpi-receber" class="price-default"></span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <b class="text-red-600 dash-amounts">A pagar</b>
                        <div class="h-[15px] flex items-center">
                            <div class="shimmer is-loading">
                                <span id="kpi-pagar" class="price-default"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end items-center mt-3">
                <a href="{{ url()->current() . '?month=' . $startOfMonth->format('Y-m') }}"
                   class="text-brand-600 font-semibold text-[13px]">Extrato do mês</a>
            </div>
        </div>

        <div class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-3">
            <div class="icons-carousel">
                <div class="icon-button">
                    <a href="{{ route('account-view.index') }}" class="nav-link-atalho">
                        <i class="fas fa-landmark"></i> Bancos
                    </a>
                </div>
                <div class="icon-button">
                    <a href="{{ route('transaction-view.index') }}" class="nav-link-atalho">
                        <i class="fa-solid fa-cart-plus"></i> Transações
                    </a>
                </div>
                <div class="icon-button">
                    <a href="{{ route('card-view.index') }}" class="nav-link-atalho">
                        <i class="fas fa-credit-card"></i> Cartões
                    </a>
                </div>
                <div class="icon-button">
                    <a href="{{ route('investment-view.index') }}" class="nav-link-atalho">
                        <i class="fas fa-chart-line"></i> Investimentos
                    </a>
                </div>
                <div class="icon-button">
                    <a href="{{ route('saving-view.index') }}" class="nav-link-atalho">
                        <i class="fas fa-piggy-bank"></i> Cofrinhos
                    </a>
                </div>
                <div class="icon-button">
                    <a href="{{ route('projection-view.index') }}" class="nav-link-atalho">
                        <i class="fas fa-calendar"></i> Projeção
                    </a>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-3">
            <div id="calendar"></div>
            <div id="calendar-results" class="py-3"></div>
        </div>

        <div class="space-y-2">
            <h2 class="text-lg font-semibold">Próximos pagamentos</h2>
            @forelse($upcomingAny as $item)
                <div class="transaction-card" data-invoice-card>
                    <div class="transaction-info">
                        <div class="icon text-white" style="background: {{ $item['color'] }};">
                            <i class="{{ $item['icon'] }}"></i>
                        </div>
                        <div class="details">
                            {{ $item['title'] }}<br>
                            <span class="text-neutral-500 text-sm">{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</span>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div class="transaction-amount price-default mx-3">
                            - {{ brlPrice($item['amount']) }}
                        </div>

                        @if($item['kind'] === 'tx')
                            <button type="button"
                                    class="bg-transparent border-0"
                                    data-open-payment
                                    data-id="{{ $item['modal_id'] }}"
                                    data-amount="{{ $item['modal_amount'] }}"
                                    data-date="{{ \Carbon\Carbon::parse($item['modal_date'])->format('Y-m-d') }}"
                                    data-title="{{ e($item['title']) }}">
                                <i class="fa-solid fa-check-to-slot text-green-600"></i>
                            </button>
                        @else
                            <button type="button"
                                    class="bg-transparent border-0"
                                    data-pay-invoice
                                    data-card="{{ $item['card_id'] }}"
                                    data-month="{{ $item['current_month'] }}"
                                    data-amount="{{ $item['amount'] }}"
                                    data-title="{{ e($item['title']) }}">
                                <i class="fa-solid fa-check text-green-600"></i>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="transaction-card">
                    <div class="transaction-info">
                        <div class="icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                        <div class="details">Nenhum pagamento.</div>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="space-y-2">
            <h2 class="text-lg font-semibold">Transações recentes</h2>
            @forelse($recentTransactions as $transaction)
                @php
                    $categoryType = optional($transaction->transactionCategory)->type;
                    $categoryName = optional($transaction->transactionCategory)->name;
                @endphp

                <div class="transaction-card">
                    <div class="transaction-info">
                        <div class="icon text-white" style="background: {{$transaction->transactionCategory->color}}">
                            <i class="{{$transaction->transactionCategory->icon}}"></i>
                        </div>

                        <div class="details">
                            <p class="m-0 p-0 font-medium">{{ ucwords($transaction->title) ?? ucwords($categoryName) }}</p>
                            @if($transaction->type == 'card')
                                <small class="text-neutral-500" style="font-size: 10.5px;">No cartão</small>
                            @endif
                            @if($transaction->date)
                                <span class="mt-1 block text-neutral-500 text-sm">
                                    {{ \Carbon\Carbon::parse($transaction->date)->format('d/m/Y') }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="transaction-amount price-default">
                        {{ $categoryType === 'despesa' ? '-' : '+' }} {{ brlPrice($transaction->amount) }}
                    </div>
                </div>
            @empty
                <p class="text-neutral-500">Nenhuma transação encontrada</p>
            @endforelse
        </div>

        <h2 class="text-lg font-semibold">Faturas atuais</h2>
        <div class="balance-box">
            @if($cardTip)
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-credit-card" style="color: {{ $cardTip['color'] }}"></i>
                    <small class="text-neutral-700 dark:text-neutral-300">{{ $cardTip['label'] }}</small>
                </div>
            @endif
        </div>

        @foreach($currentInvoices as $inv)
            <div class="balance-box mt-2">
                <div class="flex items-center justify-between">
                    <span>{{ $inv['title'] }}</span>
                </div>
                <strong class="text-lg">{{ $inv['total_brl'] }}</strong>

                @if(!is_null($inv['available_limit']))
                    <div class="flex items-center justify-between">
                        <span>Limite disponível <b>{{ brlPrice($inv['available_limit']) }}</b></span>
                    </div>
                @endif

                <div class="flex items-center justify-between">
                    <span>Vence em <b>{{ $inv['due_label'] }}.</b></span>
                </div>
            </div>
        @endforeach
    </section>

    <x-modal id="paymentModal" titleCreate="Registrar pagamento" titleEdit="Registrar pagamento" titleShow="Registrar pagamento" submitLabel="Salvar">
        <input type="hidden" name="transaction_id" id="payment_transaction_id">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <label class="block">
                <span class="text-xs text-neutral-500 dark:text-neutral-400">Valor pago</span>
                <input type="text" inputmode="decimal" name="amount" id="payment_amount"
                       class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"
                       required>
            </label>
            <label class="block">
                <span class="text-xs text-neutral-500 dark:text-neutral-400">Data do pagamento</span>
                <input type="date" name="payment_date" id="payment_date"
                       class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"
                       required>
            </label>
        </div>
    </x-modal>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>

        <script>
            function startLoading(...ids){
                ids.forEach(id=>{
                    const el = document.getElementById(id);
                    el?.closest('.shimmer')?.classList.add('is-loading');
                    el?.closest('.shimmer')?.classList.remove('is-loaded');
                });
            }
            function finishLoading(...ids){
                ids.forEach(id=>{
                    const el = document.getElementById(id);
                    el?.closest('.shimmer')?.classList.remove('is-loading');
                    el?.closest('.shimmer')?.classList.add('is-loaded');
                });
            }

            const PAY_TPL = @json(route('transaction-payment', ['transaction' => '__ID__']));
            const INVOICE_PAY_TPL = @json(route('invoice-payment.update', ['cardId' => '__CARD__', 'ym' => '__YM__']));
            let CURRENT_TX_CARD = null;
            let CURRENT_ID = null, CURRENT_TITLE = null, CURRENT_DUE_DATE = null;

            function getPaymentEls() {
                const modal  = document.getElementById('paymentModal');
                const form   = modal?.querySelector('form');
                const inAmt  = modal?.querySelector('#payment_amount,[name="amount"]');
                const inDate = modal?.querySelector('#payment_date,[name="payment_date"],[name="date"]');
                return { modal, form, inAmt, inDate };
            }
            function showPaymentModal() {
                const { modal } = getPaymentEls();
                modal?.classList.remove('hidden');
                document.body.classList.add('overflow-hidden','ui-modal-open');
            }
            function hidePaymentModal() {
                const { modal } = getPaymentEls();
                modal?.classList.add('hidden');
                document.body.classList.remove('overflow-hidden','ui-modal-open');
            }

            function parseMoneyBR(input) {
                if (typeof input === 'number') return input;
                let s = String(input || '').trim();
                s = s.replace(/[^\d.,-]/g, '');
                const lastComma = s.lastIndexOf(','), lastDot = s.lastIndexOf('.');
                if (lastComma > -1 && lastDot > -1) {
                    if (lastComma > lastDot) s = s.replace(/\./g, '').replace(',', '.');
                    else s = s.replace(/,/g, '');
                } else if (lastComma > -1) {
                    s = s.replace(',', '.');
                }
                return Number(s || 0);
            }
            const formatBRL = v => Number(v || 0).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});

            function renderBreakdown(elId, k, root, label = 'atrasados') {
                const el = document.getElementById(elId);
                if (!el) return;
                const mes   = k[`${root}_mes_brl`];
                const extra = k[`${root}_atrasados_brl`];
                if (typeof mes === 'string' && typeof extra === 'string') {
                    el.innerHTML = `${mes}<span class="price-default mx-1"><span class="late"> + ${extra} ${label}</span></span>`;
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
                renderSaldoCofrinhos('kpi-contas', k.accountsBalance_brl, k.savingsBalance_brl);
                renderBreakdown('kpi-receber', k, 'aReceber', 'pendentes');
                renderBreakdown('kpi-pagar',   k, 'aPagar',   'atrasados');
                const el = document.getElementById('kpi-balanco');
                if (el) el.textContent = (k.saldoPrevisto_brl || k.saldoMes_brl || k.saldoReal_brl || '');
            }

            document.addEventListener('click', (e) => {
                const btnPayTx = e.target.closest('[data-open-payment]');
                if (btnPayTx) {
                    const { form, inAmt, inDate } = getPaymentEls();
                    if (!form) { alert('Formulário do pagamento não encontrado.'); return; }

                    CURRENT_TX_CARD   = btnPayTx.closest('.transaction-card') || null;
                    CURRENT_ID        = btnPayTx.dataset.id;
                    CURRENT_TITLE     = btnPayTx.dataset.title || 'Pagamento';
                    CURRENT_DUE_DATE  = (btnPayTx.dataset.date || '').slice(0,10);

                    form.action = PAY_TPL.replace('__ID__', CURRENT_ID);
                    if (inAmt)  inAmt.value  = btnPayTx.dataset.amount || '0';
                    if (inDate) inDate.value = CURRENT_DUE_DATE;

                    showPaymentModal();
                    return;
                }

                const btnPayInv = e.target.closest('[data-pay-invoice]');
                if (btnPayInv) payInvoice(btnPayInv);
            });

            async function payInvoice(btn){
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

                    const currentMonth = document.getElementById('monthPicker')?.value || '';
                    if (currentMonth) {
                        const r = await fetch(`{{ route('dashboard.kpis') }}?month=${currentMonth}`, {headers: {Accept: 'application/json'}});
                        if (r.ok) applyKpis(await r.json());
                    }

                    if (cardEl) cardEl.remove();

                    const cal = window.__cal;
                    if (cal) {
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

                        const todayIso = cal.iso(new Date());
                        const mapToday = cal.eventosCache[todayIso] ?? (cal.eventosCache[todayIso] = new Map());
                        const id = `invpay_local_${cardId}_${ym}_${Date.now()}`;
                        mapToday.set(id, {
                            id, tipo:'payment', color:'#0ea5e9', icon:'fa-regular fa-circle-check',
                            descricao:title, valor:Math.abs(amt), valor_brl:Number(Math.abs(amt)).toLocaleString('pt-BR',{style:'currency',currency:'BRL'}),
                            is_invoice:true, paid:true, card_id:cardId, current_month:ym
                        });

                        cal.fp.redraw();
                        const sel = cal.fp.selectedDates?.[0] ? cal.iso(cal.fp.selectedDates[0]) : todayIso;
                        cal.exibirEventos(sel);
                    }
                } catch (err) {
                    alert('Erro ao pagar fatura: ' + (err.message || ''));
                    btn.disabled = false;
                }
            }

            document.getElementById('paymentModal')?.addEventListener('submit', async (e) => {
                const form = e.target;
                if (form.tagName !== 'FORM') return;
                e.preventDefault();

                const { inAmt, inDate } = getPaymentEls();
                const fd = new FormData(form);
                const currentMonth = document.getElementById('monthPicker')?.value || '';
                if (currentMonth) fd.set('month', currentMonth);

                try {
                    const resp = await fetch(form.action, {
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

                    const payDate = (inDate?.value || '').slice(0,10);
                    const dueDate = (CURRENT_DUE_DATE || payDate);
                    const payAmount = parseMoneyBR(inAmt?.value || 0);

                    let data = null;
                    try { data = await resp.json(); } catch (_){}

                    if (data && data.ok) {
                        applyKpis(data);
                    } else if (currentMonth) {
                        const r = await fetch(`{{ route('dashboard.kpis') }}?month=${currentMonth}`, {headers: {Accept: 'application/json'}});
                        if (r.ok) applyKpis(await r.json());
                    }

                    hidePaymentModal();

                    if (CURRENT_TX_CARD) {
                        CURRENT_TX_CARD.remove();
                        CURRENT_TX_CARD = null;
                    }
                    form.reset();

                    const cal = window.__cal;
                    if (cal && cal.eventosCache) {
                        const mapDue = cal.eventosCache[dueDate];
                        if (mapDue) {
                            const toDel = [];
                            mapDue.forEach((ev, key) => {
                                if (ev.tx_id === CURRENT_ID && (ev.tipo === 'despesa' || ev.tipo === 'entrada')) {
                                    toDel.push(key);
                                }
                            });
                            toDel.forEach(k => mapDue.delete(k));
                        }

                        if (payDate && payDate !== dueDate) {
                            const mapPayRed = cal.eventosCache[payDate];
                            if (mapPayRed) {
                                const toDel2 = [];
                                mapPayRed.forEach((ev, key) => {
                                    if (ev.tx_id === CURRENT_ID && (ev.tipo === 'despesa' || ev.tipo === 'entrada')) {
                                        toDel2.push(key);
                                    }
                                });
                                toDel2.forEach(k => mapPayRed.delete(k));
                            }
                        }

                        const mapPay = cal.eventosCache[payDate] ?? (cal.eventosCache[payDate] = new Map());
                        const pid = `localpay_${CURRENT_ID}_${payDate}`;
                        mapPay.set(pid, {
                            id: pid,
                            tipo: 'payment',
                            color: '#0ea5e9',
                            icon: 'fa-regular fa-circle-check',
                            descricao: (CURRENT_TITLE || 'Pagamento'),
                            valor: Math.abs(payAmount),
                            valor_brl: formatBRL(Math.abs(payAmount)),
                            is_invoice: false,
                            paid: true,
                            card_id: null,
                            current_month: null,
                            invoice_id: null,
                            tx_id: CURRENT_ID
                        });

                        cal.fp.redraw();
                        const sel = cal.fp.selectedDates?.[0] ? cal.iso(cal.fp.selectedDates[0]) : payDate;
                        cal.exibirEventos(sel);
                    }

                    CURRENT_ID = null;
                    CURRENT_TITLE = null;
                    CURRENT_DUE_DATE = null;
                } catch (err) {
                    alert(err.message || 'Erro ao registrar pagamento.');
                }
            });

            (function calendarBoot(){
                const routeUrl = "{{ route('calendar.events') }}";
                const ym = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
                const iso = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                const br = s => { const [y,m,d]=String(s).slice(0,10).split('-'); return `${d}/${m}/${y}`; };
                const brl = n => Number(n||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});
                const escAttr = s => String(s ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

                const eventosCache = {};
                const loadedWindows = new Set();

                function addEventToCache(ev){
                    const day = String(ev.start).slice(0,10);
                    const xp = ev.extendedProps || {};
                    let key = ev.id ?? `${ev.title}-${ev.start}`;

                    const tipo = (xp.type || '').toLowerCase().trim();
                    const isTxLaunch = (tipo === 'entrada' || tipo === 'despesa') && xp.transaction_id;
                    if (isTxLaunch) key = `tx_${xp.transaction_id}_${day}`;

                    const item = {
                        id:key, tipo, color:ev.bg, icon:ev.icon,
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
                    if (!map.has(key)) map.set(key, item);
                }

                async function loadWindow(ymStr, months=2){
                    const key = `${ymStr}:${months}`;
                    if (loadedWindows.has(key)) return;
                    const resp = await fetch(`${routeUrl}?start=${ymStr}&months=${months}`, {headers:{'Accept':'application/json'}});
                    if (!resp.ok) return;
                    (await resp.json()).forEach(addEventToCache);
                    loadedWindows.add(key);
                }

                const eventosDoDia = dateStr => {
                    const map = eventosCache[dateStr];
                    return map ? Array.from(map.values()) : [];
                };

                function exibirEventos(dateStr){
                    const c = document.getElementById('calendar-results');
                    const eventos = eventosDoDia(dateStr);

                    let html = `<h2 class="mt-3 text-lg font-semibold">Lançamentos do dia ${br(dateStr)}</h2>`;
                    if (!eventos.length){
                        html += `<div class="transaction-card"><div class="transaction-info"><div class="icon"><i class="fa-solid fa-sack-dollar"></i></div><div class="details">Nenhum lançamento.</div></div></div>`;
                        c.innerHTML = html; return;
                    }

                    for (const ev of eventos){
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
                                <i class="fa-solid fa-check text-green-600"></i></button>`;
                        } else if ((ev.tipo === 'despesa' || ev.tipo === 'entrada') && ev.tx_id && !ev.paid) {
                            action = `<button type="button" class="bg-transparent border-0"
                                data-open-payment data-id="${ev.tx_id}"
                                data-amount="${Math.abs(ev.valor)}" data-date="${dateStr}" data-title="${escAttr(ev.descricao)}">
                                <i class="fa-solid fa-check-to-slot text-green-600"></i></button>`;
                        }

                        html += `
                        <div class="transaction-card">
                            <div class="transaction-info">
                                <div class="icon text-white" style="background-color:${bgColor}"><i class="${iconCls}"></i></div>
                                <div class="details">${ev.descricao}<br><span class="text-neutral-500 text-sm">${br(dateStr)}</span></div>
                            </div>
                            <div class="flex items-center">
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
                            const dot = c => { const s = document.createElement('span'); s.style.cssText = `width:6px;height:6px;background:${c};border-radius:50%`; wrap.appendChild(s); };
                            if (hasGreen) dot('green');
                            if (hasRed)   dot('red');
                            if (hasBlue)  dot('#0ea5e9');
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
                        onChange: sd => { if (sd?.[0]) exibirEventos(iso(sd[0])); }
                    });

                    document.getElementById('monthForm')?.addEventListener('submit', e => e.preventDefault());
                    document.getElementById('monthPicker')?.addEventListener('change', async (e) => {
                        await window.syncMonthUI(e.target.value);
                    });

                    window.__cal = {fp, eventosCache, exibirEventos, iso};
                });

                async function atualizarKpisDoMes(ymStr){
                    startLoading('kpi-contas','kpi-receber','kpi-pagar','kpi-balanco');
                    try {
                        const url = `{{ route('dashboard.kpis') }}?month=${encodeURIComponent(ymStr)}&cumulative=1`;
                        const r = await fetch(url, {headers:{Accept:'application/json'}});
                        if (!r.ok) throw new Error('Falha ao carregar KPIs');
                        const k = await r.json();
                        renderSaldoCofrinhos('kpi-contas', k.accountsBalance_brl, k.savingsBalance_brl);
                        renderBreakdown('kpi-receber', k, 'aReceber', 'pendentes');
                        renderBreakdown('kpi-pagar',   k, 'aPagar',   'atrasados');
                        document.getElementById('kpi-balanco').textContent = k.saldoPrevisto_brl;
                    } catch(e){ console.error(e); }
                    finally { finishLoading('kpi-contas','kpi-receber','kpi-pagar','kpi-balanco'); }
                }

                async function syncMonthUI(ymStr){
                    const [y,m] = ymStr.split('-').map(Number);
                    const first = new Date(y, m-1, 1);
                    await loadWindow(ymStr, 2);
                    fp.jumpToDate(first, true);
                    fp.setDate(first, true);
                    fp.redraw();
                    exibirEventos(iso(first));
                    atualizarKpisDoMes(ymStr);
                }
                window.syncMonthUI = syncMonthUI;

                async function changeMonth(delta){
                    const input = document.getElementById('monthPicker');
                    const [y,m] = input.value.split('-').map(Number);
                    const next = new Date(y, m-1 + delta, 1);
                    const ymStr = ym(next);
                    input.value = ymStr;
                    await syncMonthUI(ymStr);
                }
                window.changeMonth = changeMonth;
            })();
        </script>
    @endpush
@endsection
