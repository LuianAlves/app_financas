@extends('layouts.templates.app')
@section('content')
    <x-card-header
        prevRoute="{{route('dashboard')}}"
        iconRight="fa-solid fa-tags"
        title="Categorias de transação"
        description="Cadastre corretamente todas as categoriais para maior controle.">
    </x-card-header>

    <div id="transactionCategoryList" class="mt-4"></div>

    <button id="openModal" class="create-btn"><i class="fa fa-plus text-white"></i></button>

    <x-modal modalId="modalTransactionCategory" formId="formTransactionCategory"
             pathForm="app.transactions.transaction_category.transaction_category_form"></x-modal>

    <script>
        const modal = document.getElementById('modalTransactionCategory');
        const openBtn = document.getElementById('openModal');
        const closeBtn = document.getElementById('closeModal');

        openBtn.addEventListener('click', () => {
            modal.classList.add('show');
        });

        closeBtn.addEventListener('click', () => {
            modal.classList.remove('show');
        });

        function brlPrice(valor) {
            return valor.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
        }

        document.getElementById('formTransactionCategory').addEventListener('submit', async function (e) {
            e.preventDefault();
            const form = e.target;
            const data = new FormData(form);

            try {
                const response = await fetch("{{ route('transaction-categories.store') }}", {
                    method: "POST",
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                    body: data
                });

                if (!response.ok) throw new Error('Erro ao salvar cartão.');

                const transactionCategory = await response.json();

                modal.classList.remove('show');

                form.reset();

                storeTransactionCategory(transactionCategory);
            } catch (err) {
                alert(err.message);
            }
        });

        function storeTransactionCategory(transactionCategory) {
            const container = document.getElementById('transactionCategoryList');

            if (transactionCategory.monthly_limit == 'R$ 0,00') {
                var limit = 0;
            } else {
                var limit = transactionCategory.monthly_limit;
            }

            if (!container) return;

            const transactionCategoryAfterStore = `
                      <div class="balance-box">
                        <div class="d-flex align-items-center justify-content-between">
                          <span class="text-muted fw-bold" style="font-size:16px;">
                            ${transactionCategory.name}
                          </span>
                          <span class="badge" style="color: ${transactionCategory.color}; font-size: 10px; letter-spacing: 0.5px; border: 1px solid ${transactionCategory.color}; background: ${transactionCategory.color}0a">${transactionCategory.type}</span>
                        </div>
                        ${limit != 0
                ? `<div class="d-flex justify-content-between align-items-center mt-3">
                                 <small class="text-muted">Limite por mês</small>
                                 <div class="d-flex align-items-center fw-bold ">
                                   <span>${limit}</span>
                                 </div>
                             </div>`
                : ''}
                      </div>
                    `;
            container.insertAdjacentHTML('beforeend', transactionCategoryAfterStore);
        }

        async function loadTransactionCategories() {
            try {
                const response = await fetch("{{ route('transaction-categories.index') }}", {
                    headers: {'Accept': 'application/json'}
                });
                if (!response.ok) throw new Error('Erro ao carregar cartões.');

                const transactionCategories = await response.json();

                transactionCategories.forEach(storeTransactionCategory);
            } catch (err) {
                alert(err.message);
            }
        }

        window.addEventListener('DOMContentLoaded', loadTransactionCategories);
    </script>
@endsection
