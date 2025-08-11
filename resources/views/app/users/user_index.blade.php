<x-app-layout>
    @push('styles')
        <style>
            .profile {
                display: flex;
                justify-content: center;
                align-items: center;
                flex-direction: column;
            }

            .user-profile-image {
                position: relative;
                width: auto;
                padding: 45px 20px 0 20px;
            }

            .user-profile-image img {
                width: 125px;
                height: 125px;
            }

            .user-profile-image a {
                position: absolute;
                display: flex;
                justify-content: center;
                align-items: center;
                text-decoration: none;
                background: #fff;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                color: black;
                bottom: 0;
                right: 5%;
                box-shadow: 1px 1px 10px rgba(0, 0, 0, 0.25);
            }

            .user-profile-image a i {
                color: #cd7e03;
            }

            .profile .user-profile-info {
                margin-top: 25px;
            }

            .profile .user-profile-info h2 {
                font-size: 16px;
                font-weight: 600;
                letter-spacing: .35px;
            }

            .profile .user-profile-info p {
                font-size: 14px;
                color: #888;
                letter-spacing: .25px;
            }

            .other-infos, .additional-users .nav-tab-info {
                border-radius: 12.5px;
                margin: auto 5px;
                box-shadow: 1px 1px 2.5px rgba(0, 0, 0, 0.2);
            }

            .other-infos {
                margin-top: 15px;
                padding: 15px 20px;
                background: #cd7e03;
            }

            .other-infos .row {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .other-infos .row .col-6 {
                text-align: center;
            }

            .other-infos i {
                padding: 10px;
                font-size: 18px;
                color: #fff;
                background: rgba(255, 255, 255, 0.4);
                box-shadow: 1px 1px 10px rgba(0, 0, 0, 0.25);
                border-radius: 50%;
            }

            .other-infos span {
                font-size: 14px;
                font-weight: 300;
                color: #fff;
                letter-spacing: .25px;
                margin-left: 5px;
            }

            .additional-users .nav-tab-info {
                margin-top: 15px;
                padding: 15px 12.5px;
                background: #fff;
            }

            .additional-users .nav {
                border: none;
            }

            .additional-users .nav .nav-link {
                margin-top: 35px;
                padding: 7.5px 15px;
                border: none;
                font-size: 14.5px;
                border-radius: 7.5px;
                letter-spacing: .6px;
            }

            .additional-users .nav .nav-link.active {
                background: none;
                font-weight: 600;
                color: #222;
            }

            .additional-profile-detail {
                display: flex;
                align-items: flex-start;
            }

            .additional-profile-image img {
                width: 40px;
                height: 40px;
            }

            .addition-profile-info {
                margin: 0;
                padding: 0;
                margin-left: 15px;
                letter-spacing: .25px;
            }

            .addition-profile-info h2 {
                font-size: 14.5px;
                font-weight: 600;
                margin: 0;
            }

            .addition-profile-info .profile-email {
                font-size: 13.5px;
                color: rgba(174, 174, 174, 0.65);
            }

            .addition-profile-info .profile-status-active {
                background: rgba(0, 191, 166, 0.12);
                color: #00bfa6;
                border-radius: 2.5px;
            }

            .addition-profile-info .profile-status-inactive {
                background: rgba(191, 0, 0, 0.12);
                color: #bf0000;
                border-radius: 2.5px;
            }

            .additional-profile-image img, .user-profile-image img {
                border-radius: 100%;
            }
        </style>
    @endpush

    @section('content')
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{route('dashboard')}}"><i class="fas fa-chevron-left text-dark" style="font-size: 16px;"></i></a>
            <h2 class="m-0 mx-3 fw-semi-bold" style="font-size: 14px; letter-spacing: .75px;">Meu perfil</h2>
            <a href="{{route('logout')}}">
                <i class="fa-solid fa-right-from-bracket text-danger"></i>
            </a>
        </div>

        <div class="profile">
            <div class="user-profile-image">
                @if(auth()->user()->image)
                    <img src="data:image/jpeg;base64,{{auth()->user()->image }}" alt="{{auth()->user()->name}}">
                @else
                    <img src="{{ asset('assets/img/user_profile/profile_example.png')}}" alt="{{auth()->user()->name}}">
                @endif
                <a href="#">
                    <i class="fa-solid fa-camera-retro"></i>
                </a>
            </div>
            <div class="user-profile-info text-center">
                <h2>{{auth()->user()->name}}</h2>
                <p>{{auth()->user()->email}}</p>
            </div>
        </div>

        <div class="other-infos">
            <div class="row">
                <div class="col-6">
                    <i class="fa-solid fa-file-waveform"></i>
                    <span>Histórico</span>
                </div>

                <div class="col-6">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Editar</span>
                </div>
            </div>
        </div>

        <div class="additional-users">
            <div class="nav-tab-title">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="home-tab" data-bs-toggle="tab"
                                data-bs-target="#home-tab-pane" type="button" role="tab" aria-controls="home-tab-pane"
                                aria-selected="true">Usuários Adicionais
                        </button>
                    </li>
                </ul>
            </div>

            <div id="userList"></div>
        </div>

        <button id="openModal" class="create-btn"><i class="fa fa-plus text-white"></i></button>

        <x-modal modalId="modalUser" formId="formUser" pathForm="app.users.user_form"></x-modal>

        @push('scripts')
            <script>
                const list = document.getElementById('userList');
                const modal = document.getElementById('modalUser');
                const formEl = document.getElementById('formUser');
                const openBtn = document.getElementById('openModal');

                openBtn.addEventListener('click', () => modal.classList.add('show'));

                const assetUrl = "{{ asset('assets/img/user_profile/profile_example.png') }}";

                formEl.addEventListener('submit', async function (e) {
                    e.preventDefault();
                    const formData = new FormData(formEl);

                    try {
                        const resp = await fetch("{{ route('users.store') }}", {
                            method: "POST",
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: formData
                        });
                        if (!resp.ok) throw new Error('Erro ao salvar registro.');

                        const payload = await resp.json();

                        modal.classList.remove('show');

                        formEl.reset();

                        storeData(payload);
                    } catch (err) {
                        alert(err.message);
                    }
                });

                function storeData(row) {
                    if (!list) return;

                    const imgSrc = row.image
                        ? `data:image/jpeg;base64,${row.image}`
                        : assetUrl;

                    const statusColor = row.is_active ? 'active' : 'inactive';
                    const statusUser = row.is_active ? 'Ativo' : 'Inativo';

                    const html = `
        <div class="nav-tab-info additional-profile-detail">
            <div class="additional-profile-image">
                <img src="${imgSrc}" alt="${row.name}">
            </div>
            <div class="addition-profile-info">
                <h2>${row.name}</h2>
                <span class="profile-email">${row.email}</span><br>
                <span class="badge profile-status-${statusColor}">${statusUser}</span>
            </div>
        </div>`;
                    list.insertAdjacentHTML('beforeend', html);
                }

                async function loadData() {
                    try {
                        const resp = await fetch("{{ route('users.index') }}", {
                            headers: {'Accept': 'application/json'}
                        });
                        if (!resp.ok) throw new Error('Erro ao carregar registros.');
                        const rows = await resp.json();
                        rows.forEach(storeData);
                    } catch (err) {
                        console.error(err);
                    }
                }

                window.addEventListener('DOMContentLoaded', loadData);
            </script>
        @endpush
    @endsection
</x-app-layout>
