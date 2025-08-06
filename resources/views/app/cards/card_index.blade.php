@extends('layouts.templates.mobile')
@section('content-mobile')

    <div class="d-flex justify-content-between mb-3">
        <a href="{{ route('dashboard') }}"><i class="fas fa-xmark text-dark" style="font-size: 22px;"></i></a>
        <i class="fa-solid fa-credit-card text-color" style="font-size: 22px;"></i>
    </div>

    <div class="header">
        <h1 class="mb-1">Cartões de Crédito</h1>
        <p class="p-0 m-0">Gerencie seus cartões e acompanhe seus limites e faturas.</p>
    </div>

    <button id="openModal" class="create-btn"><i class="fa fa-plus text-white"></i></button>

    <!-- Modal com Form -->
    <div id="modalCartao" class="custom-modal">
        <div class="custom-modal-content">
            <span id="closeModal" class="close-btn">&times;</span>

            <form id="formCartao">
                <div class="balance-box">
                    <span>Adicionar Cartão</span>
                    <div class="row mt-2">
                        <div class="col-12">
                            <input type="text" class="form-control" name="name" placeholder="Nome do cartão" required>
                        </div>
                        <div class="col-12 mt-2">
                            <input type="number" step="0.01" class="form-control" name="credit_limit" placeholder="Limite R$" required>
                        </div>
                        <div class="col-6 mt-2">
                            <input type="number" class="form-control" name="closing_day" placeholder="Fechamento (ex: 15)" required>
                        </div>
                        <div class="col-6 mt-2">
                            <input type="number" class="form-control" name="due_day" placeholder="Vencimento (ex: 25)" required>
                        </div>
                        <div class="col-12 mt-2">
                            <select name="account_id" class="form-select">
                                <option value="">Vincular conta (opcional)</option>
                                @foreach($accounts ?? [] as $account)
                                <option value="{{ $account->id }}">{{ $account->bank_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-success">Salvar</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de cartões -->
    <div id="listaCartoes" class="mt-4"></div>

    <script>
        const modal = document.getElementById('modalCartao');
        const openBtn = document.getElementById('openModal');
        const closeBtn = document.getElementById('closeModal');

        openBtn.addEventListener('click', () => modal.classList.add('show'));
        closeBtn.addEventListener('click', () => modal.classList.remove('show'));

        function brl(valor) {
            return parseFloat(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        document.getElementById('formCartao').addEventListener('submit', async function (e) {
            e.preventDefault();
            const form = e.target;
            const data = new FormData(form);

            try {
                const response = await fetch("{{ route('cards.store') }}", {
                    method: "POST",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',

                    },
                    body: data
                });

                if (!response.ok) throw new Error('Erro ao salvar cartão.');
                const cartao = await response.json();

                modal.classList.remove('show');
                form.reset();

                adicionarCartao(cartao);
            } catch (err) {
                alert(err.message);
            }
        });

        function adicionarCartao(cartao) {
            const container = document.getElementById('listaCartoes');
            if (!container) return;

            const card = `
            <div class="balance-box">
                <span>${cartao.name}</span>
                <strong>${brl(cartao.credit_limit)}</strong>
                <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
                    <small><b class="text-muted">Fechamento:</b> ${cartao.closing_day}</small>
                    <small><b class="text-muted">Vencimento:</b> ${cartao.due_day}</small>
                </div>
                ${cartao.account_name ? `<p class="text-muted mb-0"><small>Conta: ${cartao.account_name}</small></p>` : ''}
            </div>
        `;
            container.insertAdjacentHTML('beforeend', card);
        }

        async function carregarCartoes() {
            try {
                const response = await fetch("{{ route('cards.index') }}", {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Erro ao carregar cartões.');

                const cartoes = await response.json();
                cartoes.forEach(adicionarCartao);
            } catch (err) {
                alert(err.message);
            }
        }

        window.addEventListener('DOMContentLoaded', carregarCartoes);
    </script>

@endsection
