@extends('layouts.templates.app')
@section('content')
    <x-card-header
        prevRoute="{{ route('dashboard') }}"
        iconRight="fa-solid fa-landmark"
        title="Contas Bancárias"
        description="Gerencie suas contas e saldos."
    ></x-card-header>

    <button id="openAccountModal" class="create-btn"><i class="fa fa-plus text-white"></i></button>

    <div id="confirmDeleteAccount" class="x-confirm" hidden>
        <div class="x-sheet" role="dialog" aria-modal="true" aria-labelledby="xConfirmTitleAccount">
            <div class="x-head">
                <h5 id="xConfirmTitleAccount">Remover conta</h5>
                <button type="button" class="x-close" data-action="cancel" aria-label="Fechar">×</button>
            </div>
            <div class="x-body">Deseja remover esta conta?</div>
            <div class="x-actions">
                <button type="button" class="btn btn-light" data-action="cancel">Cancelar</button>
                <button type="button" class="btn btn-danger" data-action="confirm">Excluir</button>
            </div>
        </div>
    </div>

    <!-- Lista -->
    <ul id="accountList" class="swipe-list mt-4"></ul>

    <x-modal
        modalId="modalAccount"
        formId="formAccount"
        pathForm="app.accounts.account_form"
        :data="[]"
    ></x-modal>

    @push('styles')
        <style>
            /* (opcional) estilinho do confirm custom */
            .x-confirm{position:fixed;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;z-index:1050}
            .x-confirm.show{display:flex}
            .x-sheet{background:#fff;border-radius:10px;min-width:320px;max-width:90vw;box-shadow:0 10px 30px rgba(0,0,0,.2)}
            .x-head{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border-bottom:1px solid #eee}
            .x-body{padding:14px}
            .x-actions{display:flex;gap:8px;justify-content:end;padding:10px 14px;border-top:1px solid #eee}
            .x-close{background:transparent;border:0;font-size:22px;line-height:1}
        </style>
    @endpush

    <script>
        window.ACCOUNTS_CFG = {
            csrf: "{{ csrf_token() }}",
            selectors: {
                list:  "#accountList",
                form:  "#formAccount",
                modal: "#modalAccount",
                plus:  "#openAccountModal",
                confirmId: "confirmDeleteAccount"
            },
            routes: {
                index:   "{{ route('accounts.index') }}",
                show:    "{{ url('/accounts') }}/:id",
                store:   "{{ route('accounts.store') }}",
                update:  "{{ url('/accounts') }}/:id",
                destroy: "{{ url('/accounts') }}/:id"
            }
        };
    </script>

    <script type="module" src="{{ asset('assets/js/views/accounts.js') }}"></script>
@endsection







{{--@extends('layouts.templates.app')--}}
{{--@section('content')--}}
{{--    <x-card-header--}}
{{--        prevRoute="{{route('dashboard')}}"--}}
{{--        iconRight="fa-solid fa-circle-question"--}}
{{--        title="Contas Bancárias"--}}
{{--        description="Para uma melhor projeção, cadastre todas as suas contas bancárias atuais.">--}}
{{--    </x-card-header>--}}

{{--    <div id="accountList" class="mt-4"></div>--}}

{{--    <button id="openModal" class="create-btn"><i class="fa fa-plus text-white"></i></button>--}}

{{--    <a href="{{route('transaction-view.index')}}" class="create-btn create-other" title="Transações">--}}
{{--        <i class="fas fa-retweet text-white"></i>--}}
{{--    </a>--}}

{{--    <x-modal modalId="modalAccount" formId="formAccount" pathForm="app.accounts.account_form"></x-modal>--}}

{{--    <script>--}}
{{--        const modal = document.getElementById('modalAccount');--}}
{{--        const openBtn = document.getElementById('openModal');--}}
{{--        const closeBtn = document.getElementById('closeModal');--}}

{{--        openBtn.addEventListener('click', () => {--}}
{{--            modal.classList.add('show');--}}
{{--        });--}}

{{--        closeBtn.addEventListener('click', () => {--}}
{{--            modal.classList.remove('show');--}}
{{--        });--}}

{{--        function brlPrice(valor) {--}}
{{--            return valor.toLocaleString('pt-BR', {--}}
{{--                style: 'currency',--}}
{{--                currency: 'BRL'--}}
{{--            });--}}
{{--        }--}}

{{--        document.getElementById('formAccount').addEventListener('submit', async function (e) {--}}
{{--            e.preventDefault();--}}
{{--            const form = e.target;--}}
{{--            const data = new FormData(form);--}}

{{--            try {--}}
{{--                const response = await fetch("{{ route('accounts.store') }}", {--}}
{{--                    method: "POST",--}}
{{--                    headers: {--}}
{{--                        'X-CSRF-TOKEN': '{{ csrf_token() }}'--}}
{{--                    },--}}
{{--                    body: data--}}
{{--                });--}}

{{--                if (!response.ok) throw new Error('Erro ao salvar conta.');--}}
{{--                const novaConta = await response.json();--}}

{{--                // Fecha o modal e limpa--}}
{{--                modal.classList.remove('show');--}}
{{--                form.reset();--}}

{{--                // Adiciona novo card--}}
{{--                const container = document.getElementById('accountList');--}}

{{--                const saldoConta = parseFloat(novaConta.current_balance) || 0;--}}
{{--                const saldoCofrinho = (novaConta.savings && novaConta.savings.length > 0)--}}
{{--                    ? parseFloat(novaConta.savings[0].current_amount) || 0--}}
{{--                    : 0;--}}

{{--                const total = saldoConta + saldoCofrinho;--}}

{{--                const card = `--}}
{{--                            <div class="balance-box">--}}
{{--                                <span>${novaConta.bank_name}</span>--}}
{{--                                <strong>${brlPrice(total)}</strong>--}}
{{--                                <div class="d-flex justify-content-between align-items-center mt-2 mb-3">--}}
{{--                                    <small>--}}
{{--                                        <b class="text-muted">Na conta </b>--}}
{{--                                        <div class="d-flex align-items-center">--}}
{{--                                            <span>${brlPrice(saldoConta)}</span>--}}
{{--                                            <small class="text-success mx-2"> +17%</small>--}}
{{--                                        </div>--}}
{{--                                    </small>--}}
{{--                                    <small>--}}
{{--                                        <b class="text-muted">Cofrinhos</b>--}}
{{--                                        <div class="d-flex align-items-center">--}}
{{--                                            <span>${brlPrice(saldoCofrinho)}</span>--}}
{{--                                            <small class="text-danger mx-2"> +3%</small>--}}
{{--                                        </div>--}}
{{--                                    </small>--}}
{{--                                </div>--}}
{{--                                <a href="#" class="text-color fw-bold" style="text-decoration: none; font-size: 13px;">Ver Extrato</a>--}}
{{--                            </div>--}}
{{--                            `;--}}
{{--                container.insertAdjacentHTML('beforeend', card);--}}
{{--            } catch (err) {--}}
{{--                alert(err.message);--}}
{{--            }--}}
{{--        });--}}

{{--        async function carregarContas() {--}}
{{--            try {--}}
{{--                const response = await fetch("{{ route('accounts.index') }}");--}}
{{--                if (!response.ok) throw new Error('Erro ao carregar contas.');--}}

{{--                const contas = await response.json();--}}

{{--                const container = document.getElementById('accountList');--}}
{{--                container.innerHTML = ''; // limpa antes--}}

{{--                contas.forEach(novaConta => {--}}
{{--                    const card = `--}}
{{--                                       <div class="balance-box">--}}
{{--                                            <span>${novaConta.bank_name}</span>--}}
{{--                                            <strong>${novaConta.total}</strong>--}}
{{--                                            <div class="d-flex justify-content-between align-items-center mt-2 mb-3">--}}
{{--                                                <small>--}}
{{--                                                    <b class="text-muted">Na conta </b>--}}
{{--                                                    <div class="d-flex align-items-center">--}}
{{--                                                        <span>${novaConta.current_balance}</span>--}}
{{--                                                        <small class="text-success mx-2"> +17%</small>--}}
{{--                                                    </div>--}}
{{--                                                </small>--}}
{{--                                                <small>--}}
{{--                                                    <b class="text-muted">Cofrinhos</b>--}}
{{--                                                    <div class="d-flex align-items-center">--}}
{{--                                                        <span>${novaConta.saving_amount}</span>--}}
{{--                                                        <small class="text-danger mx-2"> +3%</small>--}}
{{--                                                    </div>--}}
{{--                                                </small>--}}
{{--                                            </div>--}}
{{--                                            <a href="#" class="text-color fw-bold" style="text-decoration: none; font-size: 13px;">Ver Extrato</a>--}}
{{--                                        </div>--}}
{{--                                    `;--}}
{{--                    container.insertAdjacentHTML('beforeend', card);--}}
{{--                });--}}
{{--            } catch (err) {--}}
{{--                alert(err.message);--}}
{{--            }--}}
{{--        }--}}

{{--        // Carrega assim que abrir a tela--}}
{{--        window.addEventListener('DOMContentLoaded', carregarContas);--}}
{{--    </script>--}}
{{--@endsection--}}
