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
        closeBtn?.addEventListener('click', () => modal.classList.remove('show'));

        function brl(valor) {
            const numero = Number(valor);
            return isNaN(numero)
                ? 'R$ 0,00'
                : numero.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        function pct(valor) {
            const n = Number(valor);
            return isNaN(n)
                ? '0,00%'
                : n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 4 }) + '%';
        }

        function dateBR(iso) {
            if (!iso) return '—';
            const d = new Date(iso);
            if (isNaN(d)) return '—';
            return d.toLocaleDateString('pt-BR');
        }

        const upper = (s, fallback = '—') =>
            (s ?? '').toString().trim() ? s.toString().toUpperCase() : fallback;

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
                    let message = 'Erro ao salvar cofrinho.';
                    try {
                        const result = await response.json();
                        message = result.message || message;
                    } catch(_) {}
                    throw new Error(message);
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

            const contaNome =
                saving?.account?.alias
                    ? upper(saving.account.alias)
                    : (saving?.account?.bank_name ? upper(saving.account.bank_name) : 'CONTA NÃO DEFINIDA');

            const periodSuffix = saving.rate_period === 'yearly' ? 'a.a.' : (saving.rate_period ? 'a.m.' : '');

            const color = '#00BFA6';

            const cardBox = `
                <div class="saving-box" style="background-color: ${color}">
                    <div class="saving-name"><strong>${upper(saving.name, 'COFRINHO')}</strong></div>
                    <div class="saving-info">Conta debitada: ${contaNome}</div>
                    <div class="saving-info">Valor aplicado: ${brl(saving.current_amount)}</div>
                    <div class="saving-info">Taxa: ${pct(saving.interest_rate)} ${periodSuffix}</div>
                    <div class="saving-info">Início: ${dateBR(saving.start_date)}</div>
                    ${saving.notes ? `<div class="saving-info">Obs.: ${saving.notes}</div>` : ''}
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
