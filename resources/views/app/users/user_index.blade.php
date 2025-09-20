<x-app-layout>
    @push('styles')
        <style>
            /* Avatar com overlay de câmera */
            .avatar-wrap{position:relative;display:grid;place-items:center;padding-top:10px}
            .avatar{width:120px;height:120px;border-radius:9999px;object-fit:cover;box-shadow:0 4px 16px rgba(0,0,0,.12)}
            .avatar-edit{position:absolute;right:10px;bottom:2px;width:40px;height:40px;border-radius:9999px;display:grid;place-items:center;background:#fff;color:#111;border:1px solid rgba(0,0,0,.08);box-shadow:0 8px 20px rgba(0,0,0,.18)}
            .dark .avatar-edit{background:#0a0a0a;color:#fafafa;border-color:rgba(255,255,255,.12)}
            /* Badges de status */
            .badge-active{background:rgba(0,191,166,.12);color:#00bfa6}
            .badge-inactive{background:rgba(191,0,0,.12);color:#bf0000}
            /* Card do item de usuário adicional */
            .user-row{display:flex;gap:.75rem;align-items:flex-start}
            .user-row img{width:44px;height:44px;border-radius:9999px;object-fit:cover}
        </style>
    @endpush

    @section('new-content')
        <!-- Header padrão -->
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('dashboard') }}" class="inline-grid size-10 place-items-center rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-800" aria-label="Voltar">
                <i class="fas fa-chevron-left text-neutral-700 dark:text-neutral-200"></i>
            </a>
            <h2 class="text-base md:text-lg font-semibold">Meu perfil</h2>
            <a href="{{ route('logout') }}" class="inline-grid size-10 place-items-center rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-red-50 dark:hover:bg-red-900/20" title="Sair" aria-label="Sair">
                <i class="fa-solid fa-right-from-bracket text-red-600"></i>
            </a>
        </div>

        <!-- Card do perfil -->
        <section class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-6 shadow-soft">
            <div class="avatar-wrap">
                @if(auth()->user()->image)
                    <img class="avatar" src="data:image/jpeg;base64,{{ auth()->user()->image }}" alt="{{ auth()->user()->name }}">
                @else
                    <img class="avatar" src="{{ asset('assets/img/user_profile/profile_example.png') }}" alt="{{ auth()->user()->name }}">
                @endif
                <button type="button" class="avatar-edit" title="Trocar foto">
                    <i class="fa-solid fa-camera-retro text-amber-600"></i>
                </button>
            </div>

            <div class="text-center mt-4">
                <h3 class="text-sm font-semibold tracking-wide">{{ auth()->user()->name }}</h3>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ auth()->user()->email }}</p>
            </div>

            <!-- Ações rápidas -->
            <div class="mt-5 grid grid-cols-2 gap-3">
                <button type="button" class="w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 p-3 hover:bg-neutral-50 dark:hover:bg-neutral-800 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-file-waveform text-brand-600"></i>
                    <span class="text-sm font-medium">Histórico</span>
                </button>
                <button id="openModal" type="button" class="w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 p-3 hover:bg-neutral-50 dark:hover:bg-neutral-800 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-user-plus text-brand-600"></i>
                    <span class="text-sm font-medium">Adicionar usuário</span>
                </button>
            </div>
        </section>

        <!-- Lista de usuários adicionais -->
        <section class="mt-4">
            <div class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-semibold">Usuários adicionais</h4>
                </div>
                <div id="userList" class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    <!-- itens renderizados via JS -->
                </div>
            </div>
        </section>

        <!-- FAB (mobile) -->
        <button id="fabUser" type="button" class="md:hidden fixed bottom-20 right-4 z-[80] size-14 rounded-2xl grid place-items-center text-white shadow-lg bg-brand-600 hover:bg-brand-700 active:scale-95 transition" aria-label="Adicionar usuário">
            <i class="fa fa-plus"></i>
        </button>

        <!-- Modal (mantém seu componente) -->
        <x-modal modalId="modalUser" formId="formUser" pathForm="app.users.user_form"></x-modal>

        @push('scripts')
            <script>
                (() => {
                    const list   = document.getElementById('userList');
                    const modal  = document.getElementById('modalUser');
                    const formEl = document.getElementById('formUser');
                    const openBtn= document.getElementById('openModal');
                    const fab    = document.getElementById('fabUser');

                    const assetUrl = "{{ asset('assets/img/user_profile/profile_example.png') }}";

                    const openModal = () => modal.classList.add('show');
                    openBtn?.addEventListener('click', openModal);
                    fab?.addEventListener('click', openModal);

                    formEl.addEventListener('submit', async (e) => {
                        e.preventDefault();
                        const formData = new FormData(formEl);
                        try{
                            const resp = await fetch("{{ route('users.store') }}", {
                                method: "POST",
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: formData
                            });
                            if(!resp.ok) throw new Error('Erro ao salvar registro.');
                            const payload = await resp.json();

                            // fecha e reseta
                            modal.classList.remove('show');
                            formEl.reset();

                            // renderiza
                            storeRow(payload);
                        }catch(err){
                            alert(err.message);
                        }
                    });

                    function storeRow(row){
                        if(!list) return;
                        const imgSrc = row.image ? `data:image/jpeg;base64,${row.image}` : assetUrl;
                        const statusClass = row.is_active ? 'badge-active' : 'badge-inactive';
                        const statusText  = row.is_active ? 'Ativo' : 'Inativo';

                        const html = `
<article class="rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-3">
  <div class="user-row">
    <img src="${imgSrc}" alt="${row.name}">
    <div class="min-w-0">
      <div class="flex items-center gap-2">
        <h5 class="text-sm font-semibold truncate">${row.name}</h5>
        <span class="px-2 py-0.5 text-[11px] rounded ${statusClass}">${statusText}</span>
      </div>
      <div class="text-xs text-neutral-500 dark:text-neutral-400 truncate">${row.email}</div>
    </div>
  </div>
</article>`;
                        list.insertAdjacentHTML('beforeend', html);
                    }

                    async function loadData(){
                        try{
                            const resp = await fetch("{{ route('users.index') }}", { headers:{'Accept':'application/json'} });
                            if(!resp.ok) throw new Error('Erro ao carregar registros.');
                            const rows = await resp.json();
                            list.innerHTML = '';
                            rows.forEach(storeRow);
                        }catch(err){
                            console.error(err);
                        }
                    }

                    window.addEventListener('DOMContentLoaded', loadData);
                })();
            </script>
        @endpush
    @endsection
</x-app-layout>
