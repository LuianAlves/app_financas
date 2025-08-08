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

    <a href="{{route('transactionCategory-view.index')}}" class="create-btn create-other">
        <i class="fa-solid fa-tags text-white"></i>
    </a>

    <a href="{{route('account-view.index')}}" class="create-btn create-other-2">
        <i class="fas fa-landmark text-white"></i>
    </a>

    <a href="{{route('card-view.index')}}" class="create-btn create-other-3">
        <i class="fas fa-credit-card text-white"></i>
    </a>

    <x-modal
        modalId="modalTransaction"
        formId="formTransaction"
        pathForm="app.transactions.transaction.transaction_form"
        :data="['cards' => $cards, 'categories' => $categories, 'accounts' => $accounts]"
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
                    <div class="d-flex justify-content-between">
                        <div class="d-flex flex-column justify-content-between">
                            <span class="text-muted fw-bold" style="font-size: 16px;">
                                ${transaction.title ?? 'Sem título'}
                            </span>
                            <small style="letter-spacing: 0.75px; color: #b5b5b5;">Em ${transaction.date}</small>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold ${transaction.typeColor}">
                                ${transaction.amount}
                            </span><br>
                            <span class="badge text-bg-${transaction.typeColor}" style="font-size: 10px;">
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

                console.log(transactions)

                transactions.forEach(storeTransaction);
            } catch (err) {
                alert(err.message);
            }
        }

        window.addEventListener('DOMContentLoaded', loadTransactions);
    </script>
@endsection
