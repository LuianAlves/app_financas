<x-app-layout>
    @section('content')
        <x-card-header title="Usuários" action="novo"></x-card-header>

        <x-table>
            <x-slot name="thead">
                <th scope="col">Usuário</th>
                <th scope="col">E-mail</th>
                <th class="text-center" scope="col">Status</th>
                <th class="text-end" scope="col"></th>
            </x-slot>
            <x-slot name="tbody">
                @foreach($users as $user)
                    <tr>
                        <td class="text-nowrap">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-xl">
                                    <img class="rounded-circle" src="../../assets/img/team/4.jpg" alt=""/>
                                </div>
                                <div class="ms-2">{{$user->name}}</div>
                            </div>
                        </td>
                        <td class="text-nowrap">{{$user->email}}</td>
                        <td>
                            <span class="badge badge rounded-pill d-block p-2 badge-subtle-{{$user->status ? 'success' : 'danger'}}">
                                {{$user->status ? 'Ativo' : 'Inativo'}}
                                <i class="fa-solid fa-xmark"></i>
                            </span>
                        </td>
                        <td class="text-end">
                            <x-table-button :id="$user->id"></x-table-button>
                        </td>
                    </tr>
                @endforeach
            </x-slot>
        </x-table>
    @endsection
</x-app-layout>
