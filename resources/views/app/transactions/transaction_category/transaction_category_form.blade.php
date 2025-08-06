<div class="row">
    <x-input col="12" set="" type="text" title="Categoria" id="name" name="name"
             value="{{ old('name', $transactionCategory->name ?? '') }}" placeholder="Salário, aluguel .."
             disabled=""></x-input>
</div>

<div class="row">
    <x-input col="6" set="" type="color" title="Cor" id="color" name="color"
             value="{{ old('color', $transactionCategory->color ?? '') }}" disabled=""></x-input>
</div>

<div class="row">
    <label for="type">Tipo</label>
    <x-input-check col="12" set="" id="entrada" value="entrada" name="type" title="Entrada" checked="1" disabled=""></x-input-check>
    <x-input-check col="12" set="" id="despesa" value="despesa" name="type" title="Despesa" checked="" disabled=""></x-input-check>
    <x-input-check col="12" set="" id="investimento" value="investimento" name="type" title="Investimento" checked="" disabled=""></x-input-check>
</div>

<input type="hidden" name="has_limit" id="has_limit" value="0">

<div class="row d-none" id="limitSwitchContainer">
    <label for="limitSwitch">Esta categoria terá um limite?</label>
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="limitSwitch">
    </div>
</div>

<!-- só aparece se switch=on -->
<div class="row d-none" id="rangeContainer">
    <x-input-range col="" set="" rangeInput="monthly_limit" rangeValue="limiteMensal" name="monthly_limit" min="0"
                   max="50000" value="{{ old('monthly_limit', 0) }}" title="Limite de gasto"></x-input-range>
</div>

@push('styles')
    <style>
        .form-switch {
            padding-left: 50px;
            padding-bottom: 15px;
        }

        .form-check.form-switch .form-check-input {
            -webkit-appearance: none !important;
            appearance: none !important;
            width: 44px !important;
            height: 22px !important;
            background-color: #f1f1f1 !important;
            background-image: none !important;
            border-radius: 14px !important;
            position: relative !important;
            cursor: pointer !important;
            transition: background-color .3s !important;
        }

        /* Bolinha */
        .form-check.form-switch .form-check-input::after {
            content: "" !important;
            width: 16px !important;
            height: 16px !important;
            background-color: #fff !important;
            border-radius: 50% !important;
            position: absolute !important;
            left: 2px !important;
            top: 2px !important;
            transition: left .3s !important;
        }

        /* Estado ON */
        .form-check.form-switch .form-check-input:checked {
            background-color: #00c779 !important;     /* fundo ON */
        }

        /* Move bolinha pra direita */
        .form-check.form-switch .form-check-input:checked::after {
            left: calc(100% - 26px) !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const typeRadios = document.querySelectorAll('input[name="type"]');
            const limitSwitchCont = document.getElementById('limitSwitchContainer');
            const limitSwitch = document.getElementById('limitSwitch');
            const hasLimitInput = document.getElementById('has_limit');
            const rangeContainer = document.getElementById('rangeContainer');

            typeRadios.forEach(radio => {
                radio.addEventListener('change', () => {
                    if (radio.id == 'despesa' || radio.id == 'investimento') {
                        limitSwitchCont.classList.remove('d-none');
                    } else {
                        limitSwitchCont.classList.add('d-none');
                        rangeContainer.classList.add('d-none');
                        limitSwitch.checked = false;
                        hasLimitInput.value = '0';
                    }
                });
            });

            limitSwitch.addEventListener('change', () => {
                hasLimitInput.value = limitSwitch.checked ? '1' : '0';
                rangeContainer.classList.toggle('d-none', !limitSwitch.checked);
            });
        });
    </script>
@endpush
