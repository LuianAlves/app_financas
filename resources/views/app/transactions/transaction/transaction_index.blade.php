@extends('layouts.templates.app')

@section('content')
    <x-card-header
        prevRoute="{{ route('dashboard') }}"
        iconRight="fa-solid fa-money-bill-transfer"
        title="Transações"
        description="Acompanhe suas transações financeiras organizadas por categoria e tipo.">
    </x-card-header>

    <div id="transactionList" class="mt-4"></div>

    <button id="openModal" class="create-btn">
        <i class="fa fa-plus text-white"></i>
    </button>

    {{-- ✅ Corrigido: enviando as variáveis para o formulário --}}
    <x-modal
        modalId="modalTransaction"
        formId="formTransaction"
        pathForm="app.transactions.transaction.transaction_form"
        :data="['cards' => $cards, 'categories' => $categories, 'transactions' => null]"
         />

    <script>
        const modal = document.getElementById('modalTransaction');
        const openBtn = document.getElementById('openModal');
        const closeBtn = document.getElementById('closeModal');

        openBtn.addEventListener('click', () => modal.classList.add('show'));
        closeBtn.addEventListener('click', () => modal.classList.remove('show'));

        function brlPrice(valor) {
            return parseFloat(valor).toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
        }

        document.getElementById('formTransaction').addEventListener('submit', async function (e) {
            e.preventDefault();
            const form = e.target;
            const data = new FormData(form);

            try {
                const response = await fetch("{{ route('transactions.store') }}", {
                    method: "POST",
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                    body: data
                });

                if (!response.ok) throw new Error('Erro ao salvar transação.');

                const transaction = await response.json();

                modal.classList.remove('show');
                form.reset();
                storeTransaction(transaction);
            } catch (err) {
                alert(err.message);
            }
        });

        function storeTransaction(transaction) {
            const container = document.getElementById('transactionList');
            if (!container) return;

            const typeColor = {
                pix: '#2ecc71',
                card: '#3498db',
                money: '#f39c12'
            };

            const typeLabel = {
                pix: 'Pix',
                card: 'Cartão',
                money: 'Dinheiro'
            };

            const transactionBox = `
                <div class="balance-box">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex flex-column">
                            <span class="text-muted fw-bold" style="font-size: 16px;">
                                ${transaction.title ?? 'Sem título'}
                            </span>
                            <small class="text-muted">${new Date(transaction.date).toLocaleDateString('pt-BR')}</small>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold ${transaction.category_type === 'expense' ? 'text-danger' : 'text-success'}">
                                ${brlPrice(transaction.amount)}
                            </span><br>
                            <span class="badge" style="color: ${typeColor[transaction.type]}; font-size: 10px; border: 1px solid ${typeColor[transaction.type]}; background: ${typeColor[transaction.type]}0a">
                                ${typeLabel[transaction.type]}
                            </span>
                        </div>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('afterbegin', transactionBox);
        }

        async function loadTransactions() {
            try {
                const response = await fetch("{{ route('transactions.index') }}", {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) throw new Error('Erro ao carregar transações.');

                const transactions = await response.json();
                transactions.forEach(storeTransaction);
            } catch (err) {
                alert(err.message);
            }
        }

        window.addEventListener('DOMContentLoaded', loadTransactions);
    </script>
@endsection
