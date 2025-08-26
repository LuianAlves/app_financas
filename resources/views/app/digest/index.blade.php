{{-- resources/views/app/digest/index.blade.php --}}
@extends('layouts.templates.app')

@section('content')
    <x-card-header
        prevRoute="{{ url()->previous() }}"
        iconRight="fa-solid fa-bell"
        title="Lançamentos"
        description="Resumo de hoje, amanhã e próximos.">
    </x-card-header>

    @php
        $br = fn($n) => number_format((float)$n, 2, ',', '.');
        $fmt = fn($d) => \Carbon\Carbon::parse($d)->format('d/m/Y');
    @endphp

    <div class="mt-3">

        {{-- HOJE --}}
        <h5 class="mb-2">Hoje ({{ $today->format('d/m/Y') }})</h5>

        <div class="mb-3">
            <strong>Entradas</strong>
            @forelse($todayIn as $t)
                <div class="transaction-card">
                    <div class="transaction-info">
                        <div class="icon" style="background:#16a34a"><i class="fa-solid fa-arrow-down"></i></div>
                        <div class="details">{{ $t->title ?? optional($t->transactionCategory)->name }}</div>
                    </div>
                    <div class="transaction-amount price-default">+ {{ brlPrice($t->amount) }}</div>
                </div>
            @empty
                <small class="text-muted">Sem entradas para hoje.</small>
            @endforelse
        </div>

        <div class="mb-3">
            <strong>Saídas</strong>
            @forelse($todayOut as $t)
                <div class="transaction-card">
                    <div class="transaction-info">
                        <div class="icon" style="background:#ef4444"><i class="fa-solid fa-arrow-up"></i></div>
                        <div class="details">{{ $t->title ?? optional($t->transactionCategory)->name }}</div>
                    </div>
                    <div class="transaction-amount price-default">- {{ brlPrice($t->amount) }}</div>
                </div>
            @empty
                <small class="text-muted">Sem saídas para hoje.</small>
            @endforelse
        </div>

        <div class="mb-4">
            <strong>Investimentos programados</strong>
            @forelse($todayInv as $t)
                <div class="transaction-card">
                    <div class="transaction-info">
                        <div class="icon" style="background:#0ea5e9"><i class="fa-solid fa-chart-line"></i></div>
                        <div class="details">{{ $t->title ?? optional($t->transactionCategory)->name }}</div>
                    </div>
                    <div class="transaction-amount price-default">{{ brlPrice($t->amount) }}</div>
                </div>
            @empty
                <small class="text-muted">Sem investimentos hoje.</small>
            @endforelse
        </div>

        {{-- AMANHÃ --}}
        <h5 class="mb-2">Amanhã ({{ $tomorrow->format('d/m/Y') }})</h5>

        <div class="mb-3">
            <strong>Entradas</strong>
            @forelse($tomIn as $t)
                <div class="transaction-card">
                    <div class="transaction-info">
                        <div class="icon" style="background:#16a34a"><i class="fa-solid fa-arrow-down"></i></div>
                        <div class="details">{{ $t->title ?? optional($t->transactionCategory)->name }}</div>
                    </div>
                    <div class="transaction-amount price-default">+ {{ brlPrice($t->amount) }}</div>
                </div>
            @empty
                <small class="text-muted">Sem entradas para amanhã.</small>
            @endforelse
        </div>

        <div class="mb-3">
            <strong>Saídas</strong>
            @forelse($tomOut as $t)
                <div class="transaction-card">
                    <div class="transaction-info">
                        <div class="icon" style="background:#ef4444"><i class="fa-solid fa-arrow-up"></i></div>
                        <div class="details">{{ $t->title ?? optional($t->transactionCategory)->name }}</div>
                    </div>
                    <div class="transaction-amount price-default">- {{ brlPrice($t->amount) }}</div>
                </div>
            @empty
                <small class="text-muted">Sem saídas para amanhã.</small>
            @endforelse
        </div>

        <div class="mb-4">
            <strong>Investimentos programados</strong>
            @forelse($tomInv as $t)
                <div class="transaction-card">
                    <div class="transaction-info">
                        <div class="icon" style="background:#0ea5e9"><i class="fa-solid fa-chart-line"></i></div>
                        <div class="details">{{ $t->title ?? optional($t->transactionCategory)->name }}</div>
                    </div>
                    <div class="transaction-amount price-default">{{ brlPrice($t->amount) }}</div>
                </div>
            @empty
                <small class="text-muted">Sem investimentos amanhã.</small>
            @endforelse
        </div>

        {{-- PRÓXIMOS 5 --}}
        <h5 class="mb-2">Próximos lançamentos</h5>
        @forelse($nextFive as $t)
            <div class="transaction-card">
                <div class="transaction-info">
                    <div class="icon" style="background:#6b7280"><i class="fa-solid fa-calendar-day"></i></div>
                    <div class="details">
                        {{ $t->title ?? optional($t->transactionCategory)->name }}
                        <br><span>{{ $fmt($t->date) }}</span>
                    </div>
                </div>
                @php $tp = optional($t->transactionCategory)->type; @endphp
                <div class="transaction-amount price-default">
                    {{ $tp === 'despesa' ? '-' : ($tp === 'entrada' ? '+' : '') }}
                    {{ brlPrice($t->amount) }}
                </div>
            </div>
        @empty
            <small class="text-muted">Não há próximos lançamentos.</small>
        @endforelse

    </div>
@endsection
