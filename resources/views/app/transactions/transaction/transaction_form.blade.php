<div class="row">
    <x-input col="6" set="" type="text" title="Título" id="title" name="title"
             placeholder="Ex: Pagamento aluguel"></x-input>
    <x-select col="6" set="" name="transaction_category_id" id="transaction_category_id" title="Categoria">
        @foreach($categories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
        @endforeach
    </x-select>
</div>

{{-- Description --}}
<div class="row">
    <x-input col="12" set="" type="text" title="Descrição" id="description" name="description"
             placeholder="Detalhes (opcional)"></x-input>
</div>

{{-- Amount/Date --}}
<div class="row">
    <x-input col="6" set="" type="number" step="0.01" title="Valor" id="amount" name="amount"
             placeholder="R$ 0,00"></x-input>
    <x-input col="6" set="" type="date" title="Data" id="date" name="date"></x-input>
</div>

{{-- Type --}}
<label for="type">Forma de pagamento</label>
<div class="row d-flex justify-content-around">
    <x-input-check col="4" set="" id="pix" value="pix" name="type" title="Pix" checked="1" disabled=""></x-input-check>
    <x-input-check col="4" set="" id="card" value="card" name="type" title="Cartão" disabled=""></x-input-check>
    <x-input-check col="4" set="" id="money" value="money" name="type" title="Dinheiro" disabled=""></x-input-check>
</div>

{{-- Account --}}
<div class="row mt-2 d-none" id="pixAccountContainer">
    <x-select col="6" set="" name="account_id" id="account_id" title="Conta (Pix)">
        @foreach($accounts as $account)
            <option value="{{ $account->id }}">{{ $account->bank_name }}</option>
        @endforeach
    </x-select>
</div>

{{-- Card --}}
<div class="row d-none" id="cardSelectContainer">
    <x-select col="12" set="" name="card_id" id="card_id" title="Cartão vinculado">
        <option value="">Selecione um cartão</option>
        @foreach($cards as $card)
            <option value="{{ $card->id }}">
                {{ "{$card->account->bank_name} {$card->last_four_digits} "}}
            </option>
        @endforeach
    </x-select>
</div>

{{-- CardType --}}
<div class="row d-flex justify-content-around d-none" id="typeCardContainer">
<label for="type_card">Tipo de cartão</label>
    <x-input-check col="6" set="" id="credit" value="credit" name="type_card" title="Crédito"
                   disabled=""></x-input-check>
    <x-input-check col="6" set="" id="debit" value="debit" name="type_card" title="Débito" disabled=""></x-input-check>
</div>

<div class="row mt-2 d-flex justify-content-between">
    <label for="recurrence_type">Recorrência</label>
    <x-input-check col="4" set="" id="unique" value="unique" name="recurrence_type" title="Única" checked="1" disabled=""></x-input-check>
    <x-input-check col="4" set="" id="monthly" value="monthly" name="recurrence_type" title="Mensal" disabled=""></x-input-check>
    <x-input-check col="4" set="" id="yearly" value="yearly" name="recurrence_type" title="Anual" disabled=""></x-input-check>
    <x-input-check col="4" set="" id="custom" value="custom" name="recurrence_type" title="Personalizada" disabled=""></x-input-check>
</div>

<div class="row mt-2 d-none" id="customRecurrenceContainer">
    <x-input col="12" set="" type="number" title="Repetições personalizadas" id="custom_occurrences"
             name="custom_occurrences" placeholder="Ex: 6 vezes" value=""></x-input>
</div>

<div class="row mt-2 d-none" id="installmentsContainer">
    <x-input col="12" set="" type="number" title="Parcelas" id="installments" name="installments" placeholder="Ex: 3 parcelas" value=""></x-input>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // radios de pagamento
            const payIds     = ['pix','card','money'];
            const payRadios  = payIds.map(id => document.getElementById(id));
            const pixCon     = document.getElementById('pixAccountContainer');
            const cardSelCon = document.getElementById('cardSelectContainer');
            const cardType   = document.getElementById('typeCardContainer');
            const creditRad  = document.getElementById('credit');

            // radios de recorrência
            const recIds     = ['unique','monthly','yearly','custom'];
            const recRadios  = recIds.map(id => document.getElementById(id));
            const recRow     = recRadios[0].closest('.row');
            const customCon  = document.getElementById('customRecurrenceContainer');
            const instCon    = document.getElementById('installmentsContainer');

            function togglePayment() {
                const sel = payRadios.find(r => r.checked).value;
                pixCon    .classList.toggle('d-none', sel!=='pix');
                cardSelCon.classList.toggle('d-none', sel!=='card');
                cardType  .classList.toggle('d-none', sel!=='card');
                if (sel==='card') {
                    recRow.classList.add('d-none');
                    customCon.classList.add('d-none');
                    instCon.classList.add('d-none');
                } else {
                    recRow.classList.remove('d-none');
                    toggleRecurrence();
                }
            }

            function toggleRecurrence() {
                const sel = recRadios.find(r => r.checked).value;
                if (payRadios.find(r => r.checked).value==='card') {
                    customCon.classList.add('d-none');
                    instCon  .classList.add('d-none');
                    return;
                }
                customCon.classList.toggle('d-none', sel!=='custom');
                instCon  .classList.add('d-none');
            }

            function toggleInstallments() {
                const pay  = payRadios.find(r => r.checked).value;
                const cred = creditRad.checked;
                if (pay==='card' && cred) instCon.classList.remove('d-none');
                else instCon.classList.add('d-none');
            }

            payRadios.forEach(r => r.addEventListener('change', () => {
                togglePayment();
                toggleInstallments();
            }));
            recRadios.forEach(r => r.addEventListener('change', toggleRecurrence));
            creditRad.addEventListener('change', toggleInstallments);
            document.getElementById('debit').addEventListener('change', toggleInstallments);

            togglePayment();
            toggleRecurrence();
            toggleInstallments();
        });
    </script>
@endpush
