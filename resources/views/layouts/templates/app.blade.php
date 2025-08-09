<!DOCTYPE html>
<html data-bs-theme="light" lang="pt-BR" dir="ltr">

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
                <a href="{{route('dashboard')}}"><i class="fas fa-home"></i></a>
                <a href="{{ route('transaction-view.index') }}"><i class="fas fa-retweet"></i></a>
                <a href="{{ route('transaction-view.index') }}"><i class="fas fa-chart-line"></i></a>
                <i class="fas fa-user"></i>
            </div>
        @endauth
    </main>
</div>

<!-- Include:scripts -->
@include('layouts.partials.scripts')
@stack('scripts')

</body>

</html>
