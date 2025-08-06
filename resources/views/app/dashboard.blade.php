@extends('layouts.templates.mobile')
@section('content-mobile')

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
        <div class="icon-button"><i class="fas fa-building"></i><span>Loans</span></div>

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
{{--<x-app-layout>--}}
{{--    @section('content')--}}
{{--        <div class="row g-3 mb-3">--}}
{{--            <div class="col-md-6 col-xxl-3">--}}
{{--                <div class="card h-md-100 ecommerce-card-min-width">--}}
{{--                    <div class="card-header pb-0">--}}
{{--                        <h6 class="mb-0 mt-2 d-flex align-items-center">Weekly Sales<span class="ms-1 text-400"--}}
{{--                                                                                          data-bs-toggle="tooltip"--}}
{{--                                                                                          data-bs-placement="top"--}}
{{--                                                                                          title="Calculated according to last week's sales"><span--}}
{{--                                    class="far fa-question-circle" data-fa-transform="shrink-1"></span></span></h6>--}}
{{--                    </div>--}}
{{--                    <div class="card-body d-flex flex-column justify-content-end">--}}
{{--                        <div class="row">--}}
{{--                            <div class="col">--}}
{{--                                <p class="font-sans-serif lh-1 mb-1 fs-5">$47K</p><span--}}
{{--                                    class="badge badge-subtle-success rounded-pill fs-11">+3.5%</span>--}}
{{--                            </div>--}}
{{--                            <div class="col-auto ps-0">--}}
{{--                                <div class="echart-bar-weekly-sales h-100"></div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="col-md-6 col-xxl-3">--}}
{{--                <div class="card h-md-100">--}}
{{--                    <div class="card-header pb-0">--}}
{{--                        <h6 class="mb-0 mt-2">Total Order</h6>--}}
{{--                    </div>--}}
{{--                    <div class="card-body d-flex flex-column justify-content-end">--}}
{{--                        <div class="row justify-content-between">--}}
{{--                            <div class="col-auto align-self-end">--}}
{{--                                <div class="fs-5 fw-normal font-sans-serif text-700 lh-1 mb-1">58.4K</div>--}}
{{--                                <span class="badge rounded-pill fs-11 bg-200 text-primary"><span--}}
{{--                                        class="fas fa-caret-up me-1"></span>13.6%</span>--}}
{{--                            </div>--}}
{{--                            <div class="col-auto ps-0 mt-n4">--}}
{{--                                <div class="echart-default-total-order"--}}
{{--                                     data-echarts='{"tooltip":{"trigger":"axis","formatter":"{b0} : {c0}"},"xAxis":{"data":["Week 4","Week 5","Week 6","Week 7"]},"series":[{"type":"line","data":[20,40,100,120],"smooth":true,"lineStyle":{"width":3}}],"grid":{"bottom":"2%","top":"2%","right":"0","left":"10px"}}'--}}
{{--                                     data-echart-responsive="true"></div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="col-md-6 col-xxl-3">--}}
{{--                <div class="card h-md-100">--}}
{{--                    <div class="card-body">--}}
{{--                        <div class="row h-100 justify-content-between g-0">--}}
{{--                            <div class="col-5 col-sm-6 col-xxl pe-2">--}}
{{--                                <h6 class="mt-1">Market Share</h6>--}}
{{--                                <div class="fs-11 mt-3">--}}
{{--                                    <div class="d-flex flex-between-center mb-1">--}}
{{--                                        <div class="d-flex align-items-center"><span--}}
{{--                                                class="dot bg-primary"></span><span--}}
{{--                                                class="fw-semi-bold">Samsung</span></div>--}}
{{--                                        <div class="d-xxl-none">33%</div>--}}
{{--                                    </div>--}}
{{--                                    <div class="d-flex flex-between-center mb-1">--}}
{{--                                        <div class="d-flex align-items-center"><span--}}
{{--                                                class="dot bg-info"></span><span class="fw-semi-bold">Huawei</span>--}}
{{--                                        </div>--}}
{{--                                        <div class="d-xxl-none">29%</div>--}}
{{--                                    </div>--}}
{{--                                    <div class="d-flex flex-between-center mb-1">--}}
{{--                                        <div class="d-flex align-items-center"><span class="dot bg-300"></span><span--}}
{{--                                                class="fw-semi-bold">Apple</span></div>--}}
{{--                                        <div class="d-xxl-none">20%</div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                            <div class="col-auto position-relative">--}}
{{--                                <div class="echart-market-share"></div>--}}
{{--                                <div class="position-absolute top-50 start-50 translate-middle text-1100 fs-7">26M--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="col-md-6 col-xxl-3">--}}
{{--                <div class="card h-md-100">--}}
{{--                    <div class="card-header d-flex flex-between-center pb-0">--}}
{{--                        <h6 class="mb-0">Weather</h6>--}}
{{--                        <div class="dropdown font-sans-serif btn-reveal-trigger">--}}
{{--                            <button--}}
{{--                                class="btn btn-link text-600 btn-sm dropdown-toggle dropdown-caret-none btn-reveal"--}}
{{--                                type="button" id="dropdown-weather-update" data-bs-toggle="dropdown"--}}
{{--                                data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><span--}}
{{--                                    class="fas fa-ellipsis-h fs-11"></span></button>--}}
{{--                            <div class="dropdown-menu dropdown-menu-end border py-2"--}}
{{--                                 aria-labelledby="dropdown-weather-update"><a class="dropdown-item"--}}
{{--                                                                              href="#!">View</a><a--}}
{{--                                    class="dropdown-item" href="#!">Export</a>--}}
{{--                                <div class="dropdown-divider"></div>--}}
{{--                                <a class="dropdown-item text-danger" href="#!">Remove</a>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="card-body pt-2">--}}
{{--                        <div class="row g-0 h-100 align-items-center">--}}
{{--                            <div class="col">--}}
{{--                                <div class="d-flex align-items-center"><img class="me-3"--}}
{{--                                                                            src="assets/img/icons/weather-icon.png"--}}
{{--                                                                            alt="" height="60"/>--}}
{{--                                    <div>--}}
{{--                                        <h6 class="mb-2">New York City</h6>--}}
{{--                                        <div class="fs-11 fw-semi-bold">--}}
{{--                                            <div class="text-warning">Sunny</div>--}}
{{--                                            Precipitation: 50%--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                            <div class="col-auto text-center ps-2">--}}
{{--                                <div class="fs-5 fw-normal font-sans-serif text-primary mb-1 lh-1">31&deg;</div>--}}
{{--                                <div class="fs-10 text-800">32&deg; / 25&deg;</div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <div class="row g-0">--}}
{{--            <div class="col-lg-6 pe-lg-2 mb-3">--}}
{{--                <div class="card h-lg-100 overflow-hidden">--}}
{{--                    <div class="card-header bg-body-tertiary">--}}
{{--                        <div class="row align-items-center">--}}
{{--                            <div class="col">--}}
{{--                                <h6 class="mb-0">Running Projects</h6>--}}
{{--                            </div>--}}
{{--                            <div class="col-auto text-center pe-x1">--}}
{{--                                <select class="form-select form-select-sm">--}}
{{--                                    <option>Working Time</option>--}}
{{--                                    <option>Estimated Time</option>--}}
{{--                                    <option>Billable Time</option>--}}
{{--                                </select>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="card-body p-0">--}}
{{--                        <div class="row g-0 align-items-center py-2 position-relative border-bottom border-200">--}}
{{--                            <div class="col ps-x1 py-1 position-static">--}}
{{--                                <div class="d-flex align-items-center">--}}
{{--                                    <div class="avatar avatar-xl me-3">--}}
{{--                                        <div class="avatar-name rounded-circle bg-primary-subtle text-dark"><span--}}
{{--                                                class="fs-9 text-primary">F</span></div>--}}
{{--                                    </div>--}}
{{--                                    <div class="flex-1">--}}
{{--                                        <h6 class="mb-0 d-flex align-items-center"><a--}}
{{--                                                class="text-800 stretched-link" href="#!">Falcon</a><span--}}
{{--                                                class="badge rounded-pill ms-2 bg-200 text-primary">38%</span></h6>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                            <div class="col py-1">--}}
{{--                                <div class="row flex-end-center g-0">--}}
{{--                                    <div class="col-auto pe-2">--}}
{{--                                        <div class="fs-10 fw-semi-bold">12:50:00</div>--}}
{{--                                    </div>--}}
{{--                                    <div class="col-5 pe-x1 ps-2">--}}
{{--                                        <div class="progress bg-200 me-2" style="height: 5px;" role="progressbar"--}}
{{--                                             aria-valuenow="38" aria-valuemin="0" aria-valuemax="100">--}}
{{--                                            <div class="progress-bar rounded-pill" style="width: 38%"></div>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="row g-0 align-items-center py-2 position-relative border-bottom border-200">--}}
{{--                            <div class="col ps-x1 py-1 position-static">--}}
{{--                                <div class="d-flex align-items-center">--}}
{{--                                    <div class="avatar avatar-xl me-3">--}}
{{--                                        <div class="avatar-name rounded-circle bg-success-subtle text-dark"><span--}}
{{--                                                class="fs-9 text-success">R</span></div>--}}
{{--                                    </div>--}}
{{--                                    <div class="flex-1">--}}
{{--                                        <h6 class="mb-0 d-flex align-items-center"><a--}}
{{--                                                class="text-800 stretched-link" href="#!">Reign</a><span--}}
{{--                                                class="badge rounded-pill ms-2 bg-200 text-primary">79%</span></h6>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                            <div class="col py-1">--}}
{{--                                <div class="row flex-end-center g-0">--}}
{{--                                    <div class="col-auto pe-2">--}}
{{--                                        <div class="fs-10 fw-semi-bold">25:20:00</div>--}}
{{--                                    </div>--}}
{{--                                    <div class="col-5 pe-x1 ps-2">--}}
{{--                                        <div class="progress bg-200 me-2" style="height: 5px;" role="progressbar"--}}
{{--                                             aria-valuenow="79" aria-valuemin="0" aria-valuemax="100">--}}
{{--                                            <div class="progress-bar rounded-pill" style="width: 79%"></div>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="row g-0 align-items-center py-2 position-relative border-bottom border-200">--}}
{{--                            <div class="col ps-x1 py-1 position-static">--}}
{{--                                <div class="d-flex align-items-center">--}}
{{--                                    <div class="avatar avatar-xl me-3">--}}
{{--                                        <div class="avatar-name rounded-circle bg-info-subtle text-dark"><span--}}
{{--                                                class="fs-9 text-info">B</span></div>--}}
{{--                                    </div>--}}
{{--                                    <div class="flex-1">--}}
{{--                                        <h6 class="mb-0 d-flex align-items-center"><a--}}
{{--                                                class="text-800 stretched-link" href="#!">Boots4</a><span--}}
{{--                                                class="badge rounded-pill ms-2 bg-200 text-primary">90%</span></h6>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                            <div class="col py-1">--}}
{{--                                <div class="row flex-end-center g-0">--}}
{{--                                    <div class="col-auto pe-2">--}}
{{--                                        <div class="fs-10 fw-semi-bold">58:20:00</div>--}}
{{--                                    </div>--}}
{{--                                    <div class="col-5 pe-x1 ps-2">--}}
{{--                                        <div class="progress bg-200 me-2" style="height: 5px;" role="progressbar"--}}
{{--                                             aria-valuenow="90" aria-valuemin="0" aria-valuemax="100">--}}
{{--                                            <div class="progress-bar rounded-pill" style="width: 90%"></div>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="row g-0 align-items-center py-2 position-relative border-bottom border-200">--}}
{{--                            <div class="col ps-x1 py-1 position-static">--}}
{{--                                <div class="d-flex align-items-center">--}}
{{--                                    <div class="avatar avatar-xl me-3">--}}
{{--                                        <div class="avatar-name rounded-circle bg-warning-subtle text-dark"><span--}}
{{--                                                class="fs-9 text-warning">R</span></div>--}}
{{--                                    </div>--}}
{{--                                    <div class="flex-1">--}}
{{--                                        <h6 class="mb-0 d-flex align-items-center"><a--}}
{{--                                                class="text-800 stretched-link" href="#!">Raven</a><span--}}
{{--                                                class="badge rounded-pill ms-2 bg-200 text-primary">40%</span></h6>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                            <div class="col py-1">--}}
{{--                                <div class="row flex-end-center g-0">--}}
{{--                                    <div class="col-auto pe-2">--}}
{{--                                        <div class="fs-10 fw-semi-bold">21:20:00</div>--}}
{{--                                    </div>--}}
{{--                                    <div class="col-5 pe-x1 ps-2">--}}
{{--                                        <div class="progress bg-200 me-2" style="height: 5px;" role="progressbar"--}}
{{--                                             aria-valuenow="40" aria-valuemin="0" aria-valuemax="100">--}}
{{--                                            <div class="progress-bar rounded-pill" style="width: 40%"></div>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="row g-0 align-items-center py-2 position-relative">--}}
{{--                            <div class="col ps-x1 py-1 position-static">--}}
{{--                                <div class="d-flex align-items-center">--}}
{{--                                    <div class="avatar avatar-xl me-3">--}}
{{--                                        <div class="avatar-name rounded-circle bg-danger-subtle text-dark"><span--}}
{{--                                                class="fs-9 text-danger">S</span></div>--}}
{{--                                    </div>--}}
{{--                                    <div class="flex-1">--}}
{{--                                        <h6 class="mb-0 d-flex align-items-center"><a--}}
{{--                                                class="text-800 stretched-link" href="#!">Slick</a><span--}}
{{--                                                class="badge rounded-pill ms-2 bg-200 text-primary">70%</span></h6>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                            <div class="col py-1">--}}
{{--                                <div class="row flex-end-center g-0">--}}
{{--                                    <div class="col-auto pe-2">--}}
{{--                                        <div class="fs-10 fw-semi-bold">31:20:00</div>--}}
{{--                                    </div>--}}
{{--                                    <div class="col-5 pe-x1 ps-2">--}}
{{--                                        <div class="progress bg-200 me-2" style="height: 5px;" role="progressbar"--}}
{{--                                             aria-valuenow="70" aria-valuemin="0" aria-valuemax="100">--}}
{{--                                            <div class="progress-bar rounded-pill" style="width: 70%"></div>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="card-footer bg-body-tertiary p-0"><a class="btn btn-sm btn-link d-block w-100 py-2"--}}
{{--                                                                     href="#!">Show all projects<span--}}
{{--                                class="fas fa-chevron-right ms-1 fs-11"></span></a></div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="col-lg-6 ps-lg-2 mb-3">--}}
{{--                <div class="card h-lg-100">--}}
{{--                    <div class="card-header">--}}
{{--                        <div class="row flex-between-center">--}}
{{--                            <div class="col-auto">--}}
{{--                                <h6 class="mb-0">Total Sales</h6>--}}
{{--                            </div>--}}
{{--                            <div class="col-auto d-flex">--}}
{{--                                <select class="form-select form-select-sm select-month me-2">--}}
{{--                                    <option value="0">January</option>--}}
{{--                                    <option value="1">February</option>--}}
{{--                                    <option value="2">March</option>--}}
{{--                                    <option value="3">April</option>--}}
{{--                                    <option value="4">May</option>--}}
{{--                                    <option value="5">Jun</option>--}}
{{--                                    <option value="6">July</option>--}}
{{--                                    <option value="7">August</option>--}}
{{--                                    <option value="8">September</option>--}}
{{--                                    <option value="9">October</option>--}}
{{--                                    <option value="10">November</option>--}}
{{--                                    <option value="11">December</option>--}}
{{--                                </select>--}}
{{--                                <div class="dropdown font-sans-serif btn-reveal-trigger">--}}
{{--                                    <button--}}
{{--                                        class="btn btn-link text-600 btn-sm dropdown-toggle dropdown-caret-none btn-reveal"--}}
{{--                                        type="button" id="dropdown-total-sales" data-bs-toggle="dropdown"--}}
{{--                                        data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><span--}}
{{--                                            class="fas fa-ellipsis-h fs-11"></span></button>--}}
{{--                                    <div class="dropdown-menu dropdown-menu-end border py-2"--}}
{{--                                         aria-labelledby="dropdown-total-sales"><a class="dropdown-item"--}}
{{--                                                                                   href="#!">View</a><a--}}
{{--                                            class="dropdown-item" href="#!">Export</a>--}}
{{--                                        <div class="dropdown-divider"></div>--}}
{{--                                        <a class="dropdown-item text-danger" href="#!">Remove</a>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="card-body h-100 pe-0">--}}
{{--                        <!-- Find the JS file for the following chart at: src\js\charts\echarts\total-sales.js-->--}}
{{--                        <!-- If you are not using gulp based workflow, you can find the transpiled code at: public\assets\js\theme.js-->--}}
{{--                        <div class="echart-line-total-sales h-100" data-echart-responsive="true"></div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <div class="row g-0">--}}
{{--            <div class="col-lg-6 col-xl-7 col-xxl-8 mb-3 pe-lg-2 mb-3">--}}
{{--                <div class="card h-lg-100">--}}
{{--                    <div class="card-body d-flex align-items-center">--}}
{{--                        <div class="w-100">--}}
{{--                            <h6 class="mb-3 text-800">Using Storage <strong class="text-1100">1775.06 MB </strong>of--}}
{{--                                2 GB</h6>--}}
{{--                            <div class="progress-stacked mb-3 rounded-3" style="height: 10px;">--}}
{{--                                <div class="progress" style="width: 43.72%;" role="progressbar"--}}
{{--                                     aria-valuenow="43.72" aria-valuemin="0" aria-valuemax="100">--}}
{{--                                    <div--}}
{{--                                        class="progress-bar bg-progress-gradient border-end border-100 border-2"></div>--}}
{{--                                </div>--}}
{{--                                <div class="progress" style="width: 18.76%;" role="progressbar"--}}
{{--                                     aria-valuenow="18.76" aria-valuemin="0" aria-valuemax="100">--}}
{{--                                    <div class="progress-bar bg-info border-end border-100 border-2"></div>--}}
{{--                                </div>--}}
{{--                                <div class="progress" style="width: 9.38%;" role="progressbar" aria-valuenow="9.38"--}}
{{--                                     aria-valuemin="0" aria-valuemax="100">--}}
{{--                                    <div class="progress-bar bg-success border-end border-100 border-2"></div>--}}
{{--                                </div>--}}
{{--                                <div class="progress" style="width: 28.14%;" role="progressbar"--}}
{{--                                     aria-valuenow="28.14" aria-valuemin="0" aria-valuemax="100">--}}
{{--                                    <div class="progress-bar bg-200"></div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                            <div class="row fs-10 fw-semi-bold text-500 g-0">--}}
{{--                                <div class="col-auto d-flex align-items-center pe-3"><span--}}
{{--                                        class="dot bg-primary"></span><span>Regular</span><span--}}
{{--                                        class="d-none d-md-inline-block d-lg-none d-xxl-inline-block">(895MB)</span>--}}
{{--                                </div>--}}
{{--                                <div class="col-auto d-flex align-items-center pe-3"><span--}}
{{--                                        class="dot bg-info"></span><span>System</span><span--}}
{{--                                        class="d-none d-md-inline-block d-lg-none d-xxl-inline-block">(379MB)</span>--}}
{{--                                </div>--}}
{{--                                <div class="col-auto d-flex align-items-center pe-3"><span--}}
{{--                                        class="dot bg-success"></span><span>Shared</span><span--}}
{{--                                        class="d-none d-md-inline-block d-lg-none d-xxl-inline-block">(192MB)</span>--}}
{{--                                </div>--}}
{{--                                <div class="col-auto d-flex align-items-center"><span--}}
{{--                                        class="dot bg-200"></span><span>Free</span><span--}}
{{--                                        class="d-none d-md-inline-block d-lg-none d-xxl-inline-block">(576MB)</span>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="col-lg-6 col-xl-5 col-xxl-4 mb-3 ps-lg-2">--}}
{{--                <div class="card h-lg-100">--}}
{{--                    <div class="bg-holder bg-card"--}}
{{--                         style="background-image:url(assets/img/icons/spot-illustrations/corner-1.png);">--}}
{{--                    </div>--}}
{{--                    <!--/.bg-holder-->--}}

