<!-- Name/Category -->
<div class="row">
    <x-input col="6" type="text" title="Título" id="title" name="title" placeholder="Ex: Pagamento aluguel"/>
    <x-select col="6" name="transaction_category_id" id="transaction_category_id" title="Categoria">
        @foreach($categories as $category)
            <option value="{{ $category->id }}" data-type="{{ $category->type }}">{{ $category->name }}</option>
        @endforeach
    </x-select>
</div>

teste

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

<!-- Parcelar valor? (PIX ou crédito + única) -->
<div class="row mt-2 d-none" id="canInstallRow">
    <label class="mb-1">Parcelar valor?</label>
    <x-input-check col="6" id="can_install_no"  value="0" name="can_install" title="Não" checked="1"/>
    <x-input-check col="6" id="can_install_yes" value="1" name="can_install" title="Sim"/>
</div>

<!--  Parcelas (somente crédito + unique) -->
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
        console.log('teste2')
        document.addEventListener('DOMContentLoaded', () => {
            const $  = s => document.querySelector(s);
            const $$ = s => [...document.querySelectorAll(s)];
        console.log('teste3')

            // elementos
            const el = {
                // forma de pagamento
                pay: {
                    pix:   document.querySelector('input[name="type"][value="pix"]'),
                    card:  document.querySelector('input[name="type"][value="card"]'),
                    money: document.querySelector('input[name="type"][value="money"]'),
                },

                // recorrência
                rec: {
                    unique:  document.querySelector('input[name="recurrence_type"][value="unique"]'),
                    monthly: document.querySelector('input[name="recurrence_type"][value="monthly"]'),
                    yearly:  document.querySelector('input[name="recurrence_type"][value="yearly"]'),
                    custom:  document.querySelector('input[name="recurrence_type"][value="custom"]'),
                },

                // tipo de cartão
                credit: document.querySelector('input[name="type_card"][value="credit"]'),
                debit:  document.querySelector('input[name="type_card"][value="debit"]'),

                typeCardCon: $('#typeCardContainer'),
                cardSelCon:  $('#cardSelectContainer'),
                pixCon:      $('#pixAccountContainer'),
                customCon:   $('#customRecurrenceContainer'),
                instCon:     $('#installmentsContainer'),

                // "pode parcelar?"
                canInstallRow: $('#canInstallRow'),
                canInstallYes: document.querySelector('input[name="can_install"][value="1"]'),
                canInstallNo:  document.querySelector('input[name="can_install"][value="0"]'),

                altRow:      $('#alternateCardsRow'),
                altSel:      $('#alternateCardsSelect'),
                altChk:      $('#alternate_cards'),
                cardId:      $('#card_id'),
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

            function payVal() {
                const checked = document.querySelector('input[name="type"]:checked');
                return checked ? checked.value : null;   // pix | card | money
            }

            function recVal() {
                const checked = document.querySelector('input[name="recurrence_type"]:checked');
                return checked ? checked.value : 'unique'; // unique | monthly | yearly | custom
            }

            function cardTypeVal() {
                const checked = document.querySelector('input[name="type_card"]:checked');
                return checked ? checked.value : null;   // credit | debit | null
            }

            function catType() {
                return el.cat?.selectedOptions?.[0]?.dataset?.type || null;
            }

            function isInvest() { return catType() === 'investimento'; }

            function toggleAll() {
                const pay     = payVal();
                const rec     = recVal();
                const cardTyp = cardTypeVal();
                const invest  = isInvest();

                const isPix  = pay === 'pix';
                const isCard = pay === 'card';
                const isCred = isCard && cardTyp === 'credit';
                const isRec  = rec !== 'unique';

                // Investimento: bloquear crédito
                if (invest && el.credit) {
                    el.credit.disabled = true;
                    if (el.credit.checked) { el.credit.checked = false; el.debit.checked = true; }
                } else {
                    if (el.credit) el.credit.disabled = false;
                }

                // Conta (Pix)
                const showPixAcc = isPix;
                if (el.pixCon) {
                    if (showPixAcc) {
                        el.pixCon.classList.remove('d-none');
                        el.pixCon.style.display = '';
                    } else {
                        el.pixCon.classList.add('d-none');
                        el.pixCon.style.display = 'none';
                    }
                    disableInside(el.pixCon, !showPixAcc);
                }

                // Tipo de cartão
                const showTypeCard = isCard;
                if (el.typeCardCon) {
                    if (showTypeCard) {
                        el.typeCardCon.classList.remove('d-none');
                        el.typeCardCon.style.display = '';
                    } else {
                        el.typeCardCon.classList.add('d-none');
                        el.typeCardCon.style.display = 'none';
                    }
                    disableInside(el.typeCardCon, !showTypeCard);
                }

                // ===== "Parcelar valor?" (PIX ou qualquer cartão) – só em ÚNICA =====
                const showCanInstall = (isPix || isCard) && !isRec;

                if (el.canInstallRow) {
                    if (showCanInstall) {
                        el.canInstallRow.classList.remove('d-none');
                        el.canInstallRow.style.display = '';
                    } else {
                        el.canInstallRow.classList.add('d-none');
                        el.canInstallRow.style.display = 'none';
                    }

                    disableInside(el.canInstallRow, !showCanInstall);

                    if (!showCanInstall) {
                        if (el.canInstallYes) el.canInstallYes.checked = false;
                        if (el.canInstallNo)  el.canInstallNo.checked  = true;
                    }
                }

                const canInstall = !!(el.canInstallYes && el.canInstallYes.checked);

                // ===== Campo "Parcelas" =====
                // - ÚNICA
                // - Parcelar = SIM
                // - E (PIX) ou (cartão CRÉDITO)
                const showInst = !isRec && canInstall && (isPix || isCred);

                if (el.instCon) {
                    if (showInst) {
                        el.instCon.classList.remove('d-none');
                        el.instCon.style.display = '';
                    } else {
                        el.instCon.classList.add('d-none');
                        el.instCon.style.display = 'none';
                    }
                    disableInside(el.instCon, !showInst);

                    if (!showInst) {
                        const instInput = document.querySelector('#installments');
                        if (instInput) instInput.value = '';
                    }
                }

                // Se investimento + cartão e nada marcado, força débito
                if (invest && isCard && !el.debit.checked && !el.credit.checked) {
                    el.debit.checked = true;
                }

                // Alternância (crédito + recorrente)
                const showAlt     = isCred && isRec;
                const showAltSel  = showAlt && el.altChk?.checked;

                if (el.altRow) {
                    el.altRow.classList.toggle('d-none', !showAlt);
                    disableInside(el.altRow, !showAlt);
                }
                if (el.altSel) {
                    el.altSel.classList.toggle('d-none', !showAltSel);
                    disableInside(el.altSel, !showAltSel);
                }

                // Recorrência custom (X dias)
                const showCustom = rec === 'custom';
                if (el.customCon) {
                    el.customCon.classList.toggle('d-none', !showCustom);
                    disableInside(el.customCon, !showCustom);
                }

                const showCardSelect = isCard && !showAltSel;
                if (el.cardSelCon) {
                    el.cardSelCon.classList.toggle('d-none', !showCardSelect);
                    disableInside(el.cardSelCon, !showCardSelect);
                    if (!showCardSelect) el.cardId.value = '';
                }

                // Término/ocorrências:
                if (el.termRow && el.occCon) {
                    if (rec === 'monthly' || rec === 'yearly' || rec === 'custom') {
                        el.termRow.classList.remove('d-none');
                        disableInside(el.termRow, false);
                        const hasEnd = $('#has_end')?.checked;
                        el.occCon.classList.toggle('d-none', !hasEnd);
                        disableInside(el.occCon, !hasEnd);
                    } else {
                        el.termRow.classList.add('d-none');
                        el.occCon.classList.add('d-none');
                        disableInside(el.termRow, true);
                        disableInside(el.occCon, true);
                    }
                }

                // Investimento: mostrar cofrinho
                const showSaving = invest;
                if (el.savingCon) {
                    el.savingCon.classList.toggle('d-none', !showSaving);
                    disableInside(el.savingCon, !showSaving);
                }
            }

            // Listeners
            [...Object.values(el.rec)].forEach(i => i?.addEventListener('change', toggleAll));
            el.credit?.addEventListener('change', toggleAll);
            el.debit?.addEventListener('change', toggleAll);
            el.altChk?.addEventListener('change', toggleAll);
            el.cat?.addEventListener('change', toggleAll);
            $('#has_end')?.addEventListener('change', toggleAll);
            $('#no_end')?.addEventListener('change', toggleAll);
            $$('input[name="can_install"]').forEach(i => i.addEventListener('change', toggleAll));

            toggleAll();
        });
    </script>
@endpush

