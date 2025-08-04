<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">--</li>
    </ol>
</nav>

<div class="card border-bottom pb-0 my-3">
    <div class="d-sm-flex align-items-center px-3 py-3">
        <div class="d-flex align-items-center ">
            @if (currentRoute()[1] != 'index')
                <a href="{{ route(currentRoute()[0] . '.index') }}"><i class="fa fa-arrow-left"
                                                                       aria-hidden="true"></i></a>
            @endif
            <div class="mx-3">
                <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">{{$title}}</h5>
                @if ($action == 'novo')
                    <p class="text-sm mb-sm-0">Atualmente há {{ $count }} {{ strtolower($title) }} no sistema</p>
                @endif
            </div>
        </div>
        <div class="ms-auto d-flex">
            @if ($action == 'novo')
                <div class="input-group input-group-sm ms-auto me-2">
                     <span class="input-group-text text-body">
                         <svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" fill="none"
                              viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round"
                                   d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"></path>
                         </svg>
                     </span>
                    <input type="text" class="form-control form-control-sm" id="inputPesquisarTabela"
                           placeholder="Pesquisar ..">
                </div>

                {{--                        --}}
                {{--                        <button class="btn btn-falcon-default btn-sm mx-2" type="button">--}}
                {{--                            <span class="fas fa-filter" data-fa-transform="shrink-3 down-2"></span><span--}}
                {{--                                class="d-none d-sm-inline-block">Filter</span></button>--}}
                {{--                        <button class="btn btn-falcon-default btn-sm" type="button"><span--}}
                {{--                                class="fas fa-external-link-alt" data-fa-transform="shrink-3 down-2"></span><span--}}
                {{--                                class="d-none d-sm-inline-block ms-1">Export</span></button>--}}
                <x-card-button button="{{ $action }}"></x-card-button>
            @else
                <span class="fw-bold" style="font-size: 12px;">
                     <span class="text-muted"></span>
                 </span>
            @endif
        </div>
    </div>
</div>
