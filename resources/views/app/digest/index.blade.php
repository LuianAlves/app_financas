{{-- resources/views/app/digest/saving_index.blade.php --}}
@extends('layouts.templates.app')

@section('new-content')
    @push('styles')
        <style>
            h5  {
                letter-spacing: .75px;
                font-weight: 600;
                font-size: 16px;
            }
        </style>
    @endpush
    <x-card-header
        prevRoute="{{ route('dashboard') }}"
        iconRight="fa-solid fa-bell"
        title="Lançamentos"
        description="Veja os seus lançamentos de hoje e dos próximos dias!">
    </x-card-header>

    @php
        $fmt = fn($d) => \Carbon\Carbon::parse($d)->format('d/m/Y');

        // normaliza TRANSAÇÃO -> card
        $txToCard = function($t) use ($today, $fmt){
            $cat   = optional($t->transactionCategory);
            $type  = $cat->type; // 'entrada'|'despesa'|'investimento'
            $amt   = (float)$t->amount;
            $signed= $type === 'entrada' ? abs($amt) : -abs($amt);

            return [
                'bg'    => $cat->color ?: '#6b7280',
                'icon'  => $cat->icon  ?: 'fa-solid fa-receipt',
                'title' => $t->title ?? $cat->name ?? 'Lançamento',
                'date'  => $t->date ? \Carbon\Carbon::parse($t->date)->toDateString() : $today->toDateString(),
                'amt'   => $signed,
                'is_invoice' => false,
                'paid'  => false,
                'tx_id' => (string)$t->id,
            ];
        };

        // normaliza FATURA -> card
        $invToCard = fn($inv) => [
            'bg'    => '#be123c',
            'icon'  => 'fa-solid fa-credit-card',
            'title' => $inv['title'],
            'date'  => $inv['due_date'],
            'amt'   => -abs((float)$inv['total']),
            'is_invoice' => true,
            'paid'  => false,
            'card_id' => $inv['card_id'],
            'current_month' => $inv['current_month'],
        ];

        // monta listas “Hoje” e “Amanhã” já no mesmo formato de cartão
        $cardsToday = collect();
        if (isset($invoicesToday))  $cardsToday = $cardsToday->merge($invoicesToday->map($invToCard));
        $cardsToday = $cardsToday
            ->merge($todayIn->map($txToCard))
            ->merge($todayOut->map($txToCard))
            ->merge($todayInv->map($txToCard))
            ->sortBy('date')
            ->values();

        $cardsTomorrow = collect();
        if (isset($invoicesTomorrow)) $cardsTomorrow = $cardsTomorrow->merge($invoicesTomorrow->map($invToCard));
        $cardsTomorrow = $cardsTomorrow
            ->merge($tomIn->map($txToCard))
            ->merge($tomOut->map($txToCard))
            ->merge($tomInv->map($txToCard))
            ->sortBy('date')
            ->values();
    @endphp

    <div class="mt-3">

        {{-- ===== HOJE ===== --}}
        <h5 class="mb-2">Hoje ({{ $today->format('d/m/Y') }})</h5>
        @forelse($cardsToday as $c)
            <div class="transaction-card" data-invoice-card>
                <div class="transaction-info">
                    <div class="icon text-white" style="background: {{ $c['bg'] }}">
                        <i class="{{ $c['icon'] }}"></i>
                    </div>
                    <div class="details">
                        {{ $c['title'] }}<br>
                        <span>{{ $fmt($c['date']) }}</span>
                    </div>
                </div>

                <div class="d-flex align-items-center">
                    <div class="transaction-amount price-default mx-3">
                        {{ ($c['amt'] ?? 0) < 0 ? '-' : '+' }} {{ brlPrice(abs($c['amt'] ?? 0)) }}
                    </div>

                    @if($c['is_invoice'] && empty($c['paid']))
                        {{-- pagar fatura --}}
                        <button type="button" class="bg-transparent border-0"
                                data-pay-invoice
                                data-card="{{ $c['card_id'] }}"
                                data-month="{{ $c['current_month'] }}"
                                data-amount="{{ abs($c['amt']) }}"
                                data-title="{{ e($c['title']) }}">
                            <i class="fa-solid fa-check text-success"></i>
                        </button>
                    @elseif(!$c['is_invoice'] && !empty($c['tx_id']))
                        {{-- registrar pagamento (transação) --}}
                        <button type="button" class="bg-transparent border-0"
                                data-open-payment
                                data-id="{{ $c['tx_id'] }}"
                                data-amount="{{ abs($c['amt']) }}"
                                data-date="{{ $c['date'] }}"
                                data-title="{{ e($c['title']) }}">
                            <i class="fa-solid fa-check-to-slot text-success"></i>
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="transaction-card">
                <div class="transaction-info">
                    <div class="icon"><i class="fa-solid fa-calendar-day"></i></div>
                    <div class="details">Sem lançamentos hoje.</div>
                </div>
            </div>
        @endforelse

        {{-- ===== AMANHÃ ===== --}}
        <h5 class="mb-2 mt-4">Amanhã ({{ $tomorrow->format('d/m/Y') }})</h5>
        @forelse($cardsTomorrow as $c)
            <div class="transaction-card" data-invoice-card>
                <div class="transaction-info">
                    <div class="icon text-white" style="background: {{ $c['bg'] }}">
                        <i class="{{ $c['icon'] }}"></i>
                    </div>
                    <div class="details">
                        {{ $c['title'] }}<br>
                        <span>{{ $fmt($c['date']) }}</span>
                    </div>
                </div>

                <div class="d-flex align-items-center">
                    <div class="transaction-amount price-default mx-3">
                        {{ ($c['amt'] ?? 0) < 0 ? '-' : '+' }} {{ brlPrice(abs($c['amt'] ?? 0)) }}
                    </div>

                    @if($c['is_invoice'] && empty($c['paid']))
                        <button type="button" class="bg-transparent border-0"
                                data-pay-invoice
                                data-card="{{ $c['card_id'] }}"
                                data-month="{{ $c['current_month'] }}"
                                data-amount="{{ abs($c['amt']) }}"
                                data-title="{{ e($c['title']) }}">
                            <i class="fa-solid fa-check text-success"></i>
                        </button>
                    @elseif(!$c['is_invoice'] && !empty($c['tx_id']))
                        <button type="button" class="bg-transparent border-0"
                                data-open-payment
                                data-id="{{ $c['tx_id'] }}"
                                data-amount="{{ abs($c['amt']) }}"
                                data-date="{{ $c['date'] }}"
                                data-title="{{ e($c['title']) }}">
                            <i class="fa-solid fa-check-to-slot text-success"></i>
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="transaction-card">
                <div class="transaction-info">
                    <div class="icon"><i class="fa-solid fa-calendar-day"></i></div>
                    <div class="details">Sem lançamentos amanhã.</div>
                </div>
            </div>
        @endforelse

        {{-- ===== PRÓXIMOS 5 (já no mesmo padrão) ===== --}}
        <h5 class="mb-2 mt-4">Próximos lançamentos</h5>
        @forelse($nextFive as $item)
            <div class="transaction-card" data-invoice-card>
                <div class="transaction-info">
                    <div class="icon text-white" style="background: {{ $item['bg'] ?? '#6b7280' }}">
                        <i class="{{ $item['icon'] ?? 'fa-solid fa-calendar-day' }}"></i>
                    </div>
                    <div class="details">
                        {{ $item['title'] ?? ($item['extendedProps']['category_name'] ?? 'Sem descrição') }}
                        <br><span>{{ \Carbon\Carbon::parse($item['start'])->format('d/m/Y') }}</span>
                    </div>
                </div>

                <div class="d-flex align-items-center">
                    @php $amt = $item['extendedProps']['amount'] ?? 0; @endphp
                    <div class="transaction-amount price-default mx-3">
                        {{ $amt < 0 ? '-' : '+' }} {{ brlPrice(abs($amt)) }}
                    </div>

                    @if(!empty($item['extendedProps']['is_invoice']) && !$item['extendedProps']['paid'])
                        <button type="button" class="bg-transparent border-0"
                                data-pay-invoice
                                data-card="{{ $item['extendedProps']['card_id'] }}"
                                data-month="{{ $item['extendedProps']['current_month'] }}"
                                data-amount="{{ abs($amt) }}"
                                data-title="{{ e($item['title']) }}">
                            <i class="fa-solid fa-check text-success"></i>
                        </button>
                    @elseif(in_array($item['extendedProps']['type'] ?? '', ['despesa','entrada']) && empty($item['extendedProps']['paid']))
                        <button type="button" class="bg-transparent border-0"
                                data-open-payment
                                data-id="{{ $item['extendedProps']['transaction_id'] }}"
                                data-amount="{{ abs($amt) }}"
                                data-date="{{ $item['start'] }}"
                                data-title="{{ e($item['title']) }}">
                            <i class="fa-solid fa-check-to-slot text-success"></i>
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="transaction-card">
                <div class="transaction-info">
                    <div class="icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                    <div class="details">Não há próximos lançamentos.</div>
                </div>
            </div>
        @endforelse

    </div>
@endsection
