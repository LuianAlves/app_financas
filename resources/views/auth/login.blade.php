@extends('layouts.templates.app')
@section('content')
    <div class="login-container">
        <div class="login-card">
            <h2>Entrar</h2>
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="card-email">Endereço de e-mail</label>
                    <input class="form-control" id="card-email" type="email" name="email" required autofocus />
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <label class="form-label" for="card-password">Senha</label>
                        <a class="fs-7" href="{{ route('password.request') }}">Esqueceu a senha?</a>
                    </div>
                    <input class="form-control" id="card-password" type="password" name="password" required />
                </div>

                <div class="mb-3 form-check">
                    <input class="form-check-input" type="checkbox" id="card-checkbox" name="remember" value="1" checked />
                    <label class="form-check-label" for="card-checkbox">Lembrar-me</label>
                </div>

                <button class="btn btn-primary d-block w-100 mt-3" type="submit">Entrar</button>
            </form>
        </div>
    </div>
@endsection
