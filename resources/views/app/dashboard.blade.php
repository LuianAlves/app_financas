@extends('layouts.templates.app')
@section('content')
    <div class="header">
        <h1>Tela inicial</h1>
        <div class="balance-box">
            <div class="d-flex justify-content-between">
                <span>Saldo</span>
                <i class="fa fa-eye"></i>
            </div>
            <strong>{{brlPrice($total)}}</strong>
            <div class="d-flex justify-content-between align-items-center mt-2 mb-1">
                <small>

                    <b class="text-muted">A receber <a href="{{route('transactionCategory-view.index')}}"><i class="fa fa-arrow-right text-color mx-2"
                                                                   style="font-size: 12px;"></i></a></b>

                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center">
                            <span>{{ brlPrice($income) }}</span>
                            <small class="text-success mx-2">+17%</small>
                        </div>
                        <b class="text-muted">Saldo contas<a href="{{route('account-view.index')}}"><i class="fa fa-arrow-right text-color mx-2"
                                                                       style="font-size: 12px;"></i></a></b>
                        <div>
                            <span>{{ brlPrice($accounts) }}</span>
                        </div>
                        <b class="text-muted">Cofrinhos<a href="{{route('saving-view.index')}}"><i class="fa fa-arrow-right text-color mx-2"
                                                                                                   style="font-size: 12px;"></i></a></b>
                        <div>
                            <span>{{ brlPrice($savings) }}</span>
                        </div>
                    </div>

                </small>
                <small>
                    <b class="text-muted">A pagar <a href="{{route('transactionCategory-view.index')}}"><i class="fa fa-arrow-right text-danger mx-2"
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
            <a href="{{route('account-view.index')}}" class="nav-link-atalho">
                <i class="fas fa-landmark"></i>
                Bancos
            </a>
        </div>

        <div class="icon-button">
            <a href="{{route('transactionCategory-view.index')}}" class="nav-link-atalho">
                <i class="fas fa-retweet"></i>
                <span>Transações</span>
            </a>
        </div>

        <div class="icon-button">
            <a href="{{route('card-view.index')}}" class="nav-link-atalho">
            <i class="fas fa-credit-card"></i><span>Cartões</span></a>
        </div>

        <div class="icon-button"><i class="fas fa-chart-line"></i><span>Investimentos</span></div>

        <div class="icon-button">
            <a href="{{route('test.push')}}" class="nav-link-atalho">
                <i class="fas fa-building"></i><span>Loans</span>
            </a>
        </div>

        <div class="icon-button">
            <a href="{{route('saving-view.index')}}" class="nav-link-atalho">
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

    <div class="py-3" id="calendar-results">
    </div>

    <!-- Transações -->
    <div class="recent-transactions">
        <h6>Transações recentes</h6>
        <!-- Loop de transações -->
        <div class="transaction-card">
            <div class="transaction-info">
                <div class="icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="details">Groceries<br><span>Apr 20</span></div>
            </div>
            <div class="transaction-amount">- $82.50</div>
        </div>
        <!-- Repetir mais 15 -->
        <div class="transaction-card">
            <div class="transaction-info">
                <div class="icon"><i class="fas fa-bolt"></i></div>
                <div class="details">Electricity<br><span>Apr 19</span></div>
            </div>
            <div class="transaction-amount">- $45.00</div>
        </div>
        <div class="transaction-card">
            <div class="transaction-info">
                <div class="icon"><i class="fas fa-wifi"></i></div>
                <div class="details">Internet<br><span>Apr 18</span></div>
            </div>
            <div class="transaction-amount">- $60.00</div>
        </div>

        <!-- Próximos pagamentos -->
        <h6 class="mt-4">Próximos pagamentos</h6>

        <div class="transaction-card">
            <div class="transaction-info">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="details">Cartão Nubank<br><span>Vence em Aug 10</span></div>
            </div>
            <div class="transaction-amount text-danger">- $650.00</div>
        </div>

        <div class="transaction-card">
            <div class="transaction-info">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="details">Aluguel<br><span>Vence em Aug 12</span></div>
            </div>
            <div class="transaction-amount text-danger">- $1,200.00</div>
        </div>

        <div class="transaction-card">
            <div class="transaction-info">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="details">Internet Fibra<br><span>Vence em Aug 15</span></div>
            </div>
            <div class="transaction-amount text-danger">- $89.99</div>
        </div>

        <!-- Bottom nav -->
        <div class="bottom-nav">
            <i class="fas fa-home"></i>
            <i class="fas fa-bars"></i>
            <i class="fas fa-layer-group"></i>
            <i class="fas fa-user"></i>
        </div>
    </div>
@endsection
