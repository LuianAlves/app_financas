@extends('layouts.templates.app')
@section('content')
    <div class="login-container">
        <div class="login-card">
            <h2>Entrar</h2>
            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="card-name">Nome</label>
                    <input class="form-control" id="card-name" type="name" name="name" required />
                </div>

                <div class="mb-3">
                    <label class="form-label" for="card-email">Endereço de e-mail</label>
                    <input class="form-control" id="card-email" type="email" name="email" required autofocus />
                </div>

                <div class="mb-3">
                    <label class="form-label" for="card-password">Senha</label>
                    <input class="form-control" id="card-password" type="password" name="password" required />
                </div>

                <div class="mb-3">
                    <label class="form-label" for="card-password-confirmation">Confirmar senha</label>
                    <input class="form-control" id="card-password-confirmation" type="password" name="password_confirmation" required />
                </div>

                <button class="btn btn-primary d-block w-100 mt-3" type="submit">Registrar</button>
            </form>
        </div>
    </div>
@endsection
