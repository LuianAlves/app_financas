@extends('layouts.templates.app')
@section('content')
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    @endpush

    <button class="btn btn-sm btn-light d-none" data-install>Instalar app</button>
    <script src="{{asset('assets/js/install.js')}}" defer></script>

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
                <span>Projeção do mês</span>
                <i class="fa fa-eye"></i>
            </div>

            {{-- Saldo do mês --}}
            <div class="shimmer is-loading shimmer--xl">
                <strong id="kpi-balanco" style="font-size:36px"></strong>
            </div>

            <div class="d-flex justify-content-between flex-column">
                <div class="row">
                    <!-- Saldo contas -->
                    <div class="col-12">
                        <b class="text-primary dash-amounts">Saldo contas</b>
                        <div class="d-flex align-items-center" style="height: 15px !important;">
                            <div class="shimmer is-loading">
                                <span id="kpi-contas" class="price-default d-flex justify-content-between"></span>
                            </div>
                        </div>
                    </div>

                    <!-- A receber -->
                    <div class="col-12 my-2">
                        <b class="text-color dash-amounts">A receber</b>
                        <div class="d-flex align-items-center" style="height: 15px !important;">
                            <div class="shimmer is-loading">
                                <span id="kpi-receber" class="price-default d-flex justify-content-between"></span>
                            </div>
                        </div>
                    </div>

                    <!-- A pagar -->
                    <div class="col-12">
                        <b class="text-danger dash-amounts">A pagar</b>
                        <div class="d-flex align-items-center" style="height: 15px !important;">
                            <div class="shimmer is-loading">
                                <span id="kpi-pagar" class="price-default d-flex justify-content-between"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end align-items-center mt-3">
                <a href="{{ url()->current() . '?month=' . $startOfMonth->format('Y-m') }}"
                   class="text-color fw-bold" style="text-decoration: none; font-size: 13px;">
                    Extrato do mês
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
