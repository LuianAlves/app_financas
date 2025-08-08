@extends('layouts.templates.app')
@section('content')
    <x-card-header
        prevRoute="{{ route('dashboard') }}"
        iconRight="fa-solid fa-credit-card"
        title="Cartões de Crédito"
        description="Gerencie seus cartões e acompanhe seus limites e faturas."
    ></x-card-header>

    <button id="openModal" class="create-btn"><i class="fa fa-plus text-white"></i></button>

    <a href="{{route('transaction-view.index')}}" class="create-btn create-other" title="Transações">
        <i class="fas fa-retweet text-white"></i>
    </a>

    <x-modal
        modalId="modalCard"
        formId="formCard"
        pathForm="app.cards.card_form"
        :data="['accounts' => $accounts]"
    ></x-modal>

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

        const brandMap = {
            1: 'Visa',
            2: 'Mastercard',
            3: 'American Express',
            4: 'Discover',
            5: 'Diners Club',
            6: 'JCB',
            7: 'Elo'
        };

        function formatCardNumber(last4) {
            return '**** **** **** ' + String(last4).padStart(4, '0');
        }

        const assetUrl = "{{ asset('assets/img') }}";

        document.getElementById('formCard').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const data = new FormData(form);

            try {
                const response = await fetch("{{ route('cards.store') }}", {
                    method: "POST",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: data
                });

                if (!response.ok) throw new Error('Erro ao salvar cartão.');
                const card = await response.json();

                modal.classList.remove('show');
                form.reset();
                storeCard(card);
            } catch (err) {
                alert(err.message);
            }
        });

        function storeCard(card) {
            const container = document.getElementById('cardList');
            if (!container) return;

            const brandName = brandMap[card.brand];
            const cardAfterStore = `
                <div class="balance-box" style="background: ${card.color_card}">
                    <img src="${assetUrl}/credit_card/chip_card.png" class="card-chip" alt="Chip" />
                    <img src="${assetUrl}/brands/${brandName}.png" class="card-brand" alt="${brandName}" />
                    <div class="card-number">
                        ${formatCardNumber(card.last_four_digits)}
                    </div>
                    <div class="card-details">
                      <div class="detail-row mb-3">
                        <div class="detail-left">${card.cardholder_name}</div>
                        <div class="detail-right">${card.account.bank_name}</div>
                      </div>
                      <div class="detail-row flex-column" style="font-size: 10px; letter-spacing: 1px;">
                        <div>Fatura: R$ 2.731,00</div>
                        <div>Limite Atual: ${card.credit_limit}</div>
                      </div>
                    </div>
                 </div>
            `;
            container.insertAdjacentHTML('beforeend', cardAfterStore);
        }

        async function loadCards() {
            try {
                const response = await fetch("{{ route('cards.index') }}", {
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Erro ao carregar cartões.');
                const cards = await response.json();
                cards.forEach(storeCard);
            } catch (err) {
                alert(err.message);
            }
        }

        window.addEventListener('DOMContentLoaded', loadCards);
    </script>

    @push('styles')
        <style>
            #savingList {
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .balance-box {
                background-repeat: no-repeat;
                background-size: cover;
                width: 290px;
                height: 175px;
                border-radius: 12px;
                color: #fff;
                padding: 16px;
                position: relative;
                font-family: sans-serif;
                box-shadow: 1px 4px 8px rgba(0,0,0,0.2);
                background-position: center;
                margin-bottom: 16px;
            }
            .balance-box .card-brand {
                position: absolute;
                top: 16px;
                left: 16px;
                width: 35px;
            }
            .balance-box .card-chip {
                position: absolute;
                top: 7.5px;
                right: 16px;
                width: 35px;
            }

            .balance-box .card-details .detail-left, .balance-box .card-details .detail-right , .balance-box .card-number {
                letter-spacing: 2px;
                text-shadow: 1px 1px 1px #000;
            }

            .balance-box .card-number {
                margin-top: 40px;
                font-size: 12.5px;
            }

            .balance-box .card-details {
                position: absolute;
                bottom: 12px;
                left: 16px;
                right: 16px;
                display: flex;
                flex-direction: column;
                gap: 4px;
                font-size: 0.8rem;
                text-shadow: 1px 1px 1px #000;
            }

            .balance-box .card-details .detail-row {
                display: flex;
                justify-content: space-between;
                width: 100%;
            }

            .balance-box .card-details .detail-left {
                text-align: left;
                font-size: 10px;
                letter-spacing: 1px;
            }

            .balance-box .card-details .detail-right {
                text-align: right;
                font-size: 10px;
                letter-spacing: 1px;
            }

        </style>
    @endpush
@endsection
