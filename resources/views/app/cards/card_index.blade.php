@extends('layouts.templates.mobile')
@section('content-mobile')
    <x-card-header
        prevRoute="{{ route('dashboard') }}"
        iconRight="fa-solid fa-credit-card"
        title="Cartões de Crédito"
        description="Gerencie seus cartões e acompanhe seus limites e faturas."
    ></x-card-header>

    <button id="openModal" class="create-btn"><i class="fa fa-plus text-white"></i></button>

    <x-modal modalId="modalCard" formId="formCard" pathForm="app.cards.card_form" :data="['accounts' => $accounts]"></x-modal>

    <div id="cardList" class="mt-4"></div>

    <script>
        const modal = document.getElementById('modalCard');
        const openBtn = document.getElementById('openModal');
        const closeBtn = document.getElementById('closeModal');

        openBtn.addEventListener('click', () => modal.classList.add('show'));
        closeBtn.addEventListener('click', () => modal.classList.remove('show'));

        function brl(valor) {
            return parseFloat(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        document.getElementById('formCard').addEventListener('submit', async function (e) {
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
            const container = document.getElementById('cardList');
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
