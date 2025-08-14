@extends('layouts.templates.app')
@section('content')
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

            <strong id="kpi-balanco">{{ $total }}</strong>

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

                <!-- Entradas -->
                <div class="d-flex justify-content-between">
                    <b class="text-muted dash-amounts">A receber</b>
                    <div class="d-flex align-items-center">
                        <a href="{{ route('transactionCategory-view.index') }}">
                            <i class="fa fa-arrow-right text-color mx-2" style="font-size: 12px;"></i>
                        </a>
                        <span class="price-default" id="kpi-receber">R$ 0,00</span>
                    </div>
                </div>

                <!-- A pagar -->
                <div class="d-flex justify-content-between">
                    <b class="text-muted dash-amounts">A pagar</b>
                    <div class="d-flex align-items-center">
                        <a href="{{ route('transactionCategory-view.index') }}">
                            <i class="fa fa-arrow-right text-danger mx-2" style="font-size: 12px;"></i>
                        </a>
                        <span class="price-default" id="kpi-pagar">R$ 0,00</span>
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
        @forelse($upcomingPayments as $payment)
            <div class="transaction-card">
                <div class="transaction-info">
                    <div class="icon text-white" style="background: {{$payment->transactionCategory->color}};">
                        <i class="{{$payment->transactionCategory->icon}}"></i>
                    </div>
                    <div class="details">
                        {{ $payment->title ?? $payment->category_name }}
                        <br>
                        <span>{{ \Carbon\Carbon::parse($payment->date)->format('d/m/Y') }}</span>
                    </div>
                </div>
                <div class="transaction-amount price-default">
                    - {{ brlPrice($payment->amount) }}
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
            <div class="d-flex">
                <i class="fa-solid fa-credit-card" style="color: {{ $cardTip['color'] ?? '#000' }}"></i>
                <small class="mx-3 text-dark">
                    {{ $cardTip['label'] }}
                </small>
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

                const eventosCache = {};
                const loadedWindows = new Set();

                function addEventToCache(ev) {
                    const day = String(ev.start).slice(0, 10);
                    const id = ev.id ?? `${ev.title}-${ev.start}`;
                    const item = {
                        id,
                        tipo: (ev.extendedProps?.type || '').toLowerCase().trim(),
                        color: ev.bg,
                        icon: ev.icon,
                        descricao: ev.title ?? ev.extendedProps?.category_name ?? 'Sem descrição',
                        valor: Number(ev.extendedProps?.amount ?? 0),
                        valor_brl: ev.extendedProps?.amount_brl
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

                            // balanco += ev.valor;

                            balanco = Math.abs(aReceber) - Math.abs(aPagar);
                        }
                    }

                    document.getElementById('kpi-receber').textContent = brl(aReceber);
                    document.getElementById('kpi-pagar').textContent = brl(aPagar);
                    document.getElementById('kpi-balanco').textContent = brl(balanco);
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
                        const sinal = ev.tipo === 'despesa' ? '-' : (ev.tipo === 'entrada' ? '+' : '');
                        html += `<div class="transaction-card">
        <div class="transaction-info">
          <div class="icon text-white" style="background-color:${ev.color}"><i class="${ev.icon}"></i></div>
          <div class="details">${ev.descricao}<br><span>${br(dateStr)}</span></div>
        </div>
        <div class="transaction-amount price-default">${sinal} ${ev.valor_brl}</div>
      </div>`;
                    }
                    c.innerHTML = html;
                }

                let fp;

                document.addEventListener('DOMContentLoaded', () => {
                    fp = flatpickr("#calendar", {
                        locale: 'pt', inline: true, defaultDate: "today", disableMobile: true,

                        onDayCreate: (_, _2, _3, dayElem) => {
                            const d = iso(dayElem.dateObj);
                            const evs = eventosDoDia(d);
                            if (!evs.length) return;
                            const wrap = document.createElement('div');
                            wrap.style.cssText = 'display:flex;justify-content:center;gap:2px;margin-top:-10px';
                            if (evs.some(e => e.tipo === 'entrada')) wrap.append(Object.assign(document.createElement('span'), {style: 'width:6px;height:6px;background:green;border-radius:50%'}));
                            if (evs.some(e => e.tipo === 'despesa')) wrap.append(Object.assign(document.createElement('span'), {style: 'width:6px;height:6px;background:red;border-radius:50%'}));
                            if (evs.some(e => e.tipo === 'investimento')) wrap.append(Object.assign(document.createElement('span'), {style: 'width:6px;height:6px;background:#0ea5e9;border-radius:50%'}));
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
