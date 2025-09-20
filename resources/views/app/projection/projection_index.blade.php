{{-- resources/views/projection/statement.blade.php --}}
@extends('layouts.templates.app')

@push('styles')
    <style>
        :root {
            --bg: #F6F7FA;
            --card: #FFF;
            --ink: #1F2937;
            --muted: #6B7280;
            --line: #E5E9F0;
            --soft: #F3F6FA;
            --pos: #1e00cf;
            --neg: #980000;
            --accent: #00BFA6;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html, body {
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: hidden !important;
            background: var(--bg);
            color: var(--ink);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
        }

        .st-wrap {
            display: grid;
            gap: 14px
        }

        /* Filtros compactos */
        .st-filters {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            flex-direction: column;
            padding: 8px 10px;
            border-radius: 10px;
            background: var(--card);
            border: 1px solid var(--line);
        }

        .st-filters label.k {
            font-size: .8rem;
            color: var(--muted);
            font-weight: 500;
            margin: 0 2px
        }

        .st-filters .form-control {
            height: 36px;
            border: 1px solid var(--line);
            border-radius: 8px;
            font-size: .85rem
        }

        .st-btn {
            border: 1px solid var(--line);
            background: #fff;
            border-radius: 7.5px;
            padding: 6px 12px;
            font-size: .8rem;
            font-weight: 500
        }

        .st-btn.active {
            border-color: var(--accent);
            background: var(--accent);
            color: #fff
        }

        #btnApply {
            margin-left: auto
        }

        /* Resumo topo */
        .sticky-summary {
            padding: 8px 12px;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 10px;
        }

        .sticky-summary > div {
            min-width: 0;
            display: flex;
            justify-content: space-between;
        }

        .sticky-summary .v {
            letter-spacing: .75px;
            font-size: 13.5px;
            font-weight: 600;
        }

        .sticky-summary #sumOpening {
            color: green;
        }

        .sticky-summary #sumIn {
            color: var(--pos)
        }

        .sticky-summary #sumOut {
            color: var(--neg)
        }

        .sticky-summary #sumEnd {}

        @media (max-width: 768px) {
            .sticky-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                row-gap: 6px
            }
        }

        @media (max-width: 380px) {
            .sticky-summary {
                grid-template-columns: 1fr
            }
        }

        /* Grupo/mês */
        .month-group {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 15px;
            box-shadow: 1px 1px 10px rgba(0, 0, 0, 0.1);
        }

        .month-head {
            padding: 10px 12px;
            background: #fff;
            font-size: .8rem;
            font-weight: 600;
            color: var(--muted);
        }

        .month-head .amt.pos {
            color: var(--pos)
        }

        .month-head .amt.neg {
            color: var(--neg)
        }

        /* Tabela estilo extrato */
        .table-wrap {
            width: 100%
        }

        .table-head, .tr {
            display:grid;
            grid-template-columns:130px 1fr 130px;
            align-items:center;
        }

        .table-head {
            background: #00bfa6;
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
            padding: 8px 12px;
        }

        .tr {
            padding: 10px 12px;
            border-bottom: 1px solid var(--line);
            background: #fbfdff;
        }

        .tr:hover {
            background: #fbfdff
        }

        .td {
            min-width: 0
        }

        .col-date {
            color: #111827;
            font-weight: 500;
            font-size: .85rem;
        }

        .col-hist .title {
            font-weight: 600;
            font-size: .9rem;
            color: #111827
        }

        .col-hist .sub {
            margin-top: 2px;
            color: var(--muted);
            font-size: .75rem;
            display: flex;
            gap: 6px;
            flex-wrap: wrap
        }

        .col-amt {
            justify-self: end;
            font-weight: 600;
            font-size: 12.5px !important;
            letter-spacing: .5px !important;
        }

        .col-amt.pos {
            color: var(--pos)
        }

        .col-amt.neg {
            color: var(--neg)
        }

        .tr.saldo {
            background: #fff;
        }

        .tr.saldo .col-hist .title {
            font-weight: 500;
            font-size: 10px;
            letter-spacing: 0.75px;
            text-transform: uppercase;
        }

        .tr.saldo .col-amt {
            font-weight: 700
        }

        /* Badge minimalista */
        .badge-soft {
            font-size: .7rem;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 1px 6px;
            background: #fff;
            color: #64748B;
            font-weight: 500
        }

        /* Mobile: esconde Doc e ajusta colunas */
        @media (max-width: 640px) {
            .col-amt {
                font-size: .88rem
            }

            .col-hist .title {
                font-size: .88rem
            }

            .table-head{
                grid-template-columns:1fr 110px;
                grid-template-areas:"hist amt";
            }

            .table-head .col-date{display:none}
            .table-head .col-hist{grid-area:hist}

            .table-head .col-amt{grid-area:amt;justify-self:end}

            .tr{
                grid-template-columns:1fr 110px;
                grid-template-areas:
      "date date"
      "hist amt";
            }
            .col-date{
                grid-area:date;
                font-size: 11.5px;
                letter-spacing: .5px;
                opacity:.7;
                margin-bottom:7.5px;
                color: var(--muted);
            }

            .col-hist{grid-area:hist}
            .col-amt{grid-area:amt;justify-self:end}
        }
    </style>
