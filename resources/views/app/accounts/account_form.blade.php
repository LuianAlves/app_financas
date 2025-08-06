<div class="row">
    <x-input col="12" set="" type="text" title="Banco" id="bank_name" name="bank_name" value="{{ old('bank_name', $account->bank_name ?? '') }}" placeholder="Bradesco, Nubank .." disabled=""></x-input>
    <x-input col="12" set="" type="number" title="Saldo atual" id="current_balance" name="current_balance" value="{{ old('cpfCnpj', $account->cnpj ?? '') }}" placeholder="R$ 0,00" step="0.01" disabled=""></x-input>
</div>

<div class="row mt-3">
    <x-input-check col="12" set="" id="corrente" name="type" title="Conta corrente" checked="1" disabled=""></x-input-check>
    <x-input-check col="12" set="" id="poupanca" name="type" title="Conta poupança" checked="" disabled=""></x-input-check>
    <x-input-check col="12" set="" id="investimento" name="type" title="Conta de investimento" checked="" disabled=""></x-input-check>
</div>
