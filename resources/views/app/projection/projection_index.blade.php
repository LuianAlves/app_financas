{{-- resources/views/projection/statement.blade.php --}}
@extends('layouts.templates.app')

@push('styles')
    <style>
        :root{
            --bg:#F6F7FA; --card:#FFF; --ink:#1F2937; --muted:#6B7280;
            --line:#E5E9F0; --soft:#F3F6FA; --pos:#107C41; --neg:#C53030; --accent:#00BFA6;
        }
        body{background:var(--bg); color:var(--ink); font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;}
        .st-wrap{display:grid;gap:14px}

        /* Filtros compactos */
        .st-filters{
            display:flex;gap:10px;align-items:center;overflow:auto;
            padding:8px 10px;border-radius:10px;background:var(--card);border:1px solid var(--line);
        }
        .st-filters label.k{font-size:.8rem;color:var(--muted);font-weight:500;margin:0 2px}
        .st-filters .form-control{height:36px;border:1px solid var(--line);border-radius:8px;font-size:.85rem}
        .st-btn{border:1px solid var(--line);background:#fff;border-radius:999px;padding:6px 12px;font-size:.8rem;font-weight:500}
        .st-btn.active{border-color:var(--accent);background:var(--accent);color:#fff}
        #btnApply{margin-left:auto}

        /* Resumo topo */
        .sticky-summary{
            position:sticky;top:0;z-index:9;background:var(--card);
            border:1px solid var(--line);border-radius:10px;padding:8px 12px;
            display:grid;grid-template-columns:repeat(4,1fr);gap:8px;
        }
        .sticky-summary .k{font-size:.75rem;color:var(--muted);font-weight:400}
        .sticky-summary .v{font-size:.92rem;font-weight:600}

        /* Grupo/mês */
        .month-group{
            background:var(--card);
            border:1px solid var(--line);
            border-radius:10px;
            overflow:hidden;
            margin-top: 15px;
        }

        .month-head{
            padding:10px 12px;background:var(--soft);
            font-size:.8rem;font-weight:600;color:var(--muted);
            display:flex;justify-content:space-between;align-items:center
        }
        .month-head .amt.pos{color:var(--pos)} .month-head .amt.neg{color:var(--neg)}

        /* Tabela estilo extrato */
        .table-wrap{width:100%}
        .table-head, .tr{
            display:grid;grid-template-columns:120px 110px 1fr 130px;align-items:center
        }
        .table-head{
            background:#fff;border-top:1px solid var(--line);border-bottom:1px solid var(--line);
            padding:8px 12px;color:#475569;font-size:.75rem;font-weight:600;letter-spacing:.02em
        }
        .tr{
            padding:10px 12px;border-bottom:1px solid var(--line);background:#fff
        }
        .tr:hover{background:#fbfdff}
        .td{min-width:0}
        .col-date{color:#111827;font-weight:500;font-size:.85rem}
        .col-doc{color:#6B7280;font-size:.8rem}
        .col-hist .title{font-weight:600;font-size:.9rem;color:#111827}
        .col-hist .sub{margin-top:2px;color:var(--muted);font-size:.75rem;display:flex;gap:6px;flex-wrap:wrap}
        .col-amt{justify-self:end;font-weight:600;font-size:.9rem}
        .col-amt.pos{color:var(--pos)} .col-amt.neg{color:var(--neg)}

        /* Linha de SALDO (cinza, igual print) */
        .tr.saldo{background:#F1F3F7}
        .tr.saldo .col-hist .title{font-weight:700}
        .tr.saldo .col-amt{font-weight:700}

        /* Badge minimalista */
        .badge-soft{font-size:.7rem;border:1px solid var(--line);border-radius:999px;padding:1px 6px;background:#fff;color:#64748B;font-weight:500}

        /* Mobile: esconde Doc e ajusta colunas */
        @media (max-width:640px){
            .table-head, .tr{grid-template-columns:110px 1fr 110px}
            .table-head .col-doc, .tr .col-doc{display:none}
            .col-amt{font-size:.88rem}
            .col-hist .title{font-size:.88rem}
        }
    </style>
@endpush

@section('content')
    <x-card-header
        prevRoute="{{ route('dashboard') }}"
        iconRight="fa-solid fa-credit-card"
        title="Projeção financeira — Extrato"
        description="Entradas, saídas e saldo acumulado no período selecionado.">
    </x-card-header>

    <div class="st-wrap mt-3">
        {{-- filtros --}}
        <div class="st-filters">
            <label class="k">Início</label>
            <input type="date" id="dtStart" class="form-control" style="max-width:170px">
            <label class="k">Fim</label>
            <input type="date" id="dtEnd" class="form-control" style="max-width:170px">
            <button class="st-btn" data-range="1">+1m</button>
            <button class="st-btn" data-range="3">+3m</button>
            <button class="st-btn" data-range="6">+6m</button>
            <button class="st-btn" data-range="12">+12m</button>
            <button class="st-btn" data-range="15">+15m</button>
            <button id="btnApply" class="btn btn-primary ms-auto"><i class="fa fa-magnifying-glass me-1"></i>Aplicar</button>
        </div>

        {{-- resumo fixo --}}
        <div class="sticky-summary">
            <div><div class="k">Saldo inicial</div><div id="sumOpening" class="v">—</div></div>
            <div><div class="k">Entradas</div><div id="sumIn" class="v">—</div></div>
            <div><div class="k">Saídas</div><div id="sumOut" class="v">—</div></div>
            <div><div class="k">Saldo final</div><div id="sumEnd" class="v">—</div></div>
        </div>

        {{-- container do extrato --}}
        <div id="statement">Carregando…</div>
    </div>
@endsection

@push('scripts')
    <script>
        (function(){
            const fmtBRL = v => (Number(v)||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});
            const pad2 = n => String(n).padStart(2,'0');

            const $start = document.getElementById('dtStart');
            const $end   = document.getElementById('dtEnd');
            const $apply = document.getElementById('btnApply');
            const $statement = document.getElementById('statement');

            // datas padrão: hoje → +3 meses
            const today = new Date();
            const defStart = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            const defEnd   = new Date(today.getFullYear(), today.getMonth()+3, today.getDate());
            $start.value = toISO(defStart);
            $end.value   = toISO(defEnd);

            document.querySelectorAll('.st-btn[data-range]').forEach(btn=>{
                btn.addEventListener('click', ()=>{
                    document.querySelectorAll('.st-btn[data-range]').forEach(b=>b.classList.remove('active'));
                    btn.classList.add('active');
                    const months = parseInt(btn.dataset.range,10);
                    const s = new Date($start.value || today.toISOString().slice(0,10));
                    const e = new Date(s); e.setMonth(e.getMonth()+months);
                    $end.value = toISO(e);
                });
            });

            $apply.addEventListener('click', ()=> load($start.value, $end.value));
            load($start.value, $end.value);

            function toISO(d){ return d.getFullYear()+'-'+pad2(d.getMonth()+1)+'-'+pad2(d.getDate()); }
            function formatDate(iso){
                const [y,m,d] = iso.split('-');
                return `${d}/${m}/${y}`;
            }

            async function load(start, end){
                try{
                    $statement.innerHTML = 'Carregando…';
                    const url = new URL("{{ route('projection.data') }}", window.location.origin);
                    url.searchParams.set('start', start);
                    url.searchParams.set('end', end);
                    const r = await fetch(url, {headers:{'Accept':'application/json'}});
                    if(!r.ok) throw new Error('Falha ao carregar projeção');
                    const data = await r.json();
                    renderSummary(data);
                    renderStatement(data.days, data.opening_balance);
                }catch(e){
                    $statement.innerHTML = `<div class="alert alert-danger">${e.message}</div>`;
                }
            }

            function renderSummary(data){
                document.getElementById('sumOpening').textContent = fmtBRL(data.opening_balance);
                document.getElementById('sumIn').textContent      = fmtBRL(data.total_in);
                document.getElementById('sumOut').textContent     = fmtBRL(data.total_out);
                document.getElementById('sumEnd').textContent     = fmtBRL(data.ending_balance);
            }

            // ====== NOVO: monta “tabela” por mês e por dia ======
            function renderStatement(days, openingBalance){
                if(!days?.length){ $statement.innerHTML = '<div class="alert alert-info">Sem lançamentos no período.</div>'; return; }

                // agrupa por mês
                const byMonth = {};
                days.forEach(d=>{
                    const ym = d.date.slice(0,7);
                    (byMonth[ym] ||= []).push(d);
                });

                const months = Object.keys(byMonth).sort();
                const parts = [];

                months.forEach((ym, idxMonth) => {
                    const ds = byMonth[ym].sort((a,b)=>a.date.localeCompare(b.date));
                    const [y,m] = ym.split('-');
                    const label = `${pad2(m)}/${y}`;

                    // cabeçalho do mês
                    const monthIn  = ds.reduce((s,x)=>s+Number(x.in||0),0);
                    const monthOut = ds.reduce((s,x)=>s+Number(x.out||0),0);
                    const monthNet = monthIn - monthOut;

                    let html = `
        <div class="month-group">
          <div class="month-head">
            <div>${label}</div>
            <div class="muted">
              <span class="me-3">Entradas: <b class="amt pos">${fmtBRL(monthIn)}</b></span>
              <span class="me-3">Saídas: <b class="amt neg">${fmtBRL(monthOut)}</b></span>
              <span>Saldo mês: <b>${fmtBRL(monthNet)}</b></span>
            </div>
          </div>
          <div class="table-wrap">
            <div class="table-head">
              <div class="col-date">DATA MOV.</div>
              <div class="col-doc">NR. DOC.</div>
              <div class="col-hist">HISTÓRICO</div>
              <div class="col-amt">VALOR</div>
            </div>
      `;

                    // primeira linha "Saldo anterior" apenas no primeiro mês exibido
                    if (idxMonth === 0) {
                        html += `
          <div class="tr saldo">
            <div class="td col-date"></div>
            <div class="td col-doc"></div>
            <div class="td col-hist"><div class="title">SALDO ANTERIOR</div></div>
            <div class="td col-amt ${openingBalance>=0?'pos':'neg'}">${fmtBRL(openingBalance)}</div>
          </div>
        `;
                    }

                    // linhas por dia
                    ds.forEach(d=>{
                        const dateLabel = formatDate(d.date);

                        // itens do dia
                        const items = (d.items||[]);
                        items.forEach(it=>{
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
              <div class="td col-doc">—</div>
              <div class="td col-hist">
                <div class="title">${escapeHtml(it.title || '—')}</div>
                ${badges.length? `<div class="sub">${badges.join('')}</div>` : ''}
              </div>
              <div class="td col-amt ${isPos?'pos':'neg'}">${fmtBRL(it.amount)} ${isPos?'C':'D'}</div>
            </div>
          `;
                        });

                        // linha SALDO do dia
                        html += `
          <div class="tr saldo">
            <div class="td col-date">${dateLabel}</div>
            <div class="td col-doc"></div>
            <div class="td col-hist"><div class="title">Saldo</div></div>
            <div class="td col-amt ${d.balance>=0?'pos':'neg'}">${fmtBRL(d.balance)}</div>
          </div>
        `;
                    });

                    html += `</div></div>`;
                    parts.push(html);
                });

                $statement.innerHTML = parts.join('');
            }

            function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
        })();
    </script>
@endpush
