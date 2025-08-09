@extends('layouts.templates.app')
@section('content')
    <div class="header">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h1 class="m-0">Tela inicial</h1>
        </div>

        {{-- Seletor de mês full width --}}
        <form method="GET" class="d-flex align-items-center justify-content-between w-100 mb-3">
            <button type="button" class="btn btn-light rounded-circle shadow-sm px-3"
                    onclick="changeMonth(-1)">
                <i class="fa fa-chevron-left"></i>
            </button>

            <input
                type="month"
                name="month"
                id="monthPicker"
                class="form-control text-center fw-bold mx-2"
                value="{{ $startOfMonth->format('Y-m') }}"
                style="max-width: 250px; flex: 1;"
                onchange="this.form.submit()"
            >

            <button type="button" class="btn btn-light rounded-circle shadow-sm px-3"
                    onclick="changeMonth(1)">
                <i class="fa fa-chevron-right"></i>
            </button>
        </form>

        <div class="balance-box mt-2">

            <div class="d-flex justify-content-between">
                <span>Saldo do mês</span>
                <i class="fa fa-eye"></i>
            </div>

            <strong>{{ brlPrice($total) }}</strong>

            <div class="d-flex justify-content-between align-items-center mt-2 mb-1">
                <small>
                    <b class="text-muted">A receber <a href="{{ route('transactionCategory-view.index') }}"><i
                                class="fa fa-arrow-right text-color mx-2" style="font-size: 12px;"></i></a></b>

                    @php
                        $incPct = $incomeMoM; // pode ser null
                        $incSign = $incPct !== null && $incPct > 0 ? '+' : '';
                        $incClass = $incPct === null ? 'text-muted'
                                    : ($incPct >= 0 ? 'text-success' : 'text-danger');
                    @endphp

                    <div class="d-flex align-items-center">
                        <span>{{ brlPrice($totalIncome) }}</span>
                        <small class="{{ $incClass }} mx-2">
                            {{ $incPct === null ? '—' : $incSign . number_format($incPct, 0) . '%' }}
                        </small>
                    </div>

                    <b class="text-muted">Saldo contas <a href="{{ route('account-view.index') }}"><i
                                class="fa fa-arrow-right text-color mx-2" style="font-size: 12px;"></i></a></b>
                    <div>
                        <span>{{ brlPrice($accountsBalance) }}</span>
                    </div>

                    <b class="text-muted">Cofrinhos <a href="{{ route('saving-view.index') }}"><i
                                class="fa fa-arrow-right text-color mx-2" style="font-size: 12px;"></i></a></b>
                    <div>
                        <span>{{ brlPrice($savingsBalance) }}</span>
                    </div>
                </small>

                <small>
                    <b class="text-muted">A pagar <a href="{{ route('transactionCategory-view.index') }}"><i
                                class="fa fa-arrow-right text-danger mx-2" style="font-size: 12px;"></i></a></b>

                    @php
                        $expPct = $expenseMoM; // pode ser null
                        $expSign = $expPct !== null && $expPct > 0 ? '+' : '';
                        $expClass = $expPct === null ? 'text-muted'
                                   : ($expPct > 0 ? 'text-danger' : 'text-success');
                    @endphp

                    <div class="d-flex align-items-center">
                        <span>{{ brlPrice($categorySums['despesa'] ?? 0, 2, ',', '.') }}</span>
                        <small class="{{ $expClass }} mx-2">
                            {{ $expPct === null ? '—' : $expSign . number_format($expPct, 0) . '%' }}
                        </small>
                    </div>

                    <b class="text-muted">Balanço <a href="#"><i class="fa fa-arrow-right text-info mx-2"
                                                                 style="font-size: 12px;"></i></a></b>
                    <div class="d-flex align-items-center">
                        <span>{{ brlPrice($balance) }}</span>
                    </div>
                </small>
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
                <i class="fas fa-retweet"></i>
                <span>Transações</span>
            </a>
        </div>
        <div class="icon-button">
            <a href="{{ route('card-view.index') }}" class="nav-link-atalho">
                <i class="fas fa-credit-card"></i><span>Cartões</span></a>
        </div>
        <div class="icon-button"><i class="fas fa-chart-line"></i><span>Investimentos</span></div>
        <div class="icon-button">
            <a href="{{ route('test.push') }}" class="nav-link-atalho">
                <i class="fas fa-building"></i><span>Loans</span>
            </a>
        </div>
        <div class="icon-button">
            <a href="{{ route('saving-view.index') }}" class="nav-link-atalho">
                <i class="fas fa-piggy-bank"></i><span>Cofrinhos</span></a>
        </div>
        <div class="icon-button"><i class="fas fa-wallet"></i><span>Carteira</span></div>
        <div class="icon-button"><i class="fas fa-exchange-alt"></i><span>Exchange</span></div>
        <div class="icon-button"><i class="fas fa-gift"></i><span>Cashbacks</span></div>
        <div class="icon-button"><i class="fas fa-cog"></i><span>Configurações</span></div>
    </div>

    <div class="">
        <div id="calendar"></div>
    </div>

    <div class="py-3" id="calendar-results"></div>

    <!-- Transações recentes -->
    <div class="recent-transactions">
        <h2>Transações recentes</h2>
        @forelse($recentTransactions as $transaction)
            @php
                $categoryType = optional($transaction->transactionCategory)->type;
                $categoryName = optional($transaction->transactionCategory)->name;
            @endphp

            <div class="transaction-card">
                <div class="transaction-info">
                    @if($categoryType === 'entrada')
                        <div class="icon bg-color">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                    @elseif($categoryType === 'despesa')
                        <div class="icon bg-danger">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                    @else
                        <div class="icon bg-info">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    @endif

                    <div class="details">
                        <p class="m-0 p-0">{{ $transaction->title ?? $categoryName }}</p>
                        @if($transaction->date)
                            <span class="text-muted mt-2"
                                  style="font-size: 12px;">{{ \Carbon\Carbon::parse($transaction->date)->format('d/m/Y') }}</span>
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

    <div class="card-invoice mt-4">
        <h2>Cartões de crédito</h2>
    </div>

    <!-- Próximos pagamentos -->
    <div class="next-payments mt-4">
        <h2>Próximos pagamentos</h2>
        @forelse($upcomingPayments as $payment)
            <div class="transaction-card">
                <div class="transaction-info">
                    <div class="icon">
                        <i class="fas fa-calendar-day"></i>
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
            <p class="text-muted">Nenhum pagamento futuro</p>
        @endforelse
    </div>

    @push('scripts')
        <script>
            function changeMonth(delta) {
                const input = document.getElementById('monthPicker');
                const [year, month] = input.value.split('-').map(Number);
                const date = new Date(year, month - 1 + delta, 1);
                input.value = date.toISOString().slice(0, 7);
                input.form.submit();
            }
        </script>
    @endpush
@endsection
