@extends('layouts.templates.app')
@section('content')
    <x-card-header
        prevRoute="{{route('dashboard')}}"
        iconRight="fa-solid fa-circle-question"
        title="Investimentos"
        description="Cadastre seus ativos e acompanhe posição, PM e P&L.">
    </x-card-header>

    <div id="investmentList" class="mt-4"></div>

    {{-- Botão flutuante: novo investimento --}}
    <button id="openInvestmentModal" class="create-btn" title="Novo investimento">
        <i class="fa fa-plus text-white"></i>
    </button>

    {{-- Atalho para transações/lançamentos de investimentos (opcional) --}}
    <a href="{{route('transaction-view.index')}}" class="create-btn create-other" title="Transações">
        <i class="fas fa-retweet text-white"></i>
    </a>

    {{-- Modal reusável no seu padrão --}}
    <x-modal modalId="modalInvestment" formId="formInvestment" pathForm="app.investments.investment_form"></x-modal>

    <script>
        // ====== Modal open/close (no padrão dos seus x-modal) ======
        const investmentModal = document.getElementById('modalInvestment');
        const openInvestmentBtn = document.getElementById('openInvestmentModal');
        const closeInvestmentBtn = document.getElementById('closeModal'); // vindo do x-modal

        openInvestmentBtn.addEventListener('click', () => {
            investmentModal.classList.add('show');
        });
        if (closeInvestmentBtn) {
            closeInvestmentBtn.addEventListener('click', () => {
                investmentModal.classList.remove('show');
            });
        }

        // ====== Helpers ======
        function brlPriceRaw(v) {
            if (v === null || v === undefined) return 'R$ 0,00';
            if (typeof v === 'string' && v.trim().startsWith('R$')) return v;
            const n = Number(v) || 0;
            return n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        // tenta converter "R$ 1.234,56" -> 1234.56
        function parseBRL(str) {
            if (typeof str === 'number') return str;
            if (typeof str !== 'string') return 0;
            const onlyNums = str.replace(/[^\d,.-]/g, '').replace(/\./g, '').replace(',', '.');
            return Number(onlyNums) || 0;
        }

        // Cálculo de posição no front (fallback se API não mandar pronto)
        function computePosition(trades = [], lastPriceRaw = 0) {
            let qty = 0, pm = 0, invested = 0, realized = 0;
            trades.forEach(t => {
                const side = String(t.side || '').toLowerCase().trim();
                const q = Number(t.quantity || 0);
                const p = Number(t.price || 0);
                const f = Number(t.fees || 0);
                if (side === 'buy') {
                    const totalCost = pm * qty + (p * q) + f;
                    qty += q;
                    pm = qty > 0 ? totalCost / qty : 0;
                    invested += (p * q) + f;
                } else if (side === 'sell') {
                    realized += ((p - pm) * q) - f;
                    qty -= q;
                    if (qty <= 0) { qty = 0; pm = 0; }
                }
            });
            const lastPrice = typeof lastPriceRaw === 'string' ? parseBRL(lastPriceRaw) : Number(lastPriceRaw || 0);
            const current = qty * lastPrice;
            const unrealized = qty > 0 ? (lastPrice - pm) * qty : 0;
            return { qty, pm, invested, realized, current, unrealized, lastPrice };
        }

        // ====== Submit do formulário (create) ======
        document.getElementById('formInvestment').addEventListener('submit', async function (e) {
            e.preventDefault();
            const form = e.target;
            const data = new FormData(form);

            try {
                const response = await fetch("{{ route('investments.store') }}", {
                    method: "POST",
                    headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                    body: data
                });
                if (!response.ok) throw new Error('Erro ao salvar investimento.');
                const novo = await response.json();

                // Fecha modal e limpa form
                investmentModal.classList.remove('show');
                form.reset();

                // Render do novo card
                renderInvestmentCard(novo, true);
            } catch (err) {
                alert(err.message);
            }
        });

        // ====== Carregamento inicial via API ======
        async function carregarInvestimentos() {
            try {
                const response = await fetch("{{ route('investments.index') }}");
                if (!response.ok) throw new Error('Erro ao carregar investimentos.');
                const list = await response.json();

                const container = document.getElementById('investmentList');
                container.innerHTML = '';

                (list || []).forEach(inv => renderInvestmentCard(inv, false));
            } catch (err) {
                alert(err.message);
            }
        }

        // ====== Render de cada card ======
        function renderInvestmentCard(inv, prepend = false) {
            const container = document.getElementById('investmentList');

            // Se a API já manda pronto (strings BRL), usamos; senão computamos
            const hasComputed = inv.current_total || inv.invested_total || inv.pl_unrealized || inv.pl_realized;

            let ticker = (inv.ticker || '').toString().toUpperCase();
            let name   = (inv.name || '') || '-';
            let klass  = (inv.class || '-')?.toString().toUpperCase();

            let qty, pm, priceNow, current, invested, unrealized, realized;

            if (hasComputed) {
                // API já formatou
                current   = inv.current_total;   // string "R$ ..."
                invested  = inv.invested_total;
                unrealized= inv.pl_unrealized;
                realized  = inv.pl_realized;

                // Se vierem numéricos brutos também:
                if (!String(current).includes('R$')) current = brlPriceRaw(current);
                if (!String(invested).includes('R$')) invested = brlPriceRaw(invested);
                if (!String(unrealized).includes('R$')) unrealized = brlPriceRaw(unrealized);
                if (!String(realized).includes('R$')) realized = brlPriceRaw(realized);
            } else {
                // Calcula no front a partir das trades e do last_price
                const trades = inv.trades || [];
                const lastPriceRaw = inv.last_price; // pode vir "R$ ..." ou number
                const pos = computePosition(trades, lastPriceRaw);
                qty       = pos.qty;
                pm        = pos.pm;
                priceNow  = pos.lastPrice;

                current   = brlPriceRaw(pos.current);
                invested  = brlPriceRaw(pos.invested);
                unrealized= brlPriceRaw(pos.unrealized);
                realized  = brlPriceRaw(pos.realized);
            }

            const badgeUnrealClass = parseBRL(unrealized) >= 0 ? 'text-success' : 'text-danger';
            const badgeRealClass   = parseBRL(realized)  >= 0 ? 'text-success' : 'text-danger';

            const card = `
                <div class="balance-box">
                    <span>${ticker} — ${name}</span>
                    <strong>${current}</strong>

                    <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
                        <small>
                            <b class="text-muted">Investido</b>
                            <div class="d-flex align-items-center">
                                <span>${invested}</span>
                            </div>
                        </small>

                        <small>
                            <b class="text-muted">PL não realizado</b>
                            <div class="d-flex align-items-center">
                                <span class="${badgeUnrealClass}">${unrealized}</span>
                            </div>
                        </small>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-2 mb-1">
                        <small>
                            <b class="text-muted">PL realizado</b>
                            <div class="d-flex align-items-center">
                                <span class="${badgeRealClass}">${realized}</span>
                            </div>
                        </small>

                        <a href="#" class="text-color fw-bold" style="text-decoration: none; font-size: 13px;">
                            Ver Extrato
                        </a>
                    </div>
                </div>
            `;

            if (prepend) {
                container.insertAdjacentHTML('afterbegin', card);
            } else {
                container.insertAdjacentHTML('beforeend', card);
            }
        }

        // Carrega ao abrir
        window.addEventListener('DOMContentLoaded', carregarInvestimentos);
    </script>
@endsection
