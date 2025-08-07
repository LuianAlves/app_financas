<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login — Financeiro</title>

    <link rel="manifest" href="{{ asset('laravelpwa/manifest.json') }}">
    <meta name="theme-color" content="#00bfa6">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Flatpickr -->
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

    @stack('styles')
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
</head>
<body>

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
                <input class="form-check-input" type="checkbox" id="card-checkbox" name="remember" />
                <label class="form-check-label" for="card-checkbox">Lembrar-me</label>
            </div>

            <button class="btn btn-primary d-block w-100 mt-3" type="submit">Entrar</button>
        </form>
    </div>
</div>

@stack('scripts')
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker
            .register('{{ asset('laravelpwa/sw.js') }}')
            .catch(e => console.error('SW falhou', e));
    }
</script>
</body>
</html>
