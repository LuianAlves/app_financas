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

    <main class="content-area scroll-content">
        @yield('content')

        @auth
            <div class="bottom-nav">
                <a href="{{route('dashboard')}}" class="bottom-nav-link">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="{{ route('transaction-view.index') }}" class="bottom-nav-link">
                    <i class="fa-solid fa-cart-plus"></i>
                    <span>Transações</span>
                </a>
                <a href="{{ route('transaction-view.index') }}" class="bottom-nav-link">
                    <i class="fa-solid fa-arrow-up-right-dots"></i>
                    <span>Projeções</span>
                </a>
                <a href="{{route('user-view.index')}}" class="bottom-nav-link">
                    <i class="fas fa-user"></i>
                    <span>Perfil</span>
                </a>
            </div>
        @endauth
    </main>
</div>

<!-- Include:scripts -->
@include('layouts.partials.scripts')

@stack('scripts')

</body>

</html>