@endpush

@section('new-content')
    <x-card-header
        prevRoute="{{ route('dashboard') }}"
        iconRight="fa-solid fa-credit-card"
        title="Projeção financeira — Extrato"
        description="Entradas, saídas e saldo acumulado no período selecionado.">
    </x-card-header>

    <div class="st-wrap mt-3">

        {{-- filtros --}}
        <div class="st-filters">
            <div class="row">
                <div class="col-6">
                    <label class="k">Início</label>
                    <input type="date" id="dtStart" class="form-control" style="max-width:170px">
                </div>

                <div class="col-6">
                    <label class="k">Fim</label>
                    <input type="date" id="dtEnd" class="form-control" style="max-width:170px">
                </div>
            </div>

            <div class="row mt-2">
                <div class="col">
                    <button class="st-btn" data-range="1">+1 mês</button>
                </div>
                <div class="col">
                    <button class="st-btn" data-range="3">+3 mês</button>
                </div>
                <div class="col">
                    <button class="st-btn" data-range="6">+6 mês</button>
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <button class="st-btn" data-range="12">+12 mês</button>
                </div>
                <div class="col">
                    <button class="st-btn" data-range="15">+15 mês</button>
                </div>
            </div>

            <button id="btnApply" class="btn bg-color border-none ms-auto">
                <i class="fa fa-magnifying-glass me-1" style="font-size: 12px;"></i>
                <span style="letter-spacing: .5px; font-size: 14px; margin-left: 2.5px;">Aplicar</span>
            </button>
        </div>

        {{-- resumo fixo --}}
        <div class="sticky-summary">
            <div>
                <div class="k">Saldo em conta</div>
                <div id="sumOpening" class="v">—</div>
            </div>
            <div>
                <div class="k">Entradas</div>
                <div id="sumIn" class="v">—</div>
            </div>
            <div>
                <div class="k">Saídas</div>
                <div id="sumOut" class="v">—</div>
            </div>
            <hr class="m-0 p-0 mt-2 pb-2 w-50">
            <div>
                <div class="k">Saldo final</div>
                <div id="sumEnd" class="v">—</div>
            </div>
        </div>

        <div id="statement">Carregando…</div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const fmtBRL = v => (Number(v) || 0).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
            const pad2 = n => String(n).padStart(2, '0');

            const $start = document.getElementById('dtStart');
            const $end = document.getElementById('dtEnd');
            const $apply = document.getElementById('btnApply');
            const $statement = document.getElementById('statement');

            const today = new Date();
            const defStart = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            const defEnd = new Date(today.getFullYear(), today.getMonth() + 3, today.getDate());
            $start.value = toISO(defStart);
            $end.value = toISO(defEnd);

            document.querySelectorAll('.st-btn[data-range]').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.st-btn[data-range]').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    const months = parseInt(btn.dataset.range, 10);
                    const s = new Date($start.value || today.toISOString().slice(0, 10));
                    const e = new Date(s);
                    e.setMonth(e.getMonth() + months);
                    $end.value = toISO(e);
                });
            });

            $apply.addEventListener('click', () => load($start.value, $end.value));
            load($start.value, $end.value);

            function toISO(d) {
                return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
            }

            function formatDate(iso) {
                const [y, m, d] = iso.split('-');
                return `${d}/${m}/${y}`;
            }

            async function load(start, end) {
                try {
                    $statement.innerHTML = 'Carregando…';
                    const url = new URL("{{ route('projection.data') }}", window.location.origin);
                    url.searchParams.set('start', start);
                    url.searchParams.set('end', end);
                    const r = await fetch(url, {headers: {'Accept': 'application/json'}});
                    if (!r.ok) throw new Error('Falha ao carregar projeção');
                    const data = await r.json();
                    renderSummary(data);
                    renderStatement(data.days, data.opening_balance);
                } catch (e) {
                    $statement.innerHTML = `<div class="alert alert-danger">${e.message}</div>`;
                }
            }

            function renderSummary(data) {
                document.getElementById('sumOpening').textContent = fmtBRL(data.opening_balance);
                document.getElementById('sumIn').textContent = fmtBRL(data.total_in);
                document.getElementById('sumOut').textContent = fmtBRL(data.total_out);
                document.getElementById('sumEnd').textContent = fmtBRL(data.ending_balance);
            }

            // ====== NOVO: monta “tabela” por mês e por dia ======
            function renderStatement(days, openingBalance) {
                if (!days?.length) {
                    $statement.innerHTML = '<div class="alert alert-info">Sem lançamentos no período.</div>';
                    return;
                }

                // agrupa por mês
                const byMonth = {};
                days.forEach(d => {
                    const ym = d.date.slice(0, 7);
                    (byMonth[ym] ||= []).push(d);
                });

                const months = Object.keys(byMonth).sort();
                const parts = [];

                months.forEach((ym, idxMonth) => {
                    const ds = byMonth[ym].sort((a, b) => a.date.localeCompare(b.date));
                    const [y, m] = ym.split('-');
                    const label = `${pad2(m)}/${y}`;

                    // cabeçalho do mês
                    const monthIn = ds.reduce((s, x) => s + Number(x.in || 0), 0);
                    const monthOut = ds.reduce((s, x) => s + Number(x.out || 0), 0);
                    const monthNet = monthIn - monthOut;

                    let html = `
        <div class="month-group">
          <div class="month-head">
            <div class="price-default mb-3">${label}</div>
            <div class="muted">
              <span class="d-flex justify-content-between price-default py-2">Entradas: <span class="amt pos">${fmtBRL(monthIn)}</span></span>
              <span class="d-flex justify-content-between price-default">Saídas: <span class="amt neg">${fmtBRL(monthOut)}</span></span> <hr class="m-0 p-0 mt-2 pb-2 w-50">
              <span class="d-flex justify-content-between price-default">Saldo mês: <span>${fmtBRL(monthNet)}</span></span>
            </div>
          </div>
          <div class="table-wrap">
            <div class="table-head">
              <div class="col-date text-white fw-bold" style="font-size: 13.5px !important;">Data</div>
              <div class="col-hist text-white fw-bold" style="font-size: 13.5px !important;">Transação</div>
              <div class="col-amt text-white fw-bold" style="font-size: 13.5px !important;">Valor</div>
            </div>
      `;

                    // primeira linha "Saldo anterior" apenas no primeiro mês exibido
                    if (idxMonth === 0) {
                        html += `
          <div class="tr saldo">
            <div class="td col-date"></div>
            <div class="td col-hist"><div class="title">Saldo anterior</div></div>
            <div class="td col-amt ${openingBalance >= 0 ? 'pos' : 'neg'}">${fmtBRL(openingBalance)}</div>
          </div>
        `;
                    }

                    // linhas por dia
                    ds.forEach(d => {
                        const dateLabel = formatDate(d.date);

                        // itens do dia
                        const items = (d.items || []);
                        items.forEach(it => {
                            const isPos = Number(it.amount) >= 0;
                            const badges = [];
                            if (it.is_invoice) badges.push('<span class="badge-soft">Fatura</span>');
                            if (it.type === 'pix') badges.push('<span class="badge-soft">PIX</span>');
                            if (it.type === 'money') badges.push('<span class="badge-soft">Dinheiro</span>');
                            if (it.type === 'card' && it.type_card) badges.push(`<span class="badge-soft">Cartão ${it.type_card}</span>`);
                            if (it.category) badges.push(`<span class="badge-soft">${it.category}</span>`);

                            html += `
            <div class="tr">
              <div class="td col-date">${dateLabel}</div>
              <div class="td col-hist">
                <div class="title">${escapeHtml(it.title || '—')}</div>
                ${badges.length ? `<div class="sub">${badges.join('')}</div>` : ''}
              </div>
              <div class="td col-amt ${isPos ? 'pos' : 'neg'}">${fmtBRL(it.amount)} ${isPos ? 'C' : 'D'}</div>
            </div>
          `;
                        });

                        // linha SALDO do dia
                        html += `
          <div class="tr saldo">
            <div class="td col-date">${dateLabel}</div>
            <div class="td col-hist"><div class="title">Saldo</div></div>
            <div class="td col-amt ${d.balance >= 0 ? 'pos' : 'neg'}">${fmtBRL(d.balance)}</div>
          </div>
        `;
                    });

                    html += `</div></div>`;
                    parts.push(html);
                });

                $statement.innerHTML = parts.join('');
            }

            function escapeHtml(s) {
                return String(s || '').replace(/[&<>"']/g, m => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                }[m]));
            }
        })();
    </script>
@endpush
