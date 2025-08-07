@extends('layouts.templates.app')

@section('content')
    <x-card-header
        prevRoute="{{ route('dashboard') }}"
        iconRight="fa-solid fa-piggy-bank"
        title="Cofrinhos"
        description="Gerencie seus cofrinhos e acompanhe suas reservas."
    ></x-card-header>

    <button id="openModal" class="create-btn"><i class="fa fa-plus text-white"></i></button>

    <x-modal
        modalId="modalSaving"
        formId="formSaving"
        pathForm="app.savings.saving_form"
        :data="['accounts' => $accounts]"
    ></x-modal>

    <div id="savingList" class="mt-4 px-3"></div>

    <script>
        const modal = document.getElementById('modalSaving');
        const openBtn = document.getElementById('openModal');
        const closeBtn = document.getElementById('closeModal');

        openBtn.addEventListener('click', () => modal.classList.add('show'));
        closeBtn.addEventListener('click', () => modal.classList.remove('show'));

        function brl(valor) {
            const numero = Number(valor);
            return isNaN(numero)
                ? 'R$ 0,00'
                : numero.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        document.getElementById('formSaving').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const data = new FormData(form);

            try {
                const response = await fetch("{{ route('savings.store') }}", {
                    method: "POST",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: data
                });

                if (!response.ok) {
                    const result = await response.json();
                    throw new Error(result.message || 'Erro ao salvar cofrinho.');
                }

                const saving = await response.json();

                modal.classList.remove('show');
                form.reset();
                storeSaving(saving);
            } catch (err) {
                alert(err.message);
            }
        });

        function storeSaving(saving) {
            const container = document.getElementById('savingList');
            if (!container) return;

            const color = saving.color_card ?? '#6c757d'; // cinza-padrão se não vier nada

            const cardBox = `
                <div class="saving-box" style="background-color: ${color}">
                    <div class="saving-name"><strong>${saving.name ?? 'Cofrinho'}</strong></div>
                    <div class="saving-info">Banco: ${saving.account?.bank_name ?? 'Conta não definida'}</div>
                    <div class="saving-info">Valor atual: ${brl(saving.current_amount)}</div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', cardBox);
        }

        async function loadSavings() {
            try {
                const response = await fetch("{{ route('savings.index') }}", {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) throw new Error('Erro ao carregar cofrinhos.');

                const savings = await response.json();
                savings.forEach(storeSaving);
            } catch (err) {
                alert(err.message);
            }
        }

        window.addEventListener('DOMContentLoaded', loadSavings);
    </script>

    @push('styles')
        <style>
            #savingList {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .saving-box {
                border-radius: 8px;
                padding: 14px 16px;
                font-family: sans-serif;
                color: #fff;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
                transition: 0.2s ease-in-out;
            }

            .saving-box:hover {
                transform: scale(1.01);
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            }

            .saving-name {
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 6px;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
            }

            .saving-info {
                font-size: 14px;
                text-shadow: 1px 1px 1px rgba(0,0,0,0.2);
            }
        </style>
    @endpush
@endsection
