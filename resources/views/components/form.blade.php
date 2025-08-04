@if ($route == 'store')
    <form method="post" action="{{ route(currentRoute()[0] . '.store') }}" enctype="multipart/form-data">
        @elseif($route == 'update')
            <form method="post" action="{{ route(currentRoute()[0] . '.update', $id) }}" enctype="multipart/form-data">
                @method('PATCH')
                @endif
                @csrf

                <div class="card p-3" style="height: calc(100vh - 32.5vh) !important; overflow-y: auto;">
                    <div class="card-header p-0 d-flex justify-content-between align-items-center">
                        @if ($route == 'store')
                            <blockquote class="blockquote border-warning">
                                <small class="text-muted mb-0 ps-2">Após o cadastro será redirecionado para a tela anterior.</small>
                            </blockquote>
                            <x-card-button button="cadastrar"></x-card-button>
                        @else
                            <blockquote class="blockquote border-warning">
                                <p class="text-muted mb-0 ps-2">Após a atualização será redirecionado para a tela anterior.</p>
                            </blockquote>

                            <x-card-button button="atualizar"></x-card-button>
                        @endif
                    </div>

                    {{ $slot }}
                </div>


            </form>
