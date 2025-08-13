@extends('layouts.templates.app')
@section('content')
    <x-card-header prevRoute="{{ route('card-view.index') }}" iconRight="fa-solid fa-credit-card" title="" description=""></x-card-header>

    {{-- Carrossel de meses --}}
    <div class="icons-carousel" id="months">
        @foreach($invoices as $inv)
            <button class="icon-button nav-link-atalho month-btn {{ $selectedYm === $inv->ym ? 'active' : '' }}"
                    data-ym="{{ $inv->ym }}">
                <span class="bg-{{ $inv->paid ? 'success' : 'danger' }}">{{ $inv->month }}</span>
                <b>{{ $inv->total }}</b>
            </button>
        @endforeach
    </div>

    {{-- Header da fatura selecionada --}}
    <div class="balance-box" id="invoiceHeader" data-card="{{ $card->id }}">
        <span class="fw-bold">Fatura <span id="hdr-month">{{ $header['month_label'] }}</span></span>
        <strong id="hdr-total">{{ $header['total'] }}</strong>
        <span>Limite disponível <b id="hdr-limit">{{ $header['limit'] }}</b></span>
        <span class="closing-date" id="hdr-close">{!! $header['close_label'] !!}</span>
        <span class="due-date" id="hdr-due">{!! $header['due_label'] !!}</span>
    </div>

    {{-- Lista de itens da fatura --}}
    <div id="invoiceItems">
        @foreach($items as $it)
            <div class="transaction-card">
                <div class="transaction-info">
                    <div class="icon"><i class="fas fa-arrow-down text-white"></i></div>
                    <div class="details">
                        {{ $it->title }}<br><span>{{ $it->date }}</span>
                    </div>
                </div>
                <div class="transaction-amount">
                    @if($it->installments > 1)
                        <small>{{ $it->current_installment }}/{{ $it->installments }}</small>
                    @endif
                    {{ $it->amount }}
                    @if($it->is_projection) <small>(proj.)</small> @endif
                </div>
            </div>
        @endforeach
    </div>
@endsection

@push('styles')
    <style>
        .icons-carousel{padding:16px;margin-bottom:10px;gap:40px;display:flex;overflow:auto}
        .icon-button{background:transparent;border:0;cursor:pointer;text-align:center}
        .icon-button.active span{outline:2px solid #0bb;}
        .icon-button span{background:#e74c3c;color:#fff;border-radius:50%;padding:12px;margin:10px;width:40px;height:40px;display:flex;justify-content:center;align-items:center;font-size:12px}
        .balance-box{height:auto;display:flex;flex-direction:column;gap:4px;margin:8px 12px}
        .transaction-card{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border-radius:10px;background:#fff;margin:6px 12px;border:1px solid #eee}
        .transaction-info{display:flex;gap:10px;align-items:center}
        .transaction-info .icon{width:30px;height:30px;border-radius:6px;background:#999;display:flex;align-items:center;justify-content:center}
        .transaction-amount{text-align:right}
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const months = document.getElementById('months');
            const hdrMonth = document.getElementById('hdr-month');
            const hdrTotal = document.getElementById('hdr-total');
            const hdrLimit = document.getElementById('hdr-limit');
            const hdrClose = document.getElementById('hdr-close');
            const hdrDue   = document.getElementById('hdr-due');
            const cardId   = document.getElementById('invoiceHeader').dataset.card;
            const itemsBox = document.getElementById('invoiceItems');

            months.addEventListener('click', async (e) => {
                const btn = e.target.closest('.month-btn');
                if (!btn) return;
                const ym = btn.dataset.ym;

                // ativa visual
                months.querySelectorAll('.month-btn').forEach(b=>b.classList.remove('active'));
                btn.classList.add('active');

                // fetch
                const url = `{{ url('/invoice') }}/${cardId}/${ym}`;
                const res = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}});
                if (!res.ok) return;
                const data = await res.json();

                // header
                hdrMonth.textContent = data.header.month_label;
                hdrTotal.textContent = data.header.total;
                hdrLimit.innerHTML   = data.header.limit;
                hdrClose.innerHTML   = data.header.close_label;
                hdrDue.innerHTML     = data.header.due_label;

                // itens
                itemsBox.innerHTML = '';
                if (data.items.length === 0) {
                    itemsBox.innerHTML = '<div class="p-3 text-muted">Sem lançamentos neste mês.</div>';
                } else {
                    data.items.forEach(it => {
                        const proj = it.is_projection ? '<small>(proj.)</small>' : '';
                        const inst = it.installments > 1 ? `<small>${it.current_installment}/${it.installments}</small> ` : '';
                        itemsBox.insertAdjacentHTML('beforeend', `
          <div class="transaction-card">
            <div class="transaction-info">
              <div class="icon"><i class="fas fa-arrow-down text-white"></i></div>
              <div class="details">${it.title}<br><span>${it.date}</span></div>
            </div>
            <div class="transaction-amount">${inst}${it.amount} ${proj}</div>
          </div>
        `);
                    });
                }
            });
        });
    </script>
@endpush
