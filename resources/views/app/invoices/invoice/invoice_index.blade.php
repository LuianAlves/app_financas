@extends('layouts.templates.app')
@section('new-content')
    <x-card-header prevRoute="{{ route('card-view.index') }}" iconRight="" title=""
                   description=""></x-card-header>

    {{-- Carrossel de meses --}}
    <div class="icons-carousel" id="months">
        @foreach($invoices as $inv)
            <button class="icon-button nav-link-atalho month-btn {{ $selectedYm === $inv->ym ? 'active' : '' }}" data-ym="{{ $inv->ym }}">
                <span class="bg-{{ $inv->paid ? 'success' : 'danger' }}">{{ $inv->month }}</span>
                <b>{{ $inv->total }}</b>
            </button>
        @endforeach
    </div>

    {{-- Header da fatura selecionada --}}
    <div class="balance-box m-0 mb-3" id="invoiceHeader" data-card="{{ $card->id }}">
        <div class="d-flex align-items-center justify-content-between">
            <span class="fw-bold" id="hdr-month">Fatura de {{ $header['month_label'] }}</span>
            <a href="{{route('invoice-payment.update', [$card->id, $header['ym']])}}">
                <i class="fa-solid fa-check-to-slot fs-5" style="color: #2563eb;"></i>

            </a>
        </div>
        <strong id="hdr-total">{{ $header['total'] }}</strong>
        <span>Limite disponível <b id="hdr-limit">{{ $header['limit'] }}</b></span>
        <span class="closing-date" id="hdr-close">{!! $header['close_label'] !!}</span>
        <span class="due-date" id="hdr-due">{!! $header['due_label'] !!}</span>
    </div>

    <ul id="invoiceItems" class="swipe-list"></ul>

    <x-modal
        modalId="modalInvoiceItem"
        formId="formInvoiceItem"
        pathForm="app.invoices.invoice_item.invoice_item_form"
        :data="[]"
    />

    <div id="confirmDeleteItem" class="x-confirm" hidden>
        <div class="x-sheet" role="dialog" aria-modal="true" aria-labelledby="xConfirmTitle2">
            <div class="x-head">
                <h5 id="xConfirmTitle2">Remover item</h5>
                <button type="button" class="x-close" data-action="cancel" aria-label="Fechar">×</button>
            </div>
            <div class="x-body">Deseja remover este item da fatura?</div>
            <div class="x-actions">
                <button type="button" class="btn btn-light" data-action="cancel">Cancelar</button>
                <button type="button" class="btn btn-danger" data-action="confirm">Excluir</button>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* ===== Carrossel de Meses ===== */
        .icons-carousel {
            display: flex;
            gap: 14px;
            padding: 8px 10px;
            margin-bottom: 20px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
        }

        .icons-carousel::-webkit-scrollbar {
            display: none;
        }

        .icons-carousel .icon-button {
            flex: 0 0 auto;
            width: 80px;
            border-radius: 16px;
            padding: 14px 10px;
            text-align: center;
            transition: all .25s ease;
            scroll-snap-align: center;
            transform: translateY(0);
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            box-shadow: 0 3px 8px var(--card-shadow);
        }

        .icons-carousel .icon-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px var(--hover-shadow);
        }

        .icons-carousel .icon-button span {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            margin: 0 auto 8px auto;
            border-radius: 50%;
            font-size: 10px;
            font-weight: 500;
            background: var(--circle-bg);
            color: var(--circle-text);
            transition: all .25s ease;
        }

        .icons-carousel .icon-button b {
            font-size: 10px;
            font-weight: 500;
            color: var(--text-primary);
            display: block;
        }

        .icons-carousel .icon-button.active {
            background: var(--brand);
            border-color: var(--brand);
            box-shadow: 0 6px 14px rgba(37, 99, 235, 0.35);
            transform: scale(1.08);
        }

        .icons-carousel .icon-button.active span {
            background: #fff;
            color: var(--brand);
            font-weight: 700;
        }

        .icons-carousel .icon-button.active b {
            color: #fff;
        }

        /* ===== Box da Fatura ===== */
        .balance-box {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 18px;
            border-radius: 18px;
            background: var(--card-bg);
            box-shadow: 0 4px 14px var(--card-shadow);
            margin-bottom: 20px;
            transition: all .25s ease;
        }

        .balance-box strong {
            font-size: 22px;
            font-weight: 700;
            color: var(--brand);
        }

        .balance-box span {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .balance-box span b {
            font-weight: 600;
        }

        /* ===== Lista ===== */
        .swipe-item {
            border-radius: 14px;
            border: 1px solid var(--card-border);
            background: var(--card-bg);
            box-shadow: 0 2px 8px var(--card-shadow);
            overflow: hidden;
            transition: transform .25s ease;
        }

        .swipe-item:hover {
            transform: scale(1.01);
        }

        .tx-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            place-items: center;
        }

        .title-date {
            display: flex;
            flex-direction: column;
            align-items: flex-start; /* texto alinhado à esquerda */
            gap: 2px; /* espaço entre título e data */
            place-items: center;
        }

        .tx-title {
            font-weight: 600;
            font-size: 14px;
            color: var(--text-primary);
            line-height: 1.3;
            text-transform: capitalize
        }

        .tx-date {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .amount-box {
            text-align: right;
            min-width: 90px; /* garante espaço fixo */
        }

        .tx-amount {
            font-weight: 700;
            font-size: 15px;
            color: var(--brand);
        }

        /* container do ícone redondinho */
        .icon-circle{
            display: inline-grid;
            place-items: center;            /* centraliza em X e Y */
            width: 32px; height: 32px;
            flex: 0 0 32px;
            border-radius: 50%;
            background: var(--cat-bg, var(--brand));
            color:#fff;
            font-size:14px;
            line-height:1;
            vertical-align: middle;
        }


        /* opcional: um espacinho entre o ícone e o texto */
        .d-flex.align-items-center { gap: 8px; }





        /* ===== Tema Claro ===== */
        :root {
            --brand: #2563eb;
            --card-bg: #ffffff;
            --card-border: #e5e7eb;
            --card-shadow: rgba(0, 0, 0, 0.05);
            --hover-shadow: rgba(0,0,0,.1);
            --circle-bg: #3b82f6;
            --circle-text: #ffffff;
            --text-primary: #111827;
            --text-secondary: #374151;
        }

        /* ===== Tema Escuro ===== */
        .dark {
            --brand: #3b82f6;
            --card-bg: #1f2937;
            --card-border: #374151;
            --card-shadow: rgba(0, 0, 0, 0.3);
            --hover-shadow: rgba(0,0,0,.4);
            --circle-bg: #2563eb;
            --circle-text: #ffffff;
            --text-primary: #f9fafb;
            --text-secondary: #d1d5db;
        }


    </style>
@endpush

@push('scripts')
    <script>
        (() => {
            const months   = document.getElementById('months');
            const itemsBox = document.getElementById('invoiceItems');
            const hdrMonth = document.getElementById('hdr-month');
            const hdrTotal = document.getElementById('hdr-total');
            const hdrLimit = document.getElementById('hdr-limit');
            const hdrClose = document.getElementById('hdr-close');
            const hdrDue   = document.getElementById('hdr-due');
            const cardId   = document.getElementById('invoiceHeader').dataset.card;

            const modalEl  = document.getElementById('modalInvoiceItem');
            const form     = document.getElementById('formInvoiceItem');

            const CSRF     = '{{ csrf_token() }}';

            // ===== Render sem editar/excluir
            function renderItem(it) {
                const inst  = (it.installments > 1) ? `<small>${it.current_installment}/${it.installments}</small> ` : '';
                const proj  = it.is_projection ? '<small>(proj.)</small>' : '';
                const date  = it.date ?? '';
                const amount= it.amount;

                const iconCls = it.icon && it.icon.trim() ? it.icon : 'fa-solid fa-tag';
                const bg      = it.color || '#999';

                return `
              <li class="swipe-item">
                  <div class="swipe-content">
                    <div class="tx-line">

                      <!-- Esquerda: ícone + infos -->
                      <div class="d-flex align-items-center">
                              <i class="${iconCls} icon-circle" style="--cat-bg:${bg}"></i>
                                <span class="tx-title">${it.title ?? 'Sem título'}</span>
                                <small class="tx-date">${date}</small>
                      </div>

                      <div class="amount-box">
                        <span class="tx-amount price-default">${inst}${amount} ${proj}</span>
                      </div>

                    </div>
                  </div>
                </li>`;
            }

            function paintList(list) {
                itemsBox.innerHTML = list.map(renderItem).join('')
                    || '<div class="p-3 text-muted">Sem lançamentos neste mês.</div>';
            }

            // ===== Click item apenas abre em modo show
            itemsBox.addEventListener('click', async (e) => {
                const content = e.target.closest('.swipe-content');
                if (!content) return;
                const li = content.closest('.swipe-item');
                if (!li) return;

                // aqui você pode abrir modal em modo leitura
                // openModal('show', it); se ainda quiser mostrar detalhes
            });

            // ===== Carrossel de meses
            months.addEventListener('click', async (e) => {
                const btn = e.target.closest('.month-btn');
                if (!btn) return;
                const ym = btn.dataset.ym;
                months.querySelectorAll('.month-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const url = `{{ url('/invoice') }}/${cardId}/${ym}`;
                const res = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
                if (!res.ok) return;
                const data = await res.json();

                hdrMonth.textContent = 'Fatura de ' + data.header.month_label;
                hdrTotal.textContent = data.header.total;
                hdrLimit.innerHTML   = data.header.limit;
                hdrClose.innerHTML   = data.header.close_label;
                hdrDue.innerHTML     = data.header.due_label;

                paintList(data.items || []);
            });

            // ===== Boot inicial
            (function bootstrapFromServer() {
                const initial = [
                        @foreach($items as $it)
                    {
                        uuid: "{{ $it->uuid ?? '' }}",
                        id: "{{ $it->id ?? '' }}",
                        title: `{!! addslashes($it->title) !!}`,
                        date: "{{ $it->date }}",
                        amount: "{{ $it->amount }}",
                        installments: {{ (int)($it->installments ?? 0) }},
                        current_installment: {{ (int)($it->current_installment ?? 0) }},
                        is_projection: {{ $it->is_projection ? 'true':'false' }},
                        icon: "{{ $it->icon ?? '' }}",
                        color: "{{ $it->color ?? '#999' }}"
                    },
                    @endforeach
                ];
                paintList(initial);
            })();

        })();
    </script>
@endpush

