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
    </main>
</div>

<!-- Include:scripts -->
@include('layouts.partials.scripts')
@stack('scripts')

</body>

</html>
