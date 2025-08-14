<div class="row">
    <x-select col="6" set="" name="account_id" id="account_id" title="Banco vinculado">
        @foreach($accounts as $account)
            <option value="{{$account->id}}">{{$account->bank_name}}</option>
        @endforeach
    </x-select>
</div>

<div class="row">
    <x-input col="12" set="" type="color" title="Cor do Cofrinho" id="color_card" name="color_card" value="{{ old('color_card', $saving->color_card ?? '#4CAF50') }}"></x-input>
</div>

<div class="row">
    <x-input col="12" set="" type="text" title="Nome do cofrinho" id="name" name="name" value="{{ old('name', $saving->name ?? '') }}" placeholder="Viagem"></x-input>
</div>

<div class="row">
    <x-input col="12" set="" type="number" step="0.01" min="0" title="Valor" id="current_amount" name="current_amount" value="{{ old('current_amount', $saving->current_amount ?? '') }}" placeholder="0,00"></x-input>
</div>

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(function(){
            $('#account_id').select2({
                width: '100%',
                dropdownParent: $('#modalSaving'),
                minimumResultsForSearch: Infinity
            });
        });
    </script>
@endpush
