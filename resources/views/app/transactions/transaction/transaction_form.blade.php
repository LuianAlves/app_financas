<!-- Name/Category -->
<div class="row">
    <x-input col="6" type="text" title="Título" id="title" name="title" placeholder="Ex: Pagamento aluguel"/>
    <x-select col="6" name="transaction_category_id" id="transaction_category_id" title="Categoria">
        @foreach($categories as $category)
            <option value="{{ $category->id }}" data-type="{{ $category->type }}">{{ $category->name }}</option>
        @endforeach
    </x-select>
</div>

<!-- Price/Date -->
<div class="row">
    <x-input-price col="6" title="Valor" id="amount" name="amount"/>
    <x-input col="6" type="date" title="Data (início)" id="date" name="date"/>
</div>

<!-- Saving -->
<div class="row mt-2 d-none" id="savingContainer">
    <x-select col="12" name="saving_id" id="saving_id" title="Cofrinho">
        @foreach($savings as $s)
            <option value="{{ $s->id }}">{{ $s->name }}</option>
        @endforeach
    </x-select>
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
                <span>{{$card->account ? $card->account->bank_name : ''}} {{$card->last_four_digits}}</span>
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
            <option value="{{ $card->id }}">{{$card->account ? $card->account->bank_name : ''}} {{$card->last_four_digits}}</option>
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
<!--  Custom (dias) -->
<div class="row mt-2 d-none" id="customRecurrenceContainer">
    <div class="col-6">
        <label class="mb-1 d-block">Intervalo (dias)</label>
        <input
            type="number"
            min="1"
            id="interval_value"
            name="interval_value"
            class="form-control"
            placeholder="Ex: 7"
            list="interval_presets"
            value="7"
        >
        <datalist id="interval_presets">
            <option value="7"></option>
            <option value="15"></option>
            <option value="30"></option>
            <option value="45"></option>
            <option value="90"></option>
            <option value="145"></option>
        </datalist>
    </div>

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

<div class="row mt-2 d-none" id="terminationRow">
    <label class="mb-1">Término</label>
    <x-input-check col="6" id="no_end"  value="no_end"  name="termination" title="Sem término" checked="1"/>
    <x-input-check col="6" id="has_end" value="has_end" name="termination" title="Com término"/>
</div>

<div class="row mt-2 d-none" id="occurrencesContainer">
    <x-input col="12" type="number" min="1" title="Nº de ocorrências" id="custom_occurrences" name="custom_occurrences" placeholder="Ex: 12"/>
</div>

