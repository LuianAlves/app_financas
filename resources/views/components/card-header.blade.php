<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">--</li>
    </ol>
</nav>

<div class="card my-3" id="customersTable">
    <div class="card-header">
        <div class="row flex-between-center">
            <div class="col-4 col-sm-auto d-flex align-items-center pe-0">
                <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">{{$title}}</h5>
            </div>

            <div class="col-8 col-sm-auto text-end ps-2">
                <div id="table-customers-replace-element d-flex align-items-center">
                    @if(currentRoute()[1] == 'index')
{{--                        --}}
{{--                        <button class="btn btn-falcon-default btn-sm mx-2" type="button">--}}
{{--                            <span class="fas fa-filter" data-fa-transform="shrink-3 down-2"></span><span--}}
{{--                                class="d-none d-sm-inline-block">Filter</span></button>--}}
{{--                        <button class="btn btn-falcon-default btn-sm" type="button"><span--}}
{{--                                class="fas fa-external-link-alt" data-fa-transform="shrink-3 down-2"></span><span--}}
{{--                                class="d-none d-sm-inline-block ms-1">Export</span></button>--}}
                    @endif

                    <x-card-button button="{{$action}}"></x-card-button>
                </div>
            </div>
        </div>
    </div>
</div>
