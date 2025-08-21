@extends('layouts.templates.app')
@section('content')
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

        <style>
            .value-line {
                position: relative;
                display: grid;
                grid-template-areas:"stack";
                align-items: center;
                min-height: 25px
            }

            .value-line > * {
                grid-area: stack
            }

            .preloader-values-sm {
                width: 75px;
                height: 22px;
                margin: 5px 0;
            }

            .preloader-values-lg {
                width: 100%;
                height: 32px;
                margin: 10px 0px 5px 0px;
            }

            .preloader-values {
                border-radius: 25px;
                background: rgba(143, 143, 143, 0.55);
                overflow: hidden;
                position: relative
            }

            .preloader-values::after {
                content: "";
                position: absolute;
                inset: 0;
                background: linear-gradient(90deg, rgba(229, 229, 229, .38), rgba(255, 255, 255, .75), rgba(229, 229, 229, .38));
                background-size: 200% 100%;
                animation: shimmer 1.2s linear infinite
            }

            @keyframes shimmer {
                0% {
                    background-position: 200% 0
                }
                100% {
                    background-position: -200% 0
                }
            }

            .value-line.is-loading .value-real {
                opacity: 0
            }

            .value-line.is-loading .preloader-values {
                opacity: 1
            }

            .value-line.loaded .value-real {
                opacity: 1;
                transition: opacity .18s
            }

            .value-line.loaded .preloader-values {
                opacity: 0;
                pointer-events: none
            }
        </style>
    @endpush

    <div class="header">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h1 class="m-0 mb-3">Tela inicial</h1>
        </div>

        <!-- Input no topo -->
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

        <!-- Saldo das contas -->
        <div class="balance-box mt-2">
            <div class="d-flex justify-content-between">
                <span>Saldo do mês</span>
                <i class="fa fa-eye"></i>
            </div>

            {{-- Saldo do mês --}}
            <div class="value-line is-loading">
                <strong class="value-real" id="kpi-balanco"></strong>
                <div class="preloader-values preloader-values-lg"></div>
            </div>


            <div class="d-flex justify-content-between flex-column">

                <!-- Saldo em contas -->
                <div class="d-flex justify-content-between">
                    <b class="text-muted dash-amounts">Saldo contas</b>
                    <div class="d-flex align-items-center">
                        <span class="price-default">{{ brlPrice($accountsBalance) }}</span>
                    </div>
                </div>

                <!-- Cofrinhos -->
                <div class="d-flex justify-content-between mb-2">
                    <b class="text-muted dash-amounts">Cofrinhos</b>
                    <div>
                        <span class="price-default">{{ brlPrice($savingsBalance) }}</span>
                    </div>
                </div>

                {{-- A receber --}}
                <div class="d-flex justify-content-between">
                    <b class="text-muted dash-amounts">A receber</b>
                    <div class="d-flex align-items-center">
                        <a href="{{ route('transactionCategory-view.index') }}">
                            <i class="fa fa-arrow-right text-color mx-2" style="font-size:12px;"></i>
                        </a>
                        <div class="value-line is-loading">
                            <span class="price-default value-real" id="kpi-receber"></span>
                            <div class="preloader-values preloader-values-sm"></div>
                        </div>
                    </div>
                </div>

                {{-- A pagar --}}
                <div class="d-flex justify-content-between">
                    <b class="text-muted dash-amounts">A pagar</b>
                    <div class="d-flex align-items-center">
                        <a href="{{ route('transactionCategory-view.index') }}">
                            <i class="fa fa-arrow-right text-danger mx-2" style="font-size:12px;"></i>
                        </a>
                        <div class="value-line is-loading">
                            <span class="price-default value-real" id="kpi-pagar"></span>
                            <div class="preloader-values preloader-values-sm"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end align-items-center mt-3">
                <a href="{{ url()->current() . '?month=' . $startOfMonth->format('Y-m') }}"
                   class="text-muted fw-bold" style="text-decoration: none; font-size: 13px;">
                    Extrato do mês<i class="fa fa-chevron-right mx-2" style="font-size: 12px;"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Carousel horizontal -->
    <div class="icons-carousel">
        <div class="icon-button">
            <a href="{{ route('account-view.index') }}" class="nav-link-atalho">
                <i class="fas fa-landmark"></i>
                Bancos
            </a>
        </div>
        <div class="icon-button">
            <a href="{{ route('transaction-view.index') }}" class="nav-link-atalho">
                <i class="fa-solid fa-cart-plus"></i>
                <span>Transações</span>
            </a>
        </div>
        <div class="icon-button">
            <a href="{{ route('card-view.index') }}" class="nav-link-atalho">
                <i class="fas fa-credit-card"></i><span>Cartões</span></a>
        </div>

        <div class="icon-button">
            <a href="{{ route('investment-view.index') }}" class="nav-link-atalho">
                <i class="fas fa-chart-line"></i>
                <span>Investimentos</span>
            </a>
        </div>

        <div class="icon-button">
            <a href="{{ route('saving-view.index') }}" class="nav-link-atalho">
                <i class="fas fa-piggy-bank"></i><span>Cofrinhos</span></a>
        </div>

        <div class="icon-button">
            <a href="{{route('projection-view.index')}}" class="nav-link-atalho">
                <i class="fas fa-calendar"></i>
                <span>Projeção</span>
            </a>
        </div>
    </div>
    <div class="">
        <div id="calendar"></div>
    </div>

    <div class="py-3" id="calendar-results"></div>

    <!-- Next Payment -->
    <div class="next-payments mb-4">
        <h2>Próximos pagamentos</h2>

        @forelse($upcomingAny as $item)
            <div class="transaction-card" data-invoice-card>
                <div class="transaction-info">
                    <div class="icon text-white" style="background: {{ $item['color'] }};">
                        <i class="{{ $item['icon'] }}"></i>
                    </div>
                    <div class="details">
                        {{ $item['title'] }}
                        <br>
                        <span>{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</span>
                    </div>
                </div>

                <div class="d-flex align-items-center">
                    <div class="transaction-amount price-default mx-3">
                        - {{ brlPrice($item['amount']) }}
                    </div>

                    @if($item['kind'] === 'tx')
                        {{-- botão que abre modal de pagamento de transação --}}
                        <button type="button"
                                class="bg-transparent border-0"
                                data-open-payment
                                data-id="{{ $item['modal_id'] }}"
                                data-amount="{{ $item['modal_amount'] }}"
                                data-date="{{ \Carbon\Carbon::parse($item['modal_date'])->format('Y-m-d') }}"
                                data-title="{{ e($item['title']) }}">
                            <i class="fa-solid fa-check-to-slot text-success"></i>
                        </button>
                    @else
                        {{-- botão que marca fatura como paga (sem modal) --}}
                        <button type="button"
                                class="bg-transparent border-0"
                                data-pay-invoice
                                data-card="{{ $item['card_id'] }}"
                                data-month="{{ $item['current_month'] }}"
                                data-amount="{{ $item['amount'] }}"
                                data-title="{{ e($item['title']) }}">
                            <i class="fa-solid fa-check text-success"></i>
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

    <!-- Recent Transactions -->
    <div class="recent-transactions mb-4">
        <h2>Transações recentes</h2>
        @forelse($recentTransactions as $transaction)
            @php
                $categoryType = optional($transaction->transactionCategory)->type;
                $categoryName = optional($transaction->transactionCategory)->name;
            @endphp

            <div class="transaction-card">
                <div class="transaction-info">
                    <div class="icon" style="background: {{$transaction->transactionCategory->color}}">
                        <i class="{{$transaction->transactionCategory->icon}}"></i>
                    </div>

                    <div class="details">
                        <p class="tx-title m-0 p-0">{{ ucwords($transaction->title) ?? ucwords($categoryName) }}</p>
                        @if($transaction->type == 'card')
                            <small class="text-muted" style="font-size: 10.5px;">No cartão</small>
                        @endif
                        @if($transaction->date)
                            <span
                                class="tx-date mt-2">{{ \Carbon\Carbon::parse($transaction->date)->format('d/m/Y') }}</span>
                        @endif
                    </div>
                </div>
                <div class="transaction-amount price-default">
                    {{ $categoryType === 'despesa' ? '-' : '+' }}
                    {{ brlPrice($transaction->amount) }}
                </div>
            </div>
        @empty
            <p class="text-muted">Nenhuma transação encontrada</p>
        @endforelse
    </div>

    <h2 class="card-invoice-title">Faturas atuais</h2>
    <div class="balance-box">
        @if($cardTip)
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-credit-card" style="color: {{ $cardTip['color'] }}"></i>
                <small class="mx-3 text-dark">{{ $cardTip['label'] }}</small>
            </div>
        @endif
    </div>

    @foreach($currentInvoices as $inv)
        <div class="balance-box mt-2">
            <div class="d-flex justify-content-between">
                <span>{{ $inv['title'] }}</span>
            </div>
            <strong style="font-size: 18px;">{{ $inv['total_brl'] }}</strong>

            @if(!is_null($inv['available_limit']))
                <div class="d-flex justify-content-between">
                    <span>Limite disponível <b>{{ brlPrice($inv['available_limit']) }}</b></span>
                </div>
            @endif

            <div class="d-flex justify-content-between">
                <span>Vence em <b>{{ $inv['due_label'] }}.</b></span>
            </div>
        </div>
    @endforeach


    <!-- Modal -->
    <x-modal
        modalId="paymentModal"
        formId="paymentForm"
        pathForm="app.transactions.transaction_payment.transaction_payment_form"
        :data="[]"
    />

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

        function startLoading(...ids) {
            ids.forEach(id => {
                const box = document.getElementById(id)?.closest('.value-line');
                if (box) {
                    box.classList.add('is-loading');
                    box.classList.remove('loaded');
                }
            });
        }

        function finishLoading(...ids) {
            ids.forEach(id => {
                const box = document.getElementById(id)?.closest('.value-line');
                if (box) {
                    box.classList.remove('is-loading');
                    box.classList.add('loaded');
                }
            });
        }

        // parser BR/EN robusto
        function parseMoneyBR(input) {
            if (typeof input === 'number') return input;
            let s = String(input || '').trim();
            s = s.replace(/[^\d.,-]/g, '');
            const lastComma = s.lastIndexOf(','), lastDot = s.lastIndexOf('.');
            if (lastComma > -1 && lastDot > -1) {
                if (lastComma > lastDot) s = s.replace(/\./g, '').replace(',', '.'); // 1.234,56
                else s = s.replace(/,/g, '');                                        // 1,234.56
            } else if (lastComma > -1) {
                s = s.replace(',', '.');                                            // 123,45
            }
            return Number(s || 0);
        }

        const formatBRL = v => Number(v || 0).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});

        // abre modal com defaults da transação
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-open-payment]');
            if (!btn) return;

            window.CURRENT_ID = btn.dataset.id;                       // id da transação
            window.CURRENT_TITLE = btn.dataset.title || 'Pagamento';     // título real
            window.CURRENT_DUE_DATE = (btn.dataset.date || '').slice(0, 10); // <-- guardamos o DIA DO VENCIMENTO
            CURRENT_TX_CARD = btn.closest('.transaction-card') || null;

            paymentForm.action = PAY_TPL.replace('__ID__', window.CURRENT_ID);
            inAmount.value = btn.dataset.amount || '0';                 // aceita 69,99 ou 69.99
            inDate.value = window.CURRENT_DUE_DATE;                    // preenche com o vencimento (usuário pode trocar)

            showPaymentModal();
        });

        // submit do modal
        paymentForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(paymentForm);

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
                const payDate = (inDate.value || '').slice(0, 10);                   // dia escolhido para pagar (pode ser hoje)
                const dueDate = (window.CURRENT_DUE_DATE || payDate);               // dia original do vencimento
                const payAmount = parseMoneyBR(inAmount.value);                     // CORRIGIDO: 69,99 => 69.99

                // fecha modal e remove card (lista diária ou "Próximos pagamentos")
                hidePaymentModal();
                if (CURRENT_TX_CARD) {
                    CURRENT_TX_CARD.remove();
                    CURRENT_TX_CARD = null;
                }
                paymentForm.reset();

                // atualiza calendário em memória
                const cal = window.__cal;
                if (cal && cal.eventosCache) {
                    // 1) remove a DESPESA do DIA DO VENCIMENTO (bolinha vermelha)
                    const mapDue = cal.eventosCache[dueDate];
                    if (mapDue) {
                        for (const [k, ev] of mapDue.entries()) {
                            if (ev.tx_id === window.CURRENT_ID && ev.tipo === 'despesa') mapDue.delete(k);
                        }
                    }

                    // (defensivo) se por algum motivo a despesa já estivesse no dia do pagamento, limpa também
                    if (payDate !== dueDate) {
                        const mapPayRed = cal.eventosCache[payDate];
                        if (mapPayRed) {
                            for (const [k, ev] of mapPayRed.entries()) {
                                if (ev.tx_id === window.CURRENT_ID && ev.tipo === 'despesa') mapPayRed.delete(k);
                            }
                        }
                    }

                    // 2) adiciona o evento AZUL no DIA DO PAGAMENTO
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

                    // 3) redesenha pontos e a lista do dia atualmente selecionado
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
                const escAttr = s => String(s ?? '')
                    .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;').replace(/>/g, '&gt;');

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
                        // extras
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
                    startLoading('kpi-receber', 'kpi-pagar', 'kpi-balanco');

                    await loadWindow(ymStr, 2);
                    const [y, m] = ymStr.split('-').map(Number);

                    let aReceber = 0, aPagar = 0, balanco = 0;

                    for (const dia of diasDoMes(y, m)) {
                        for (const ev of eventosDoDia(dia)) {
                            if (ev.tipo === 'entrada') {
                                aReceber += +Math.abs(ev.valor);
                            } else if (ev.tipo === 'despesa') {
                                aPagar += -Math.abs(ev.valor);
                            }

                            balanco = Math.abs(aReceber) - Math.abs(aPagar);
                        }
                    }
                    const ymStr = document.getElementById('monthPicker').value;
                    const r = await fetch(`{{ route('dashboard.kpis') }}?month=${ymStr}`, {headers:{'Accept':'application/json'}});
                    const k = await r.json();
                    document.getElementById('kpi-receber').textContent = k.aReceber_brl;
                    document.getElementById('kpi-pagar').textContent   = k.aPagar_brl;
                    document.getElementById('kpi-balanco').textContent = k.saldoMes_brl;

                    finishLoading('kpi-receber', 'kpi-pagar', 'kpi-balanco');
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

                        let amountHtml = ev.valor_brl;
                        let sinal = '';
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
                        } else if (ev.tipo === 'despesa' && ev.tx_id) {
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

                            // verde: entradas | vermelho: despesas em aberto (inclui faturas não pagas)
                            // azul: investimentos/pagamentos/faturas pagas
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
