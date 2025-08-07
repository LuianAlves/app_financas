<div class="row">
    <x-input col="12" set="" type="text" title="Título" id="title" name="title"
             value="{{ old('title', $transaction->title ?? '') }}" placeholder="Ex: Pagamento aluguel">
    </x-input>
</div>

<div class="row">
    <x-input col="12" set="" type="text" title="Descrição" id="description" name="description"
             value="{{ old('description', $transaction->description ?? '') }}" placeholder="Detalhes (opcional)">
    </x-input>
</div>

<div class="row">
    <x-input col="6" set="" type="number" step="0.01" title="Valor" id="amount" name="amount"
             value="{{ old('amount', $transaction->amount ?? '') }}" placeholder="R$ 0,00">
    </x-input>

    <x-input col="6" set="" type="date" title="Data" id="date" name="date"
             value="{{ old('date', $transaction->date ?? date('Y-m-d')) }}">
    </x-input>
</div>

{{--<div class="row">--}}
{{--    <x-select col="12" set="" name="transaction_category_id" id="transaction_category_id" title="Categoria">--}}
{{--        @foreach($categories as $category)--}}
{{--            <option value="{{ $category->id }}">{{ $category->name }}</option>--}}
{{--        @endforeach--}}
{{--    </x-select>--}}
{{--</div>--}}

<div class="row">
    <label for="type">Forma de pagamento</label>
    <x-input-check col="12" set="" id="pix" value="pix" name="type" title="Pix" checked="1"></x-input-check>
    <x-input-check col="12" set="" id="card" value="card" name="type" title="Cartão"></x-input-check>
    <x-input-check col="12" set="" id="money" value="money" name="type" title="Dinheiro"></x-input-check>
</div>

<div class="row d-none" id="typeCardContainer">
    <label for="type_card">Tipo de cartão</label>
    <x-input-check col="12" set="" id="credit" value="credit" name="type_card" title="Crédito"></x-input-check>
    <x-input-check col="12" set="" id="debit" value="debit" name="type_card" title="Débito"></x-input-check>
</div>

{{--<div class="row d-none" id="cardSelectContainer">--}}
{{--    <x-select col="12" set="" name="card_id" id="card_id" title="Cartão vinculado">--}}
{{--        <option value="">Selecione um cartão</option>--}}
{{--        @foreach($cards as $card)--}}
{{--            <option value="{{ $card->id }}">{{ $card->name }}</option>--}}
{{--        @endforeach--}}
{{--    </x-select>--}}
{{--</div>--}}

<div class="row">
    <x-select col="12" set="" name="recurrence_type" id="recurrence_type" title="Recorrência">
        <option value="unique">Única</option>
        <option value="monthly">Mensal</option>
        <option value="yearly">Anual</option>
        <option value="custom">Personalizada</option>
    </x-select>
</div>

<div class="row d-none" id="customRecurrenceContainer">
    <x-input col="12" set="" type="number" title="Repetições personalizadas" id="recurrence_custom"
             name="recurrence_custom" placeholder="Ex: 6 vezes"></x-input>
</div>

<div class="row d-none" id="installmentsContainer">
    <x-input col="12" set="" type="number" title="Parcelas" id="installments"
             name="installments" placeholder="Ex: 3 parcelas">
    </x-input>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const typeRadios = document.querySelectorAll('input[name="type"]');
            const typeCardContainer = document.getElementById('typeCardContainer');
            const cardSelectContainer = document.getElementById('cardSelectContainer');
            const recurrenceSelect = document.getElementById('recurrence_type');
            const customRecurrenceContainer = document.getElementById('customRecurrenceContainer');
            const installmentsContainer = document.getElementById('installmentsContainer');

            typeRadios.forEach(radio => {
                radio.addEventListener('change', () => {
                    const isCard = radio.value === 'card';
                    typeCardContainer.classList.toggle('d-none', !isCard);
                    cardSelectContainer.classList.toggle('d-none', !isCard);
                });

                // Inicializa com valor atual
                if (radio.checked && radio.value === 'card') {
                    typeCardContainer.classList.remove('d-none');
                    cardSelectContainer.classList.remove('d-none');
                }
            });

            recurrenceSelect.addEventListener('change', () => {
                const value = recurrenceSelect.value;
                customRecurrenceContainer.classList.toggle('d-none', value !== 'custom');
                installmentsContainer.classList.toggle('d-none', value !== 'custom' && value !== 'monthly');
            });

            // Inicializa se já tiver valor
            if (recurrenceSelect.value === 'custom') {
                customRecurrenceContainer.classList.remove('d-none');
                installmentsContainer.classList.remove('d-none');
            } else if (recurrenceSelect.value === 'monthly') {
                installmentsContainer.classList.remove('d-none');
            }
        });
    </script>
@endpush
