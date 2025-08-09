<div class="row">
{{--    <x-select col="6" set="" name="account_id" id="account_id" title="Conta vinculada">--}}
{{--        @foreach($accounts as $account)--}}
{{--            <option value="{{ $account->id }}">{{ strtoupper($account->bank_name) }}</option>--}}
{{--        @endforeach--}}
{{--    </x-select>--}}

    <x-input col="6" set="" type="text" title="Nome do investimento"
             id="investment_name" name="investment_name"
             value="{{ old('investment_name', $investment->investment_name ?? '') }}"
             placeholder="Tesouro Direto, Ações, FII...">
    </x-input>
</div>

<div class="row">
    <x-input col="6" set="" type="number" step="0.01" title="Valor investido (R$)"
             id="amount_invested" name="amount_invested"
             value="{{ old('amount_invested', $investment->amount_invested ?? '') }}"
             placeholder="0,00">
    </x-input>

    <x-input col="6" set="" type="number" step="0.01" title="Patrimônio Líquido (PL)"
             id="pl" name="pl"
             value="{{ old('pl', $investment->pl ?? '') }}"
             placeholder="0,00">
    </x-input>
</div>

<div class="row">
    <x-input col="6" set="" type="number" step="0.01" title="Rentabilidade (%)"
             id="profitability" name="profitability"
             value="{{ old('profitability', $investment->profitability ?? '') }}"
             placeholder="0,00">
    </x-input>

    <x-input col="6" set="" type="date" title="Data da aplicação"
             id="application_date" name="application_date"
             value="{{ old('application_date', isset($investment->application_date) ? $investment->application_date->format('Y-m-d') : '') }}">
    </x-input>
</div>

<div class="row">
    <x-input col="12" set="" type="text" title="Observações"
             id="notes" name="notes"
             value="{{ old('notes', $investment->notes ?? '') }}"
             placeholder="Informações adicionais sobre o investimento">
    </x-input>
</div>