{{--                    <div class="card-body position-relative">--}}
{{--                        <h5 class="text-warning">Running out of your space?</h5>--}}
{{--                        <p class="fs-10 mb-0">Your storage will be running out soon. Get more space and powerful--}}
{{--                            productivity features.</p><a class="btn btn-link fs-10 text-warning mt-lg-3 ps-0"--}}
{{--                                                         href="#!">Upgrade storage<span--}}
{{--                                class="fas fa-chevron-right ms-1" data-fa-transform="shrink-4 down-1"></span></a>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <div class="row g-0">--}}
{{--            <div class="col-lg-7 col-xl-8 pe-lg-2 mb-3">--}}
{{--                <div class="card h-lg-100 overflow-hidden">--}}
{{--                    <div class="card-body p-0">--}}
{{--                        <div class="table-responsive scrollbar">--}}
{{--                            <table class="table table-dashboard mb-0 table-borderless fs-10 border-200">--}}
{{--                                <thead class="bg-body-tertiary">--}}
{{--                                <tr>--}}
{{--                                    <th class="text-900">Best Selling Products</th>--}}
{{--                                    <th class="text-900 text-end">Revenue ($3333)</th>--}}
{{--                                    <th class="text-900 pe-x1 text-end" style="width: 8rem">Revenue (%)</th>--}}
{{--                                </tr>--}}
{{--                                </thead>--}}
{{--                                <tbody>--}}
{{--                                <tr class="border-bottom border-200">--}}
{{--                                    <td>--}}
{{--                                        <div class="d-flex align-items-center position-relative"><img--}}
{{--                                                class="rounded-1 border border-200" src="assets/img/products/12.png"--}}
{{--                                                width="60" alt=""/>--}}
{{--                                            <div class="flex-1 ms-3">--}}
{{--                                                <h6 class="mb-1 fw-semi-bold"><a class="text-1100 stretched-link"--}}
{{--                                                                                 href="#!">Raven Pro</a></h6>--}}
{{--                                                <p class="fw-semi-bold mb-0 text-500">Landing</p>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                    </td>--}}
{{--                                    <td class="align-middle text-end fw-semi-bold">$1311</td>--}}
{{--                                    <td class="align-middle pe-x1">--}}
{{--                                        <div class="d-flex align-items-center">--}}
{{--                                            <div class="progress me-3 rounded-3 bg-200"--}}
{{--                                                 style="height: 5px; width:80px;" role="progressbar"--}}
{{--                                                 aria-valuenow="39" aria-valuemin="0" aria-valuemax="100">--}}
{{--                                                <div class="progress-bar rounded-pill" style="width: 39%;"></div>--}}
{{--                                            </div>--}}
{{--                                            <div class="fw-semi-bold ms-2">39%</div>--}}
{{--                                        </div>--}}
{{--                                    </td>--}}
{{--                                </tr>--}}
{{--                                <tr class="border-bottom border-200">--}}
{{--                                    <td>--}}
{{--                                        <div class="d-flex align-items-center position-relative"><img--}}
{{--                                                class="rounded-1 border border-200" src="assets/img/products/10.png"--}}
{{--                                                width="60" alt=""/>--}}
{{--                                            <div class="flex-1 ms-3">--}}
{{--                                                <h6 class="mb-1 fw-semi-bold"><a class="text-1100 stretched-link"--}}
{{--                                                                                 href="#!">Boots4</a></h6>--}}
{{--                                                <p class="fw-semi-bold mb-0 text-500">Portfolio</p>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                    </td>--}}
{{--                                    <td class="align-middle text-end fw-semi-bold">$860</td>--}}
{{--                                    <td class="align-middle pe-x1">--}}
{{--                                        <div class="d-flex align-items-center">--}}
{{--                                            <div class="progress me-3 rounded-3 bg-200"--}}
{{--                                                 style="height: 5px; width:80px;" role="progressbar"--}}
{{--                                                 aria-valuenow="26" aria-valuemin="0" aria-valuemax="100">--}}
{{--                                                <div class="progress-bar rounded-pill" style="width: 26%;"></div>--}}
{{--                                            </div>--}}
{{--                                            <div class="fw-semi-bold ms-2">26%</div>--}}
{{--                                        </div>--}}
{{--                                    </td>--}}
{{--                                </tr>--}}
{{--                                <tr class="border-bottom border-200">--}}
{{--                                    <td>--}}
{{--                                        <div class="d-flex align-items-center position-relative"><img--}}
{{--                                                class="rounded-1 border border-200" src="assets/img/products/11.png"--}}
{{--                                                width="60" alt=""/>--}}
{{--                                            <div class="flex-1 ms-3">--}}
{{--                                                <h6 class="mb-1 fw-semi-bold"><a class="text-1100 stretched-link"--}}
{{--                                                                                 href="#!">Falcon</a></h6>--}}
{{--                                                <p class="fw-semi-bold mb-0 text-500">Admin</p>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                    </td>--}}
{{--                                    <td class="align-middle text-end fw-semi-bold">$539</td>--}}
{{--                                    <td class="align-middle pe-x1">--}}
{{--                                        <div class="d-flex align-items-center">--}}
{{--                                            <div class="progress me-3 rounded-3 bg-200"--}}
{{--                                                 style="height: 5px; width:80px;" role="progressbar"--}}
{{--                                                 aria-valuenow="16" aria-valuemin="0" aria-valuemax="100">--}}
{{--                                                <div class="progress-bar rounded-pill" style="width: 16%;"></div>--}}
{{--                                            </div>--}}
{{--                                            <div class="fw-semi-bold ms-2">16%</div>--}}
{{--                                        </div>--}}
{{--                                    </td>--}}
{{--                                </tr>--}}
{{--                                <tr class="border-bottom border-200">--}}
{{--                                    <td>--}}
{{--                                        <div class="d-flex align-items-center position-relative"><img--}}
{{--                                                class="rounded-1 border border-200" src="assets/img/products/14.png"--}}
{{--                                                width="60" alt=""/>--}}
{{--                                            <div class="flex-1 ms-3">--}}
{{--                                                <h6 class="mb-1 fw-semi-bold"><a class="text-1100 stretched-link"--}}
{{--                                                                                 href="#!">Slick</a></h6>--}}
{{--                                                <p class="fw-semi-bold mb-0 text-500">Builder</p>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                    </td>--}}
{{--                                    <td class="align-middle text-end fw-semi-bold">$343</td>--}}
{{--                                    <td class="align-middle pe-x1">--}}
{{--                                        <div class="d-flex align-items-center">--}}
{{--                                            <div class="progress me-3 rounded-3 bg-200"--}}
{{--                                                 style="height: 5px; width:80px;" role="progressbar"--}}
{{--                                                 aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">--}}
{{--                                                <div class="progress-bar rounded-pill" style="width: 10%;"></div>--}}
{{--                                            </div>--}}
{{--                                            <div class="fw-semi-bold ms-2">10%</div>--}}
{{--                                        </div>--}}
{{--                                    </td>--}}
{{--                                </tr>--}}
{{--                                <tr>--}}
{{--                                    <td>--}}
{{--                                        <div class="d-flex align-items-center position-relative"><img--}}
{{--                                                class="rounded-1 border border-200" src="assets/img/products/13.png"--}}
{{--                                                width="60" alt=""/>--}}
{{--                                            <div class="flex-1 ms-3">--}}
{{--                                                <h6 class="mb-1 fw-semi-bold"><a class="text-1100 stretched-link"--}}
{{--                                                                                 href="#!">Reign Pro</a></h6>--}}
{{--                                                <p class="fw-semi-bold mb-0 text-500">Agency</p>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                    </td>--}}
{{--                                    <td class="align-middle text-end fw-semi-bold">$280</td>--}}
{{--                                    <td class="align-middle pe-x1">--}}
{{--                                        <div class="d-flex align-items-center">--}}
{{--                                            <div class="progress me-3 rounded-3 bg-200"--}}
{{--                                                 style="height: 5px; width:80px;" role="progressbar"--}}
{{--                                                 aria-valuenow="8" aria-valuemin="0" aria-valuemax="100">--}}
{{--                                                <div class="progress-bar rounded-pill" style="width: 8%;"></div>--}}
{{--                                            </div>--}}
{{--                                            <div class="fw-semi-bold ms-2">8%</div>--}}
{{--                                        </div>--}}
{{--                                    </td>--}}
{{--                                </tr>--}}
{{--                                </tbody>--}}
{{--                            </table>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="card-footer bg-body-tertiary py-2">--}}
{{--                        <div class="row flex-between-center">--}}
{{--                            <div class="col-auto">--}}
{{--                                <select class="form-select form-select-sm">--}}
{{--                                    <option>Last 7 days</option>--}}
{{--                                    <option>Last Month</option>--}}
{{--                                    <option>Last Year</option>--}}
{{--                                </select>--}}
{{--                            </div>--}}
{{--                            <div class="col-auto"><a class="btn btn-sm btn-falcon-default" href="#!">View All</a>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="col-lg-5 col-xl-4 ps-lg-2 mb-3">--}}
{{--                <div class="card h-100">--}}
{{--                    <div class="card-header d-flex flex-between-center bg-body-tertiary py-2">--}}
{{--                        <h6 class="mb-0">Shared Files</h6><a class="py-1 fs-10 font-sans-serif" href="#!">View--}}
{{--                            All</a>--}}
{{--                    </div>--}}
{{--                    <div class="card-body pb-0">--}}
{{--                        <div class="d-flex mb-3 hover-actions-trigger align-items-center">--}}
{{--                            <div class="file-thumbnail"><img class="border h-100 w-100 object-fit-cover rounded-2"--}}
{{--                                                             src="assets/img/products/5-thumb.png" alt=""/>--}}
{{--                            </div>--}}
{{--                            <div class="ms-3 flex-shrink-1 flex-grow-1">--}}
{{--                                <h6 class="mb-1"><a class="stretched-link text-900 fw-semi-bold" href="#!">apple-smart-watch.png</a>--}}
{{--                                </h6>--}}
{{--                                <div class="fs-10"><span class="fw-semi-bold">Antony</span><span--}}
{{--                                        class="fw-medium text-600 ms-2">Just Now</span></div>--}}
{{--                                <div class="hover-actions end-0 top-50 translate-middle-y"><a--}}
{{--                                        class="btn btn-tertiary border-300 btn-sm me-1 text-600"--}}
{{--                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Download"--}}
{{--                                        href="assets/img/icons/cloud-download.svg" download="download"><img--}}
{{--                                            src="assets/img/icons/cloud-download.svg" alt="" width="15"/></a>--}}
{{--                                    <button class="btn btn-tertiary border-300 btn-sm me-1 text-600 shadow-none"--}}
{{--                                            type="button" data-bs-toggle="tooltip" data-bs-placement="top"--}}
{{--                                            title="Edit"><img src="assets/img/icons/edit-alt.svg" alt=""--}}
{{--                                                              width="15"/></button>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <hr class="text-200"/>--}}
{{--                        <div class="d-flex mb-3 hover-actions-trigger align-items-center">--}}
{{--                            <div class="file-thumbnail"><img class="border h-100 w-100 object-fit-cover rounded-2"--}}
{{--                                                             src="assets/img/products/3-thumb.png" alt=""/>--}}
{{--                            </div>--}}
{{--                            <div class="ms-3 flex-shrink-1 flex-grow-1">--}}
{{--                                <h6 class="mb-1"><a class="stretched-link text-900 fw-semi-bold"--}}
{{--                                                    href="#!">iphone.jpg</a>--}}
{{--                                </h6>--}}
{{--                                <div class="fs-10"><span class="fw-semi-bold">Antony</span><span--}}
{{--                                        class="fw-medium text-600 ms-2">Yesterday at 1:30 PM</span></div>--}}
{{--                                <div class="hover-actions end-0 top-50 translate-middle-y"><a--}}
{{--                                        class="btn btn-tertiary border-300 btn-sm me-1 text-600"--}}
{{--                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Download"--}}
{{--                                        href="assets/img/icons/cloud-download.svg" download="download"><img--}}
{{--                                            src="assets/img/icons/cloud-download.svg" alt="" width="15"/></a>--}}
{{--                                    <button class="btn btn-tertiary border-300 btn-sm me-1 text-600 shadow-none"--}}
{{--                                            type="button" data-bs-toggle="tooltip" data-bs-placement="top"--}}
{{--                                            title="Edit"><img src="assets/img/icons/edit-alt.svg" alt=""--}}
{{--                                                              width="15"/></button>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <hr class="text-200"/>--}}
{{--                        <div class="d-flex mb-3 hover-actions-trigger align-items-center">--}}
{{--                            <div class="file-thumbnail"><img class="img-fluid" src="assets/img/icons/zip.png"--}}
{{--                                                             alt=""/>--}}
{{--                            </div>--}}
{{--                            <div class="ms-3 flex-shrink-1 flex-grow-1">--}}
{{--                                <h6 class="mb-1"><a class="stretched-link text-900 fw-semi-bold" href="#!">Falcon--}}
{{--                                        v1.8.2</a></h6>--}}
{{--                                <div class="fs-10"><span class="fw-semi-bold">Jane</span><span--}}
{{--                                        class="fw-medium text-600 ms-2">27 Sep at 10:30 AM</span></div>--}}
{{--                                <div class="hover-actions end-0 top-50 translate-middle-y"><a--}}
{{--                                        class="btn btn-tertiary border-300 btn-sm me-1 text-600"--}}
{{--                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Download"--}}
{{--                                        href="assets/img/icons/cloud-download.svg" download="download"><img--}}
{{--                                            src="assets/img/icons/cloud-download.svg" alt="" width="15"/></a>--}}
{{--                                    <button class="btn btn-tertiary border-300 btn-sm me-1 text-600 shadow-none"--}}
{{--                                            type="button" data-bs-toggle="tooltip" data-bs-placement="top"--}}
{{--                                            title="Edit"><img src="assets/img/icons/edit-alt.svg" alt=""--}}
{{--                                                              width="15"/></button>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <hr class="text-200"/>--}}
{{--                        <div class="d-flex mb-3 hover-actions-trigger align-items-center">--}}
{{--                            <div class="file-thumbnail"><img class="border h-100 w-100 object-fit-cover rounded-2"--}}
{{--                                                             src="assets/img/products/2-thumb.png" alt=""/>--}}
{{--                            </div>--}}
{{--                            <div class="ms-3 flex-shrink-1 flex-grow-1">--}}
{{--                                <h6 class="mb-1"><a class="stretched-link text-900 fw-semi-bold"--}}
{{--                                                    href="#!">iMac.jpg</a></h6>--}}
{{--                                <div class="fs-10"><span class="fw-semi-bold">Rowen</span><span--}}
{{--                                        class="fw-medium text-600 ms-2">23 Sep at 6:10 PM</span></div>--}}
{{--                                <div class="hover-actions end-0 top-50 translate-middle-y"><a--}}
{{--                                        class="btn btn-tertiary border-300 btn-sm me-1 text-600"--}}
{{--                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Download"--}}
{{--                                        href="assets/img/icons/cloud-download.svg" download="download"><img--}}
{{--                                            src="assets/img/icons/cloud-download.svg" alt="" width="15"/></a>--}}
{{--                                    <button class="btn btn-tertiary border-300 btn-sm me-1 text-600 shadow-none"--}}
{{--                                            type="button" data-bs-toggle="tooltip" data-bs-placement="top"--}}
{{--                                            title="Edit"><img src="assets/img/icons/edit-alt.svg" alt=""--}}
{{--                                                              width="15"/></button>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <hr class="text-200"/>--}}
{{--                        <div class="d-flex mb-3 hover-actions-trigger align-items-center">--}}
{{--                            <div class="file-thumbnail"><img class="img-fluid" src="assets/img/icons/docs.png"--}}
{{--                                                             alt=""/>--}}
{{--                            </div>--}}
{{--                            <div class="ms-3 flex-shrink-1 flex-grow-1">--}}
{{--                                <h6 class="mb-1"><a class="stretched-link text-900 fw-semi-bold"--}}
{{--                                                    href="#!">functions.php</a>--}}
{{--                                </h6>--}}
{{--                                <div class="fs-10"><span class="fw-semi-bold">John</span><span--}}
{{--                                        class="fw-medium text-600 ms-2">1 Oct at 4:30 PM</span></div>--}}
{{--                                <div class="hover-actions end-0 top-50 translate-middle-y"><a--}}
{{--                                        class="btn btn-tertiary border-300 btn-sm me-1 text-600"--}}
{{--                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Download"--}}
{{--                                        href="assets/img/icons/cloud-download.svg" download="download"><img--}}
{{--                                            src="assets/img/icons/cloud-download.svg" alt="" width="15"/></a>--}}
{{--                                    <button class="btn btn-tertiary border-300 btn-sm me-1 text-600 shadow-none"--}}
{{--                                            type="button" data-bs-toggle="tooltip" data-bs-placement="top"--}}
{{--                                            title="Edit"><img src="assets/img/icons/edit-alt.svg" alt=""--}}
{{--                                                              width="15"/></button>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <div class="row g-0">--}}
{{--            <div class="col-md-6 col-xxl-3 pe-md-2 mb-3 mb-xxl-0">--}}
{{--                <div class="card">--}}
{{--                    <div class="card-header d-flex flex-between-center bg-body-tertiary py-2">--}}
{{--                        <h6 class="mb-0">Active Users</h6>--}}
{{--                        <div class="dropdown font-sans-serif btn-reveal-trigger">--}}
{{--                            <button--}}
{{--                                class="btn btn-link text-600 btn-sm dropdown-toggle dropdown-caret-none btn-reveal"--}}
{{--                                type="button" id="dropdown-active-user" data-bs-toggle="dropdown"--}}
{{--                                data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><span--}}
{{--                                    class="fas fa-ellipsis-h fs-11"></span></button>--}}
{{--                            <div class="dropdown-menu dropdown-menu-end border py-2"--}}
{{--                                 aria-labelledby="dropdown-active-user"><a class="dropdown-item"--}}
{{--                                                                           href="#!">View</a><a--}}
{{--                                    class="dropdown-item" href="#!">Export</a>--}}
{{--                                <div class="dropdown-divider"></div>--}}
{{--                                <a class="dropdown-item text-danger" href="#!">Remove</a>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="card-body py-2">--}}
{{--                        <div class="d-flex align-items-center position-relative mb-3">--}}
{{--                            <div class="avatar avatar-2xl status-online">--}}
{{--                                <img class="rounded-circle" src="assets/img/team/1.jpg" alt=""/>--}}

{{--                            </div>--}}
{{--                            <div class="flex-1 ms-3">--}}
{{--                                <h6 class="mb-0 fw-semi-bold"><a class="stretched-link text-900"--}}
{{--                                                                 href="pages/user/profile.html">Emma Watson</a></h6>--}}
{{--                                <p class="text-500 fs-11 mb-0">Admin</p>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="d-flex align-items-center position-relative mb-3">--}}
{{--                            <div class="avatar avatar-2xl status-online">--}}
{{--                                <img class="rounded-circle" src="assets/img/team/2.jpg" alt=""/>--}}

{{--                            </div>--}}
{{--                            <div class="flex-1 ms-3">--}}
{{--                                <h6 class="mb-0 fw-semi-bold"><a class="stretched-link text-900"--}}
{{--                                                                 href="pages/user/profile.html">Antony Hopkins</a>--}}
{{--                                </h6>--}}
{{--                                <p class="text-500 fs-11 mb-0">Moderator</p>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="d-flex align-items-center position-relative mb-3">--}}
{{--                            <div class="avatar avatar-2xl status-away">--}}
{{--                                <img class="rounded-circle" src="assets/img/team/3.jpg" alt=""/>--}}

{{--                            </div>--}}
{{--                            <div class="flex-1 ms-3">--}}
{{--                                <h6 class="mb-0 fw-semi-bold"><a class="stretched-link text-900"--}}
{{--                                                                 href="pages/user/profile.html">Anna Karinina</a>--}}
{{--                                </h6>--}}
{{--                                <p class="text-500 fs-11 mb-0">Editor</p>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="d-flex align-items-center position-relative mb-3">--}}
{{--                            <div class="avatar avatar-2xl status-offline">--}}
{{--                                <img class="rounded-circle" src="assets/img/team/4.jpg" alt=""/>--}}

{{--                            </div>--}}
{{--                            <div class="flex-1 ms-3">--}}
{{--                                <h6 class="mb-0 fw-semi-bold"><a class="stretched-link text-900"--}}
{{--                                                                 href="pages/user/profile.html">John Lee</a></h6>--}}
{{--                                <p class="text-500 fs-11 mb-0">Admin</p>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="d-flex align-items-center position-relative false">--}}
{{--                            <div class="avatar avatar-2xl status-offline">--}}
{{--                                <img class="rounded-circle" src="assets/img/team/5.jpg" alt=""/>--}}

{{--                            </div>--}}
{{--                            <div class="flex-1 ms-3">--}}
{{--                                <h6 class="mb-0 fw-semi-bold"><a class="stretched-link text-900"--}}
{{--                                                                 href="pages/user/profile.html">Rowen Atkinson</a>--}}
{{--                                </h6>--}}
{{--                                <p class="text-500 fs-11 mb-0">Editor</p>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="card-footer bg-body-tertiary p-0"><a class="btn btn-sm btn-link d-block w-100 py-2"--}}
{{--                                                                     href="app/social/followers.html">All active--}}
{{--                            users<span class="fas fa-chevron-right ms-1 fs-11"></span></a></div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="col-md-6 col-xxl-3 ps-md-2 order-xxl-1 mb-3 mb-xxl-0">--}}
{{--                <div class="card h-100">--}}
{{--                    <div class="card-header bg-body-tertiary d-flex flex-between-center py-2">--}}
{{--                        <h6 class="mb-0">Bandwidth Saved</h6>--}}
{{--                        <div class="dropdown font-sans-serif btn-reveal-trigger">--}}
{{--                            <button--}}
{{--                                class="btn btn-link text-600 btn-sm dropdown-toggle dropdown-caret-none btn-reveal"--}}
{{--                                type="button" id="dropdown-bandwidth-saved" data-bs-toggle="dropdown"--}}
{{--                                data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><span--}}
{{--                                    class="fas fa-ellipsis-h fs-11"></span></button>--}}
{{--                            <div class="dropdown-menu dropdown-menu-end border py-2"--}}
{{--                                 aria-labelledby="dropdown-bandwidth-saved"><a class="dropdown-item"--}}
{{--                                                                               href="#!">View</a><a--}}
{{--                                    class="dropdown-item" href="#!">Export</a>--}}
{{--                                <div class="dropdown-divider"></div>--}}
{{--                                <a class="dropdown-item text-danger" href="#!">Remove</a>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="card-body d-flex flex-center flex-column">--}}
{{--                        <!-- Find the JS file for the following chart at: src/js/charts/echarts/bandwidth-saved.js-->--}}
{{--                        <!-- If you are not using gulp based workflow, you can find the transpiled code at: public/assets/js/theme.js-->--}}
{{--                        <div class="echart-bandwidth-saved" data-echart-responsive="true"></div>--}}
{{--                        <div class="text-center mt-3">--}}
{{--                            <h6 class="fs-9 mb-1"><span class="fas fa-check text-success me-1"--}}
{{--                                                        data-fa-transform="shrink-2"></span>35.75 GB saved</h6>--}}
{{--                            <p class="fs-10 mb-0">38.44 GB total bandwidth</p>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="card-footer bg-body-tertiary py-2">--}}
{{--                        <div class="row flex-between-center">--}}
{{--                            <div class="col-auto">--}}
{{--                                <select class="form-select form-select-sm">--}}
{{--                                    <option>Last 6 Months</option>--}}
{{--                                    <option>Last Year</option>--}}
{{--                                    <option>Last 2 Year</option>--}}
{{--                                </select>--}}
{{--                            </div>--}}
{{--                            <div class="col-auto"><a class="fs-10 font-sans-serif" href="#!">Help</a></div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="col-xxl-6 px-xxl-2">--}}
{{--                <div class="card h-100">--}}
{{--                    <div class="card-header bg-body-tertiary py-2">--}}
{{--                        <div class="row flex-between-center">--}}
{{--                            <div class="col-auto">--}}
{{--                                <h6 class="mb-0">Top Products</h6>--}}
{{--                            </div>--}}
{{--                            <div class="col-auto d-flex"><a class="btn btn-link btn-sm me-2" href="#!">View--}}
{{--                                    Details</a>--}}
{{--                                <div class="dropdown font-sans-serif btn-reveal-trigger">--}}
{{--                                    <button--}}
{{--                                        class="btn btn-link text-600 btn-sm dropdown-toggle dropdown-caret-none btn-reveal"--}}
{{--                                        type="button" id="dropdown-top-products" data-bs-toggle="dropdown"--}}
{{--                                        data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><span--}}
{{--                                            class="fas fa-ellipsis-h fs-11"></span></button>--}}
{{--                                    <div class="dropdown-menu dropdown-menu-end border py-2"--}}
{{--                                         aria-labelledby="dropdown-top-products"><a class="dropdown-item"--}}
{{--                                                                                    href="#!">View</a><a--}}
{{--                                            class="dropdown-item" href="#!">Export</a>--}}
{{--                                        <div class="dropdown-divider"></div>--}}
{{--                                        <a class="dropdown-item text-danger" href="#!">Remove</a>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="card-body h-100">--}}
{{--                        <!-- Find the JS file for the following chart at: src/js/charts/echarts/top-products.js-->--}}
{{--                        <!-- If you are not using gulp based workflow, you can find the transpiled code at: public/assets/js/theme.js-->--}}
{{--                        <div class="echart-bar-top-products h-100" data-echart-responsive="true"></div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <div class="row g-0 my-3">--}}
{{--            <div class="col-12">--}}
{{--                <div class="card overflow-hidden">--}}
{{--                    <div class="card-header">--}}
{{--                        <div class="row gx-0 align-items-center">--}}
{{--                            <div class="col-auto d-flex justify-content-end order-md-1">--}}
{{--                                <button class="btn icon-item icon-item-sm shadow-none p-0 me-1 ms-md-2" type="button"--}}
{{--                                        data-event="prev" data-bs-toggle="tooltip" title="Previous"><span--}}
{{--                                        class="fas fa-arrow-left"></span></button>--}}
{{--                                <button class="btn icon-item icon-item-sm shadow-none p-0 me-1 me-lg-2" type="button"--}}
{{--                                        data-event="next" data-bs-toggle="tooltip" title="Next"><span--}}
{{--                                        class="fas fa-arrow-right"></span></button>--}}
{{--                            </div>--}}
{{--                            <div class="col-auto col-md-auto order-md-2">--}}
{{--                                <h4 class="mb-0 fs-9 fs-sm-8 fs-lg-7 calendar-title"></h4>--}}
{{--                            </div>--}}
{{--                            <div class="col col-md-auto d-flex justify-content-end order-md-3">--}}
{{--                                <button class="btn btn-falcon-primary btn-sm" type="button" data-event="today">Today--}}
{{--                                </button>--}}
{{--                            </div>--}}
{{--                            <div class="col-md-auto d-md-none">--}}
{{--                                <hr/>--}}
{{--                            </div>--}}
{{--                            <div class="col-auto d-flex order-md-0">--}}
{{--                                <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal"--}}
{{--                                        data-bs-target="#addEventModal"><span class="fas fa-plus me-2"></span>Add--}}
{{--                                    Schedule--}}
{{--                                </button>--}}
{{--                            </div>--}}
{{--                            <div class="col d-flex justify-content-end order-md-2">--}}
{{--                                <div class="dropdown font-sans-serif me-md-2">--}}
{{--                                    <button--}}
{{--                                        class="btn btn-falcon-default text-600 btn-sm dropdown-toggle dropdown-caret-none"--}}
{{--                                        type="button" id="email-filter" data-bs-toggle="dropdown"--}}
{{--                                        data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><span--}}
{{--                                            data-view-title="data-view-title">Month View</span><span--}}
{{--                                            class="fas fa-sort ms-2 fs-10"></span></button>--}}
{{--                                    <div class="dropdown-menu dropdown-menu-end border py-2"--}}
{{--                                         aria-labelledby="email-filter"><a--}}
{{--                                            class="active dropdown-item d-flex justify-content-between" href="#!"--}}
{{--                                            data-fc-view="dayGridMonth">Month View<span class="icon-check"><span--}}
{{--                                                    class="fas fa-check"--}}
{{--                                                    data-fa-transform="down-4 shrink-4"></span></span></a><a--}}
{{--                                            class="dropdown-item d-flex justify-content-between" href="#!"--}}
{{--                                            data-fc-view="timeGridWeek">Week View<span class="icon-check"><span--}}
{{--                                                    class="fas fa-check"--}}
{{--                                                    data-fa-transform="down-4 shrink-4"></span></span></a><a--}}
{{--                                            class="dropdown-item d-flex justify-content-between" href="#!"--}}
{{--                                            data-fc-view="timeGridDay">Day View<span class="icon-check"><span--}}
{{--                                                    class="fas fa-check"--}}
{{--                                                    data-fa-transform="down-4 shrink-4"></span></span></a><a--}}
{{--                                            class="dropdown-item d-flex justify-content-between" href="#!"--}}
{{--                                            data-fc-view="listWeek">List View<span class="icon-check"><span--}}
{{--                                                    class="fas fa-check"--}}
{{--                                                    data-fa-transform="down-4 shrink-4"></span></span></a><a--}}
{{--                                            class="dropdown-item d-flex justify-content-between" href="#!"--}}
{{--                                            data-fc-view="listYear">Year View<span class="icon-check"><span--}}
{{--                                                    class="fas fa-check"--}}
{{--                                                    data-fa-transform="down-4 shrink-4"></span></span></a>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="card-body p-0 scrollbar">--}}
{{--                        <div class="calendar-outline" id="appCalendar"></div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}

{{--        <div class="modal fade" id="eventDetailsModal" tabindex="-1">--}}
{{--            <div class="modal-dialog modal-dialog-centered">--}}
{{--                <div class="modal-content border"></div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <div class="modal fade" id="addEventModal" tabindex="-1">--}}
{{--            <div class="modal-dialog">--}}
{{--                <div class="modal-content border">--}}
{{--                    <form id="addEventForm" autocomplete="off">--}}
{{--                        <div class="modal-header px-x1 bg-body-tertiary border-bottom-0">--}}
{{--                            <h5 class="modal-title">Create Schedule</h5>--}}
{{--                            <button class="btn-close me-n1" type="button" data-bs-dismiss="modal" aria-label="Close"></button>--}}
{{--                        </div>--}}
{{--                        <div class="modal-body p-x1">--}}
{{--                            <div class="mb-3">--}}
{{--                                <label class="fs-9" for="eventTitle">Title</label>--}}
{{--                                <input class="form-control" id="eventTitle" type="text" name="title" required="required" />--}}
{{--                            </div>--}}
{{--                            <div class="mb-3">--}}
{{--                                <label class="fs-9" for="eventStartDate">Start Date</label>--}}
{{--                                <input class="form-control datetimepicker" id="eventStartDate" type="text" required="required" name="startDate" placeholder="yyyy/mm/dd hh:mm" data-options='{"static":"true","enableTime":"true","dateFormat":"Y-m-d H:i"}' />--}}
{{--                            </div>--}}
{{--                            <div class="mb-3">--}}
{{--                                <label class="fs-9" for="eventEndDate">End Date</label>--}}
{{--                                <input class="form-control datetimepicker" id="eventEndDate" type="text" name="endDate" placeholder="yyyy/mm/dd hh:mm" data-options='{"static":"true","enableTime":"true","dateFormat":"Y-m-d H:i"}' />--}}
{{--                            </div>--}}
{{--                            <div class="form-check">--}}
{{--                                <input class="form-check-input" type="checkbox" id="eventAllDay" name="allDay" />--}}
{{--                                <label class="form-check-label" for="eventAllDay">All Day--}}
{{--                                </label>--}}
{{--                            </div>--}}
{{--                            <div class="mb-3">--}}
{{--                                <label class="fs-9">Schedule Meeting</label>--}}
{{--                                <div><a class="btn badge-subtle-success btn-sm" href="#!"><span class="fas fa-video me-2"></span>Add video conference link</a></div>--}}
{{--                            </div>--}}
{{--                            <div class="mb-3">--}}
{{--                                <label class="fs-9" for="eventDescription">Description</label>--}}
{{--                                <textarea class="form-control" rows="3" name="description" id="eventDescription"></textarea>--}}
{{--                            </div>--}}
{{--                            <div class="mb-3">--}}
{{--                                <label class="fs-9" for="eventLabel">Label</label>--}}
{{--                                <select class="form-select" id="eventLabel" name="label">--}}
{{--                                    <option value="" selected="selected">None</option>--}}
{{--                                    <option value="primary">Business</option>--}}
{{--                                    <option value="danger">Important</option>--}}
{{--                                    <option value="success">Personal</option>--}}
{{--                                    <option value="warning">Must Attend</option>--}}
{{--                                </select>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="modal-footer d-flex justify-content-end align-items-center bg-body-tertiary border-0"><a class="me-3 text-600" href="../app/events/create-an-event.html">More options</a>--}}
{{--                            <button class="btn btn-primary px-4" type="submit">Save</button>--}}
{{--                        </div>--}}
{{--                    </form>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    @endsection--}}

{{--    @push('scripts')--}}
{{--        <script src="{{asset('assets/vendors/fullcalendar/index.global.min.js')}}"></script>--}}
{{--        <script src="{{asset('assets/vendors/flatpickr/flatpickr.min.js')}}"></script>--}}
{{--        <script src="{{asset('assets/vendors/dayjs/dayjs.min.js')}}"></script>--}}
{{--    @endpush--}}
{{--</x-app-layout>--}}

