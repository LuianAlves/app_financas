<x-app-layout>
    @section('content')

        <x-card-header title="Nova conta" action="cadastrar"></x-card-header>

        <x-form route="store">
            @include('app.accounts.account_form')
        </x-form>

    @endsection
</x-app-layout>
