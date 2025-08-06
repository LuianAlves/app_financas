    <div class="row">
        <x-input col="12" set="" type="text" title="Banco" id="bank_name" name="bank_name" value="{{ old('bank_name', $account->bank_name ?? '') }}" placeholder="Bradesco, Nubank .." disabled=""></x-input>
        <x-input col="12" set="" type="number" title="Saldo atual" id="current_balance" name="current_balance" value="{{ old('cpfCnpj', $account->cnpj ?? '') }}" placeholder="R$ 0,00" step="0.01" disabled=""></x-input>
    </div>

    <div class="col-12 mt-2">
        <div class="form-check">
            <input class="form-check-input" type="radio" name="type" id="corrente">
            <label class="form-check-label" for="corrente">
                Corrente
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="type" id="poupanca" checked>
            <label class="form-check-label" for="poupanca">
                Poupança
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="type" id="investimento" checked>
            <label class="form-check-label" for="investimento">
                Investimento
            </label>
        </div>
    </div>
    <div class="text-end mt-3">
        <button type="submit" class="btn btn-success">Salvar</button>
    </div>
</div>
