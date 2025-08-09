@extends('layouts.templates.app')
@section('content')

    <x-card-header
        prevRoute="{{ route('card-view.index') }}"
        iconRight="fa-solid fa-credit-card"
        title=""
        description=""
    ></x-card-header>

    <div class="icons-carousel">
        @foreach($invoices as $invoice)
            <div class="icon-button">
                <a href="{{ route('account-view.index') }}" class="nav-link-atalho">
                    <span class="bg-{{$invoice->paid ? 'success' : 'danger'}}">{{strtoupper($invoice->month)}}</span>
                    <b>{{$invoice->totalAmount}}</b>
                </a>
            </div>
        @endforeach
    </div>

    <div class="balance-box">
        <span class="fw-bold">Fatura atual</span>
        <strong>{{ $faturaAtual }}</strong>

        <span>Limite disponível <b>{{ $limiteDisponivel }}</b></span>
        <span class="closing-date">{!! $closeLabel !!}</span>
        <span class="due-date">{!! $dueLabel !!}</span>
    </div>

    @foreach($transactions as $t)
        <div class="transaction-card">
            <div class="transaction-info">
                <div class="icon">
                    <i class="fas fa-arrow-down text-white"></i>
                </div>
                <div class="details">
                    {{ $t->title }}
                    <br>
                    @if($t->date)
                        <span>{{ \Carbon\Carbon::parse($t->date)->format('d/m/Y') }}</span>
                    @endif
                </div>
            </div>
            <div class="transaction-amount">
                @php
                    $occ = $t->custom_occurrences;
                @endphp
                {{ $occ ? $occ . 'x ' : '' }}{{ brlPrice($t->amount/$occ) }}
            </div>
        </div>
    @endforeach
@endsection

@push('styles')
    <style>
        .icons-carousel {
            padding: 16px !important;
            margin-bottom: 10px !important;
            gap: 40px !important;
        }

        .balance-box {
            height: 20vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .icon-button span {
            background-color: #00bfa6;
            color: #fff;
            border-radius: 50%;
            padding: 12px;
            margin: 10px;
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
        }

        .closing-date {
            margin-top: 5px;
        }

        span {
            letter-spacing: 0.25px;
        }
    </style>

@endpush
