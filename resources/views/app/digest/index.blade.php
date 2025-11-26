@extends('layouts.templates.app')

@section('new-content')
    <x-card-header
        prevRoute="{{ route('dashboard') }}"
        iconRight="calendar"
        title="Lançamentos"
        description="Veja os seus lançamentos de hoje e dos próximos dias!">
    </x-card-header>

    @php
        $fmt = fn($d) => \Carbon\Carbon::parse($d)->format('d/m/Y');

        // TRANSAÇÃO -> card normalizado
        $txToCard = function($t) use ($today, $fmt){
            $cat    = optional($t->transactionCategory);
            $type   = $cat->type; // 'entrada'|'despesa'|'investimento'
            $amt    = (float)$t->amount;
            $signed = $type === 'entrada' ? abs($amt) : -abs($amt);

            return [
                'bg'          => $cat->color ?: '#6b7280',
                'icon'        => $cat->icon  ?: 'fa-solid fa-receipt',
                'title'       => $t->title ?? $cat->name ?? 'Lançamento',
                'date'        => $t->date ? \Carbon\Carbon::parse($t->date)->toDateString() : $today->toDateString(),
                'amt'         => $signed,
                'is_invoice'  => false,
                'paid'        => false,
                'tx_id'       => (string)$t->id,
            ];
        };

        // FATURA -> card normalizado
        $invToCard = fn($inv) => [
            'bg'            => '#be123c',
            'icon'          => 'fa-solid fa-credit-card',
            'title'         => $inv['title'],
            'date'          => $inv['due_date'],
            'amt'           => -abs((float)$inv['total']),
            'is_invoice'    => true,
            'paid'          => false,
            'card_id'       => $inv['card_id'],
            'current_month' => $inv['current_month'],
        ];

        // HOJE
        $cardsToday = collect();
        if (isset($invoicesToday)) {
            $cardsToday = $cardsToday->merge($invoicesToday->map($invToCard));
        }

        $cardsToday = $cardsToday
            ->merge($todayIn->map($txToCard))
            ->merge($todayOut->map($txToCard))
            ->merge($todayInv->map($txToCard))
            ->sortBy('date')
            ->values();

        // AMANHÃ
        $cardsTomorrow = collect();
        if (isset($invoicesTomorrow)) {
            $cardsTomorrow = $cardsTomorrow->merge($invoicesTomorrow->map($invToCard));
        }

        $cardsTomorrow = $cardsTomorrow
            ->merge($tomIn->map($txToCard))
            ->merge($tomOut->map($txToCard))
            ->merge($tomInv->map($txToCard))
            ->sortBy('date')
            ->values();

        $overdueTop  = isset($overdueCards) ? $overdueCards->take(2) : collect();
        $overdueRest = isset($overdueCards) ? $overdueCards->slice(2)->values() : collect();
    @endphp

    <section class="mt-4 space-y-4">

        {{-- ATRASADOS --}}
        @if(isset($overdueCards) && $overdueCards->count())
            <div
                class="rounded-2xl border border-red-200/70 dark:border-red-900/60 bg-red-50 dark:bg-red-950/40 p-4">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-red-700 dark:text-red-300">
                        Lançamentos atrasados
                    </h2>

                    @if($overdueRest->count())
                        <button
                            type="button"
                            id="btn-overdue-toggle"
                            class="text-xs font-medium text-red-700 hover:text-red-800 dark:text-red-300 dark:hover:text-red-200"
                            data-label-more="Ver mais ({{ $overdueRest->count() }})"
                            data-label-less="Ver menos"
                        >
                            Ver mais ({{ $overdueRest->count() }})
                        </button>
                    @endif
                </div>

                {{-- 2 primeiros --}}
                <ul class="mt-3 divide-y divide-red-100/70 dark:divide-red-900/50">
                    @foreach($overdueTop as $c)
                        <li class="grid grid-cols-[auto_1fr_auto] items-center gap-3 py-3 transaction-card">
                            <span class="size-10 grid place-items-center rounded-xl text-white"
                                  style="background: {{ $c['bg'] }}">
                                <i class="{{ $c['icon'] }}"></i>
                            </span>

                            <div>
                                <p class="text-sm font-medium">
                                    {{ $c['title'] }}
                                </p>
                                <p class="text-xs text-red-700/80 dark:text-red-200/80">
                                    @if(!empty($c['parcel_of']) && !empty($c['parcel_total']) && $c['parcel_total'] > 1)
                                        <span class="font-semibold">
                                            Parcela {{ $c['parcel_of'] }}/{{ $c['parcel_total'] }}
                                        </span>
                                        <span class="mx-1">•</span>
                                    @endif
                                    Venceu em {{ $fmt($c['date']) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-3 text-right">
                                <p class="text-sm font-semibold price-default">
                                    {{ ($c['amt'] ?? 0) < 0 ? '-' : '+' }} {{ brlPrice(abs($c['amt'] ?? 0)) }}
                                </p>

                                @if($c['is_invoice'] && empty($c['paid']))
                                    <button type="button"
                                            class="inline-flex items-center justify-center rounded-full border border-transparent text-green-600 hover:bg-green-50 text-sm"
                                            data-pay-invoice
                                            data-card="{{ $c['card_id'] }}"
                                            data-month="{{ $c['current_month'] }}"
                                            data-amount="{{ abs($c['amt']) }}"
                                            data-title="{{ e($c['title']) }}">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                @elseif(!$c['is_invoice'] && !empty($c['tx_id']))
                                    <button type="button"
                                            class="inline-flex items-center justify-center rounded-full border border-transparent text-green-600 hover:bg-green-50 text-sm"
                                            data-open-payment
                                            data-id="{{ $c['tx_id'] }}"
                                            data-amount="{{ abs($c['amt']) }}"
                                            data-date="{{ $c['date'] }}"
                                            data-title="{{ e($c['title']) }}">
                                        <i class="fa-solid fa-check-to-slot"></i>
                                    </button>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- resto (accordion) --}}
                @if($overdueRest->count())
                    <ul id="overdue-more" class="mt-1 divide-y divide-red-100/70 dark:divide-red-900/50 hidden">
                        @foreach($overdueRest as $c)
                            <li class="grid grid-cols-[auto_1fr_auto] items-center gap-3 py-3 transaction-card">
                                <span class="size-10 grid place-items-center rounded-xl text-white"
                                      style="background: {{ $c['bg'] }}">
                                    <i class="{{ $c['icon'] }}"></i>
                                </span>

                                <div>
                                    <p class="text-sm font-medium">
                                        {{ $c['title'] }}
                                    </p>
                                    <p class="text-xs text-red-700/80 dark:text-red-200/80">
                                        @if(!empty($c['parcel_of']) && !empty($c['parcel_total']) && $c['parcel_total'] > 1)
                                            <span class="font-semibold">
                                                Parcela {{ $c['parcel_of'] }}/{{ $c['parcel_total'] }}
                                            </span>
                                            <span class="mx-1">•</span>
                                        @endif
                                        Venceu em {{ $fmt($c['date']) }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-3 text-right">
                                    <p class="text-sm font-semibold price-default">
                                        {{ ($c['amt'] ?? 0) < 0 ? '-' : '+' }} {{ brlPrice(abs($c['amt'] ?? 0)) }}
                                    </p>

                                    @if($c['is_invoice'] && empty($c['paid']))
                                        <button type="button"
                                                class="inline-flex items-center justify-center rounded-full border border-transparent text-green-600 hover:bg-green-50 text-sm"
                                                data-pay-invoice
                                                data-card="{{ $c['card_id'] }}"
                                                data-month="{{ $c['current_month'] }}"
                                                data-amount="{{ abs($c['amt']) }}"
                                                data-title="{{ e($c['title']) }}">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    @elseif(!$c['is_invoice'] && !empty($c['tx_id']))
                                        <button type="button"
                                                class="inline-flex items-center justify-center rounded-full border border-transparent text-green-600 hover:bg-green-50 text-sm"
                                                data-open-payment
                                                data-id="{{ $c['tx_id'] }}"
                                                data-amount="{{ abs($c['amt']) }}"
                                                data-date="{{ $c['date'] }}"
                                                data-title="{{ e($c['title']) }}">
                                            <i class="fa-solid fa-check-to-slot"></i>
                                        </button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        {{-- HOJE --}}
        <div
            class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-neutral-700 dark:text-neutral-100">
                    Hoje ({{ $today->format('d/m/Y') }})
                </h2>
            </div>

            <ul class="mt-3 divide-y divide-neutral-200/70 dark:divide-neutral-800/70">
                @forelse($cardsToday as $c)
                    <li class="grid grid-cols-[auto_1fr_auto] items-center gap-3 py-3 transaction-card">
                        <span class="size-10 grid place-items-center rounded-xl text-white"
                              style="background: {{ $c['bg'] }}">
                            <i class="{{ $c['icon'] }}"></i>
                        </span>

                        <div>
                            <p class="text-sm font-medium">
                                {{ $c['title'] }}
                            </p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                {{ $fmt($c['date']) }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3 text-right">
                            <p class="text-sm font-semibold price-default">
                                {{ ($c['amt'] ?? 0) < 0 ? '-' : '+' }} {{ brlPrice(abs($c['amt'] ?? 0)) }}
                            </p>

                            @if($c['is_invoice'] && empty($c['paid']))
                                <button type="button"
                                        class="inline-flex items-center justify-center rounded-full border border-transparent text-green-600 hover:bg-green-50 text-sm"
                                        data-pay-invoice
                                        data-card="{{ $c['card_id'] }}"
                                        data-month="{{ $c['current_month'] }}"
                                        data-amount="{{ abs($c['amt']) }}"
                                        data-title="{{ e($c['title']) }}">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            @elseif(!$c['is_invoice'] && !empty($c['tx_id']))
                                <button type="button"
                                        class="inline-flex items-center justify-center rounded-full border border-transparent text-green-600 hover:bg-green-50 text-sm"
                                        data-open-payment
                                        data-id="{{ $c['tx_id'] }}"
                                        data-amount="{{ abs($c['amt']) }}"
                                        data-date="{{ $c['date'] }}"
                                        data-title="{{ e($c['title']) }}">
                                    <i class="fa-solid fa-check-to-slot"></i>
                                </button>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="grid grid-cols-[auto_1fr_auto] items-center gap-3 py-3">
                        <span class="size-10 grid place-items-center rounded-xl bg-neutral-100 dark:bg-neutral-800">
                            <i class="fa-solid fa-calendar-day"></i>
                        </span>
                        <div class="text-sm text-neutral-500 dark:text-neutral-400">
                            Sem lançamentos hoje.
                        </div>
                        <div></div>
                    </li>
                @endforelse
            </ul>
        </div>

        {{-- AMANHÃ --}}
        <div
            class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-neutral-700 dark:text-neutral-100">
                    Amanhã ({{ $tomorrow->format('d/m/Y') }})
                </h2>
            </div>

            <ul class="mt-3 divide-y divide-neutral-200/70 dark:divide-neutral-800/70">
                @forelse($cardsTomorrow as $c)
                    <li class="grid grid-cols-[auto_1fr_auto] items-center gap-3 py-3 transaction-card">
                        <span class="size-10 grid place-items-center rounded-xl text-white"
                              style="background: {{ $c['bg'] }}">
                            <i class="{{ $c['icon'] }}"></i>
                        </span>

                        <div>
                            <p class="text-sm font-medium">
                                {{ $c['title'] }}
                            </p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                {{ $fmt($c['date']) }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3 text-right">
                            <p class="text-sm font-semibold price-default">
                                {{ ($c['amt'] ?? 0) < 0 ? '-' : '+' }} {{ brlPrice(abs($c['amt'] ?? 0)) }}
                            </p>

                            @if($c['is_invoice'] && empty($c['paid']))
                                <button type="button"
                                        class="inline-flex items-center justify-center rounded-full border border-transparent text-green-600 hover:bg-green-50 text-sm"
                                        data-pay-invoice
                                        data-card="{{ $c['card_id'] }}"
                                        data-month="{{ $c['current_month'] }}"
                                        data-amount="{{ abs($c['amt']) }}"
                                        data-title="{{ e($c['title']) }}">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            @elseif(!$c['is_invoice'] && !empty($c['tx_id']))
                                <button type="button"
                                        class="inline-flex items-center justify-center rounded-full border border-transparent text-green-600 hover:bg-green-50 text-sm"
                                        data-open-payment
                                        data-id="{{ $c['tx_id'] }}"
                                        data-amount="{{ abs($c['amt']) }}"
                                        data-date="{{ $c['date'] }}"
                                        data-title="{{ e($c['title']) }}">
                                    <i class="fa-solid fa-check-to-slot"></i>
                                </button>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="grid grid-cols-[auto_1fr_auto] items-center gap-3 py-3">
                        <span class="size-10 grid place-items-center rounded-xl bg-neutral-100 dark:bg-neutral-800">
                            <i class="fa-solid fa-calendar-day"></i>
                        </span>
                        <div class="text-sm text-neutral-500 dark:text-neutral-400">
                            Sem lançamentos amanhã.
                        </div>
                        <div></div>
                    </li>
                @endforelse
            </ul>
        </div>

        {{-- PRÓXIMOS LANÇAMENTOS --}}
        <div
            class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-neutral-700 dark:text-neutral-100">
                    Próximos lançamentos
                </h2>
            </div>

            <ul class="mt-3 divide-y divide-neutral-200/70 dark:divide-neutral-800/70">
                @forelse($nextFive as $item)
                    @php $amt = $item['extendedProps']['amount'] ?? 0; @endphp
                    <li class="grid grid-cols-[auto_1fr_auto] items-center gap-3 py-3 transaction-card">
                        <span class="size-10 grid place-items-center rounded-xl text-white"
                              style="background: {{ $item['bg'] ?? '#6b7280' }}">
                            <i class="{{ $item['icon'] ?? 'fa-solid fa-calendar-day' }}"></i>
                        </span>

                        <div>
                            <p class="text-sm font-medium">
                                {{ $item['title'] ?? ($item['extendedProps']['category_name'] ?? 'Sem descrição') }}
                            </p>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                {{ \Carbon\Carbon::parse($item['start'])->format('d/m/Y') }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3 text-right">
                            <p class="text-sm font-semibold price-default">
                                {{ $amt < 0 ? '-' : '+' }} {{ brlPrice(abs($amt)) }}
                            </p>

                            @if(!empty($item['extendedProps']['is_invoice']) && !$item['extendedProps']['paid'])
                                <button type="button"
                                        class="inline-flex items-center justify-center rounded-full border border-transparent text-green-600 hover:bg-green-50 text-sm"
                                        data-pay-invoice
                                        data-card="{{ $item['extendedProps']['card_id'] }}"
                                        data-month="{{ $item['extendedProps']['current_month'] }}"
                                        data-amount="{{ abs($amt) }}"
                                        data-title="{{ e($item['title']) }}">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            @elseif(in_array($item['extendedProps']['type'] ?? '', ['despesa','entrada']) && empty($item['extendedProps']['paid']))
                                <button type="button"
                                        class="inline-flex items-center justify-center rounded-full border border-transparent text-green-600 hover:bg-green-50 text-sm"
                                        data-open-payment
                                        data-id="{{ $item['extendedProps']['transaction_id'] }}"
                                        data-amount="{{ abs($amt) }}"
                                        data-date="{{ $item['start'] }}"
                                        data-title="{{ e($item['title']) }}">
                                    <i class="fa-solid fa-check-to-slot"></i>
                                </button>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="grid grid-cols-[auto_1fr_auto] items-center gap-3 py-3">
                        <span class="size-10 grid place-items-center rounded-xl bg-neutral-100 dark:bg-neutral-800">
                            <i class="fa-solid fa-file-invoice-dollar"></i>
                        </span>
                        <div class="text-sm text-neutral-500 dark:text-neutral-400">
                            Não há próximos lançamentos.
                        </div>
                        <div></div>
                    </li>
                @endforelse
            </ul>
        </div>
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const btn = document.getElementById('btn-overdue-toggle');
                const more = document.getElementById('overdue-more');

                if (!btn || !more) return;

                const labelMore = btn.dataset.labelMore || btn.textContent;
                const labelLess = btn.dataset.labelLess || 'Ver menos';

                btn.addEventListener('click', () => {
                    const isOpen = !more.classList.contains('hidden');

                    if (isOpen) {
                        more.classList.add('hidden');
                        btn.textContent = labelMore;
                    } else {
                        more.classList.remove('hidden');
                        btn.textContent = labelLess;
                        more.scrollIntoView({behavior: 'smooth', block: 'start'});
                    }
                });
            });
        </script>
    @endpush
@endsection
