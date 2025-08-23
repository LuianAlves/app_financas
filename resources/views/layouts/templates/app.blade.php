<!DOCTYPE html>
<html lang="pt-BR">

<!-- Include:head -->
@include('layouts.partials.head')

<body>

<div class="app-container">
    <!-- Include:head -->
    @auth
        @include('layouts.partials.sidenav')
    @endauth

    <main id="app-main" class="content-area scroll-content" data-skeleton="tx-list">
        <button class="btn bg-color w-100 mb-3" style="letter-spacing: .75px; font-size: 12px; font-weight: 600;"
                data-install><i class="fa fa-download mx-2"></i>Baixe o aplicativo
        </button>
        <div id="ios-a2hs" class="alert alert-light d-none" role="alert" style="margin:8px 0">
            No iPhone: toque <strong>Compartilhar</strong> → <strong>Adicionar à Tela de Início</strong> para instalar o app.
        </div>

        <button id="ios-enable-push" class="btn btn-sm btn-primary d-none">Ativar notificações</button>

        @yield('content')

        @auth
            <div class="bottom-nav">
                <a href="{{route('dashboard')}}" class="bottom-nav-link" data-nav>
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="{{ route('transaction-view.index') }}" class="bottom-nav-link" data-nav>
                    <i class="fa-solid fa-cart-plus"></i>
                    <span>Transações</span>
                </a>
                <a href="{{ route('projection-view.index') }}" class="bottom-nav-link" data-nav>
                    <i class="fa-solid fa-arrow-up-right-dots"></i>
                    <span>Projeções</span>
                </a>
                <a href="{{route('user-view.index')}}" class="bottom-nav-link" data-nav>
                    <i class="fas fa-user"></i>
                    <span>Perfil</span>
                </a>
            </div>
        @endauth
    </main>
</div>

<!-- Include:scripts -->
@include('layouts.partials.scripts')

<script>
    window.__SPA_LITE__ = true;
</script>

<script src="{{asset('assets/js/cache/app-nav.js')}}"></script>
<script src="{{asset('assets/js/cache/http.js')}}"></script>
<script src="{{asset('assets/js/cache/storage.js')}}"></script>
<script src="{{asset('assets/js/install.js')}}" defer></script>
<div id="net-banner"
     style="display:none;position:fixed;left:50%;transform:translateX(-50%);bottom:85px;z-index:1200;background:#222;color:#fff;padding:6px 10px;border-radius:8px;font-size:12px;">
    Conexão lenta — exibindo dados em cache…
</div>


@stack('scripts')

</body>

</html>
