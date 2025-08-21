<!-- Name/Category -->
<div class="row">
    <x-input col="6" type="text" title="Título" id="title" name="title" placeholder="Ex: Pagamento aluguel"/>
    <x-select col="6" name="transaction_category_id" id="transaction_category_id" title="Categoria">
        @foreach($categories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
        @endforeach
    </x-select>
</div>

<!-- Description -->
<div class="row">
    <x-input col="12" type="text" title="Descrição" id="description" name="description"
             placeholder="Detalhes (opcional)"/>
</div>

<!-- Price/Date -->
<div class="row">
    <input type="text" class="moeda-brl" placeholder="R$ 0,00" autocomplete="off" inputmode="numeric" />
    <input type="number" class="moeda-brl-valor" name="current_amount" step="0.01" hidden />



    <x-input col="6" type="number" step="0.01" title="Valor" id="amount" name="amount" placeholder="R$ 0,00"/>
    <x-input col="6" type="date" title="Data (início)" id="date" name="date"/>
</div>

<!-- Payment Type -->
<label class="mt-2">Forma de pagamento</label>
<div class="row d-flex justify-content-around">
    <x-input-check col="4" id="pix" value="pix" name="type" title="Pix" checked="1"/>
    <x-input-check col="4" id="card" value="card" name="type" title="Cartão"/>
    <x-input-check col="4" id="money" value="money" name="type" title="Dinheiro"/>
</div>

<!-- Card Type -->
<div class="row d-flex justify-content-around d-none" id="typeCardContainer">
    <label class="mb-1">Tipo de cartão</label>
    <x-input-check col="6" id="credit" value="credit" name="type_card" title="Crédito"/>
    <x-input-check col="6" id="debit" value="debit" name="type_card" title="Débito"/>
</div>

<!-- Alternância -->
<div class="row mt-2 d-none" id="alternateCardsRow">
    <label class="mb-1">Alternar entre cartões?</label>
    <div class="col-12 d-flex align-items-center gap-2">
        <input type="checkbox" id="alternate_cards" name="alternate_cards" value="1">
        <span>Sim</span>
    </div>
</div>
<div class="row mt-2 d-none" id="alternateCardsSelect">
    <label class="mb-1">Selecione os cartões para alternar</label>
    @foreach($cards as $card)
        <div class="col-6">
            <label class="d-flex align-items-center gap-2">
                <input type="checkbox" name="alternate_card_ids[]" value="{{ $card->id }}">
                <span>{{ "{$card->account->bank_name} {$card->last_four_digits}" }}</span>
            </label>
        </div>
    @endforeach
</div>

<!-- Select account -->
<div class="row mt-2" id="pixAccountContainer">
    <x-select col="12" name="account_id" id="account_id" title="Conta (Pix/Dinheiro)">
        @foreach($accounts as $account)
            <option value="{{ $account->id }}">{{ $account->bank_name }}</option>
        @endforeach
    </x-select>
</div>

<!-- Select Card -->
<div class="row d-none" id="cardSelectContainer">
    <x-select col="12" name="card_id" id="card_id" title="Cartão vinculado">
        <option value="">Selecione um cartão</option>
        @foreach($cards as $card)
            <option value="{{ $card->id }}">{{ "{$card->account->bank_name} {$card->last_four_digits}" }}</option>
        @endforeach
    </x-select>
</div>

<!-- Recorrência -->
<div class="row mt-2" id="recurrenceRow">
    <label class="mb-1">Recorrência</label>
    <x-input-check col="3" id="unique" value="unique" name="recurrence_type" title="Única" checked="1"/>
    <x-input-check col="3" id="monthly" value="monthly" name="recurrence_type" title="Mensal"/>
    <x-input-check col="3" id="yearly" value="yearly" name="recurrence_type" title="Anual"/>
    <x-input-check col="3" id="custom" value="custom" name="recurrence_type" title="A cada X dias"/>
</div>

<!--  Custom (dias) -->
<div class="row mt-2 d-none" id="customRecurrenceContainer">
    <x-select col="6" name="interval_value" id="interval_value" title="Intervalo (dias)">
        @foreach([7,15,30,45,90] as $d)
            <option value="{{ $d }}">{{ $d }} dias</option>
        @endforeach
    </x-select>
    <div class="col-6">
        <label class="mb-1 d-block">Contar fim de semana?</label>
        <label class="me-3"><input type="checkbox" name="include_sat" id="include_sat" value="1" checked> Sábado</label>
        <label><input type="checkbox" name="include_sun" id="include_sun" value="1" checked> Domingo</label>
    </div>
</div>

<!--  Parcelas (somente crédito + unique) -->
<div class="row mt-2 d-none" id="installmentsContainer">
    <x-input col="12" type="number" min="1" title="Parcelas" id="installments" name="installments"
             placeholder="Ex: 3 parcelas"/>
</div>

@push('scripts')
    <script src="{{asset('assets/js/common/mask_price_input.js')}}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const $ = id => document.getElementById(id);

            const pay = ['pix', 'card', 'money'];
            const rec = ['unique', 'monthly', 'yearly', 'custom'];

            const pixCon = $('pixAccountContainer'),
                cardSelCon = $('cardSelectContainer'),
                typeCardCon = $('typeCardContainer'),
                recRow = $('recurrenceRow'),
                customCon = $('customRecurrenceContainer'),
                instCon = $('installmentsContainer'),
                altRow = $('alternateCardsRow'),
                altSel = $('alternateCardsSelect'),
                altChk = $('alternate_cards'),
                credit = $('credit'), debit = $('debit');

            function current(ids) {
                for (const id of ids) {
                    const el = $(id);
                    if (el?.checked) return el.value;
                }
                return null;
            }

            function toggleAll() {
                const p = current(pay), r = current(rec);
                const isCard = p === 'card';
                const isCred = credit.checked;
                const isRec = r !== 'unique';

                pixCon.classList.toggle('d-none', !(p === 'pix' || p === 'money'));
                cardSelCon.classList.toggle('d-none', !isCard);
                typeCardCon.classList.toggle('d-none', !isCard);

                recRow.classList.remove('d-none');
                customCon.classList.toggle('d-none', r !== 'custom');

                // parcelas: só crédito + unique
                instCon.classList.toggle('d-none', !(isCard && isCred && !isRec));

                // alternância: só crédito + recorrente
                const showAlt = isCard && isCred && isRec;
                altRow.classList.toggle('d-none', !showAlt);
                altSel.classList.toggle('d-none', !(showAlt && altChk.checked));

                // se alterna, esconder select de um cartão fixo
                cardSelCon.classList.toggle('d-none', showAlt && altChk.checked);
            }

            [...pay.map(id => $(id))].forEach(el => el.addEventListener('change', toggleAll));
            [...rec.map(id => $(id))].forEach(el => el.addEventListener('change', toggleAll));
            credit.addEventListener('change', toggleAll);
            debit.addEventListener('change', toggleAll);
            $('alternate_cards').addEventListener('change', toggleAll);

            toggleAll();
        });
    </script>
@endpush
