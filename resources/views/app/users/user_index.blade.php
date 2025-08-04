<x-app-layout>
    @section('content')
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Library</li>
            </ol>
        </nav>

        <div class="card my-3" id="customersTable">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-4 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">Usuários</h5>
                    </div>

                    <div class="col-8 col-sm-auto text-end ps-2">
                        <div class="d-none" id="table-customers-actions">
                            <div class="d-flex">
                                <select class="form-select form-select-sm" aria-label="Bulk actions">
                                    <option selected="">Bulk actions</option>
                                    <option value="Refund">Refund</option>
                                    <option value="Delete">Delete</option>
                                    <option value="Archive">Archive</option>
                                </select>
                                <button class="btn btn-falcon-default btn-sm ms-2" type="button">Apply</button>
                            </div>
                        </div>
                        <div id="table-customers-replace-element">
                            <button class="btn btn-falcon-default btn-sm" type="button"><span class="fas fa-plus"
                                                                                              data-fa-transform="shrink-3 down-2"></span><span
                                    class="d-none d-sm-inline-block ms-1">New</span></button>
                            <button class="btn btn-falcon-default btn-sm mx-2" type="button"><span class="fas fa-filter"
                                                                                                   data-fa-transform="shrink-3 down-2"></span><span
                                    class="d-none d-sm-inline-block ms-1">Filter</span></button>
                            <button class="btn btn-falcon-default btn-sm" type="button"><span
                                    class="fas fa-external-link-alt" data-fa-transform="shrink-3 down-2"></span><span
                                    class="d-none d-sm-inline-block ms-1">Export</span></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive scrollbar">
                <table class="table table-hover table-striped overflow-hidden">
                    <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Phone</th>
                        <th scope="col">Address</th>
                        <th scope="col">Status</th>
                        <th class="text-end" scope="col"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="align-middle">
                        <td class="text-nowrap">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-xl">
                                    <img class="rounded-circle" src="../../assets/img/team/4.jpg" alt=""/>
                                </div>
                                <div class="ms-2">Ricky Antony</div>
                            </div>
                        </td>
                        <td class="text-nowrap">ricky@example.com</td>
                        <td class="text-nowrap">(201) 200-1851</td>
                        <td class="text-nowrap">2392 Main Avenue, Penasauka</td>
                        <td>
                            <span class="badge badge rounded-pill d-block p-2 badge-subtle-success">
                                Completed
                                <span class="ms-1 fas fa-check" data-fa-transform="shrink-2"></span></span>
                        </td>
                        <td class="text-end">
                            <div class="dropdown font-sans-serif position-static">
                                <button class="btn btn-link text-600 btn-sm dropdown-toggle btn-reveal" type="button"
                                        data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true"
                                        aria-expanded="false"><span class="fas fa-ellipsis-h fs-10"></span></button>
                                <div class="dropdown-menu dropdown-menu-end border py-0">
                                    <div class="py-2"><a class="dropdown-item" href="#!">Edit</a><a
                                            class="dropdown-item text-danger" href="#!">Delete</a></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endsection
</x-app-layout>