@push('scripts')
    <script src="{{asset('assets/js/common/mask_price_input.js')}}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const $  = s => document.querySelector(s);
            const $$ = s => [...document.querySelectorAll(s)];

            // elementos
            const el = {
                pay: { pix: $('#pix'), card: $('#card'), money: $('#money') },
                rec: { unique: $('#unique'), monthly: $('#monthly'), yearly: $('#yearly'), custom: $('#custom') },
                typeCardCon: $('#typeCardContainer'),
                cardSelCon:  $('#cardSelectContainer'),
                pixCon:      $('#pixAccountContainer'),
                customCon:   $('#customRecurrenceContainer'),
                instCon:     $('#installmentsContainer'),
                altRow:      $('#alternateCardsRow'),
                altSel:      $('#alternateCardsSelect'),
                altChk:      $('#alternate_cards'),
                cardId:      $('#card_id'),
                credit:      $('#credit'),
                debit:       $('#debit'),
                cat:         $('#transaction_category_id'),
                savingCon:   $('#savingContainer'),
                termRow:     $('#terminationRow'),
                occCon:      $('#occurrencesContainer'),
                occInput:    $('#custom_occurrences'),
                intervalSel: $('#interval_value'),
            };

            // exclusividade nos "type" (se estiver usando checkbox)
            ['pix','card','money'].forEach(id => {
                const input = el.pay[id];
                input?.addEventListener('change', e => {
                    if (e.target.checked) {
                        ['pix','card','money'].forEach(o => { if (o !== id) el.pay[o].checked = false; });
                        toggleAll();
                    }
                });
            });

            function disableInside(container, disabled) {
                if (!container) return;
                container.querySelectorAll('input,select,textarea').forEach(i => {
                    i.disabled = disabled;
                    if (disabled && (i.type === 'checkbox' || i.type === 'radio')) i.checked = false;
                    if (disabled && i.tagName === 'SELECT') i.selectedIndex = 0;
                    if (disabled && i.tagName === 'INPUT' && ['text','number','date'].includes(i.type)) i.value = '';
                });
            }

            function payVal() { return el.pay.pix.checked ? 'pix' : (el.pay.card.checked ? 'card' : (el.pay.money.checked ? 'money' : null)); }
            function recVal() { return el.rec.custom.checked ? 'custom' : (el.rec.monthly.checked ? 'monthly' : (el.rec.yearly.checked ? 'yearly' : 'unique')); }
            function catType() { return el.cat?.selectedOptions?.[0]?.dataset?.type || null; }

            function isInvest() { return catType() === 'investimento'; }

            function toggleAll() {
                const pay = payVal();           // pix|card|money
                const rec = recVal();           // unique|monthly|yearly|custom
                const invest = isInvest();

                // Investimento: bloquear crédito
                if (invest && el.credit) {
                    el.credit.disabled = true;
                    // se estava crédito, força débito
                    if (el.credit.checked) { el.credit.checked = false; el.debit.checked = true; }
                } else {
                    if (el.credit) el.credit.disabled = false;
                }

                const isCard = pay === 'card';
                const isCred = isCard && el.credit?.checked;
                const isRec  = rec !== 'unique';

                // PIX account
                const showPixAcc = pay === 'pix';
                el.pixCon.classList.toggle('d-none', !showPixAcc);
                disableInside(el.pixCon, !showPixAcc);

                // Card container
                const showTypeCard = isCard;
                el.typeCardCon.classList.toggle('d-none', !showTypeCard);
                disableInside(el.typeCardCon, !showTypeCard);

                // Parcelas (apenas crédito + única)
                const showInst = isCred && !isRec;
                el.instCon.classList.toggle('d-none', !showInst);
                disableInside(el.instCon, !showInst);

                if (invest && isCard && !el.debit.checked && !el.credit.checked) {
                    el.debit.checked = true;
                }

                // Alternância (crédito + recorrente)
                const showAlt     = isCred && isRec;
                const showAltSel  = showAlt && el.altChk?.checked;

                el.altRow.classList.toggle('d-none', !showAlt);
                el.altSel.classList.toggle('d-none', !showAltSel);
                disableInside(el.altSel, !showAltSel);

                // Recorrência custom (X dias)
                const showCustom = rec === 'custom';
                el.customCon.classList.toggle('d-none', !showCustom);
                disableInside(el.customCon, !showCustom);

                const showCardSelect = isCard && !showAltSel;

                el.cardSelCon.classList.toggle('d-none', !showCardSelect);
                disableInside(el.cardSelCon, !showCardSelect);
                if (!showCardSelect) el.cardId.value = '';

                // Término/ocorrências:
                if (rec === 'monthly' || rec === 'yearly' || rec === 'custom') {
                    el.termRow.classList.remove('d-none');
                    disableInside(el.termRow, false);
                    const hasEnd = $('#has_end')?.checked;
                    el.occCon.classList.toggle('d-none', !hasEnd);
                    disableInside(el.occCon, !hasEnd);
                } else {
                    // unique
                    el.termRow.classList.add('d-none');
                    el.occCon.classList.add('d-none');
                    disableInside(el.termRow, true);
                    disableInside(el.occCon, true);
                }

                // Investimento: mostrar cofrinho
                const showSaving = invest;
                el.savingCon.classList.toggle('d-none', !showSaving);
                disableInside(el.savingCon, !showSaving);
            }

            // Listeners
            [...Object.values(el.rec)].forEach(i => i?.addEventListener('change', toggleAll));
            el.credit?.addEventListener('change', toggleAll);
            el.debit?.addEventListener('change', toggleAll);
            el.altChk?.addEventListener('change', toggleAll);
            el.cat?.addEventListener('change', toggleAll);
            $('#has_end')?.addEventListener('change', toggleAll);
            $('#no_end')?.addEventListener('change', toggleAll);

            toggleAll();
        });
    </script>
@endpush

