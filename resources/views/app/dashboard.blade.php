@extends('layouts.templates.app')
@section('content')
    <div class="header">
        <h1>Tela inicial</h1>

        <div class="balance-box">
            <div class="d-flex justify-content-between">
                <span>Saldo</span>
                <i class="fa fa-eye"></i>
            </div>
            <strong>{{ brlPrice($total) }}</strong>
            <div class="d-flex justify-content-between align-items-center mt-2 mb-1">
                <small>
                    <b class="text-muted">A receber <a href="{{ route('transactionCategory-view.index') }}"><i
                                class="fa fa-arrow-right text-color mx-2"
                                style="font-size: 12px;"></i></a></b>

                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center">
                            <span>{{ brlPrice($totalIncome) }}</span>
                            <small class="text-success mx-2">+17%</small>
                        </div>
                        <b class="text-muted">Saldo contas<a href="{{ route('account-view.index') }}"><i
                                    class="fa fa-arrow-right text-color mx-2"
                                    style="font-size: 12px;"></i></a></b>
                        <div>
                            <span>{{ brlPrice($accountsBalance) }}</span>
                        </div>
                        <b class="text-muted">Cofrinhos<a href="{{ route('saving-view.index') }}"><i
                                    class="fa fa-arrow-right text-color mx-2"
                                    style="font-size: 12px;"></i></a></b>
                        <div>
                            <span>{{ brlPrice($savingsBalance) }}</span>
                        </div>
                    </div>
                </small>

                <small>
                    <b class="text-muted">A pagar <a href="{{ route('transactionCategory-view.index') }}"><i
                                class="fa fa-arrow-right text-danger mx-2"
                                style="font-size: 12px;"></i></a></b>
                    <div class="d-flex align-items-center">
                        <span>{{ brlPrice($categorySums['despesa'] ?? 0, 2, ',', '.') }}</span>
                        <small class="text-danger mx-2"> +3%</small>
                    </div>
                    <b class="text-muted">Balanço <a href="#"><i class="fa fa-arrow-right text-info mx-2"
                                                                 style="font-size: 12px;"></i></a></b>
                    <div class="d-flex align-items-center">
                        <span>{{ brlPrice($balance) }}</span>
                    </div>
                </small>
            </div>
            <div class="d-flex justify-content-end align-items-center mt-3">
                <a href="#" class="text-muted fw-bold" style="text-decoration: none; font-size: 13px;">Extrato do mês<i
                        class="fa fa-chevron-right mx-2" style="font-size: 12px;"></i></a>
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
        <h6>Transações recentes</h6>
        @forelse($recentTransactions as $transaction)
            @php
                $categoryType = optional($transaction->transactionCategory)->type;
                $categoryName = optional($transaction->transactionCategory)->name;
            @endphp

            <div class="transaction-card">
                <div class="transaction-info">
                    <div class="icon">
                        @if($categoryType === 'entrada')
                            <i class="fas fa-arrow-up text-success"></i>
                        @elseif($categoryType === 'despesa')
                            {{-- Vermelha para baixo --}}
                            <i class="fas fa-arrow-down text-danger"></i>
                        @else
                            <i class="fas fa-chart-line text-primary"></i>
                        @endif
                    </div>
                    <div class="details">
                        {{ $transaction->title ?? $categoryName }}
                        <br>
                        @if($transaction->date)
                            <span>{{ \Carbon\Carbon::parse($transaction->date)->format('d/m/Y') }}</span>
                        @endif
                    </div>
                </div>
                <div class="transaction-amount {{ $categoryType === 'despesa' ? 'text-danger' : 'text-success' }}">
                    {{ $categoryType === 'despesa' ? '-' : '+' }}
                    {{ brlPrice($transaction->amount) }}
                </div>
            </div>
        @empty
            <p class="text-muted">Nenhuma transação encontrada</p>
        @endforelse
    </div>

    <!-- Próximos pagamentos -->
    <div class="next-payments mt-4">
        <h6>Próximos pagamentos</h6>
        @forelse($upcomingPayments as $payment)
            <div class="transaction-card">
                <div class="transaction-info">
                    <div class="icon">
                        <i class="fas fa-calendar-day text-warning"></i>
                    </div>
                    <div class="details">
                        {{ $payment->title ?? $payment->category_name }}
                        <br>
                        <span>{{ \Carbon\Carbon::parse($payment->date)->format('d/m/Y') }}</span>
                    </div>
                </div>
                <div class="transaction-amount text-danger">
                    - {{ brlPrice($payment->amount) }}
                </div>
            </div>
        @empty
            <p class="text-muted">Nenhum pagamento futuro</p>
        @endforelse
    </div>

    <!-- Bottom nav -->
    <div class="bottom-nav">
        <a href="{{route('dashboard')}}"><i class="fas fa-home"></i></a>
        <a href="{{ route('transaction-view.index') }}"><i class="fas fa-retweet"></i></a>
        <a href="{{ route('transaction-view.index') }}"><i class="fas fa-chart-line"></i></a>
        <i class="fas fa-user"></i>
    </div>
@endsection
