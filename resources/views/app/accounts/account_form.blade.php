<div class="mb-3">
    <label class="form-label" for="bank_name">Banco</label>
    <input class="form-control" name="bank_name" id="bank_name" type="text"
           placeholder="Bradesco, nubank .."/>
</div>

<div class="mb-3">
    <label class="form-label" for="current_balance">Saldo atual</label>
    <input class="form-control" name="current_balance" id="current_balance" type="number" step="0.00"
           placeholder="0.00"/>
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" id="corrente" type="radio" name="type" checked="checked"/>
        <label class="form-check-label mb-0" for="corrente">Corrente</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" id="poupanca" type="radio" name="type"/>
        <label class="form-check-label mb-0" for="poupanca">Poupança</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" id="investimento" type="radio" name="type"/>
        <label class="form-check-label mb-0" for="investimento">Investimento</label>
    </div>
</div>
