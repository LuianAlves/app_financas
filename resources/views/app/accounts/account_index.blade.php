@extends('layouts.templates.mobile')
@section('content-mobile')
    <div class="d-flex justify-content-between mb-3">
        <a href="{{route('dashboard')}}"><i class="fas fa-xmark text-dark" style="font-size: 22px;"></i></a>

            <i class="fa-solid fa-circle-question text-color" style="font-size: 22px;"></i>


    </div>


    <div class="header">
        <h1 class="mb-1">Contas Bancárias</h1>
        <p class="p-0 m-0 mb-3">Para uma melhor projeção, cadastre todas as suas contas bancárias atuais.</p>
        <div class="balance-box">
            <span>Saldo (todas as contas)</span>
            <strong>R$ 423,200.75</strong>
        </div>
    </div>

@endsection
