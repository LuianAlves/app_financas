@extends('layouts.templates.app')
@section('content')
    <x-card-header
        prevRoute="{{ route('dashboard') }}"
        iconRight="fa-solid fa-credit-card"
        title="Cartões de Crédito"
        description="Gerencie seus cartões e acompanhe seus limites e faturas."
    ></x-card-header>

    <button id="openModal" class="create-btn"><i class="fa fa-plus text-white"></i></button>

    <x-modal
        modalId="modalCard"
        formId="formCard"
        pathForm="app.cards.card_form"
        :data="['accounts' => $accounts]"
    ></x-modal>

    <div id="cardList" class="mt-4"></div>

    <script>
        const route = '{{route('cards.index')}}'
    </script>

    <script type="module" src="{{asset('assets/js/views/card.js')}}"></script>

    @push('styles')
        <style>
            #savingList {
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .balance-box {
                background-repeat: no-repeat;
                background-size: cover;
                width: 290px;
                height: 175px;
                border-radius: 12px;
                color: #fff;
                padding: 16px;
                position: relative;
                font-family: sans-serif;
                box-shadow: 1px 4px 8px rgba(0,0,0,0.2);
                background-position: center;
                margin-bottom: 16px;
            }
            .balance-box .card-brand {
                position: absolute;
                top: 16px;
                left: 16px;
                width: 35px;
            }
            .balance-box .card-chip {
                position: absolute;
                top: 7.5px;
                right: 16px;
                width: 35px;
            }

            .balance-box .card-details .detail-left, .balance-box .card-details .detail-right , .balance-box .card-number {
                letter-spacing: 2px;
                text-shadow: 1px 1px 1px #000;
            }

            .balance-box .card-number {
                margin-top: 40px;
                font-size: 12.5px;
            }

            .balance-box .card-details {
                position: absolute;
                bottom: 12px;
                left: 16px;
                right: 16px;
                display: flex;
                flex-direction: column;
                gap: 4px;
                font-size: 0.8rem;
                text-shadow: 1px 1px 1px #000;
            }

            .balance-box .card-details .detail-row {
                display: flex;
                justify-content: space-between;
                width: 100%;
            }

            .balance-box .card-details .detail-left {
                text-align: left;
                font-size: 10px;
                letter-spacing: 1px;
            }

            .balance-box .card-details .detail-right {
                text-align: right;
                font-size: 10px;
                letter-spacing: 1px;
            }

        </style>
    @endpush
@endsection
