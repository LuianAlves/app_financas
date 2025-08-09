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

        <!-- Bottom nav -->
        <div class="bottom-nav">
            <a href="{{route('dashboard')}}"><i class="fas fa-home"></i></a>
            <a href="{{ route('transaction-view.index') }}"><i class="fas fa-retweet"></i></a>
            <a href="{{ route('transaction-view.index') }}"><i class="fas fa-chart-line"></i></a>
            <i class="fas fa-user"></i>
        </div>
    </main>
</div>

<!-- Include:scripts -->
@include('layouts.partials.scripts')
@stack('scripts')

</body>

</html>

{{--<!-- Bottom nav -->--}}
{{--<div class="bottom-nav">--}}
{{--    <a href="{{route('dashboard')}}"><i class="fas fa-home"></i></a>--}}
{{--    <a href="{{ route('transaction-view.index') }}"><i class="fas fa-retweet"></i></a>--}}
{{--    <a href="{{ route('transaction-view.index') }}"><i class="fas fa-chart-line"></i></a>--}}
{{--    <i class="fas fa-user"></i>--}}
{{--</div>--}}

{{--@push('scripts')--}}
{{--    <script>--}}
{{--        function changeMonth(delta) {--}}
{{--            const input = document.getElementById('monthPicker');--}}
{{--            const [year, month] = input.value.split('-').map(Number);--}}
{{--            const date = new Date(year, month - 1 + delta, 1);--}}
{{--            input.value = date.toISOString().slice(0, 7);--}}
{{--            input.form.submit();--}}
{{--        }--}}
{{--    </script>--}}
{{--@endpush--}}
