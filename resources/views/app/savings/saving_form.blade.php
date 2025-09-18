<div class="row">
    <x-select col="12" set="" name="account_id" id="account_id" title="Conta (debitar)">
        @forelse($accounts as $acc)
            <option value="{{ $acc->id }}">
                {{ strtoupper($acc->bank_name) }}
                — Saldo: R$ {{ brlPrice($acc->current_balance) }}
            </option>
        @empty
            <option value="">Nenhuma conta disponível</option>
        @endforelse
    </x-select>
</div>

<div class="row">
    <x-input col="12" set="" type="text" title="Nome do cofrinho" id="name" name="name" placeholder="Viagem" required></x-input>
</div>

<div class="row">
    <x-input-price col="6" title="Valor (R$)" id="current_amount" name="current_amount" value="{{ old('current_amount', $saving->current_amount ?? '') }}" />

    <x-input
        col="3" set="" type="number" step="0.0001" inputmode="decimal"
        title="Taxa (%)" id="interest_rate" name="interest_rate" placeholder="1.10">
    </x-input>

    <x-select col="3" set="" name="rate_period" id="rate_period" title="Período">
        <option value="monthly" selected>Mensal</option>
        <option value="yearly">Anual</option>
    </x-select>
</div>

<label class="block">
    <span class="text-xs text-neutral-500 dark:text-neutral-400">Cor do cartão</span>
    <input id="color_card" name="color_card" type="color"
           class="mt-1 w-28 h-10 rounded-lg border border-neutral-200/70 dark:border-neutral-800/70 cursor-pointer"
           value="#00BFA6" />
</label>

<div class="row">
    <x-input col="6" set="" type="date" title="Início (opcional)" id="start_date" name="start_date"></x-input>
    <x-input col="6" set="" type="text" title="Observações" id="notes" name="notes" placeholder="Opcional"></x-input>
</div>

@push('scripts')
    <script src="{{asset('assets/js/common/mask_price_input.js')}}"></script>
@endpush
