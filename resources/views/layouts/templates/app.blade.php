<!doctype html>
<html lang="pt-br" class="h-full antialiased">

<!-- Include:head -->
@include('layouts.partials.head')

<body
    class="min-h-screen text-neutral-900 dark:text-neutral-100 bg-white dark:bg-gradient-to-b dark:from-neutral-950 dark:to-neutral-900 selection:bg-brand-200 selection:text-neutral-900">

<a href="#conteudo"
   class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 bg-white dark:bg-neutral-800 text-sm px-3 py-2 rounded-lg shadow-soft dark:shadow-softDark">Pular
    para o conteúdo</a>

<div class="md:grid md:grid-cols-[260px_1fr] md:min-h-screen">
    @include('layouts.partials.sidenav')

    <div class="relative flex flex-col min-h-screen md:min-h-0">
        @include('layouts.partials.navbar')

        <main id="conteudo"
              class="flex-1 max-w-7xl mx-auto w-full px-4 pb-[calc(5.5rem+env(safe-area-inset-bottom))] md:pb-8 md:pt-6">
            @yield('new-content')
        </main>
    </div>
</div>

@include('layouts.partials.bottom_nav')

@include('layouts.partials.scripts')

</body>
</html>

