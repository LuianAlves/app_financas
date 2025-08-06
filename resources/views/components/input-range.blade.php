<div class="col-{{$col}} offset-{{$set}}">
    <label for="{{$rangeInput}}" class="form-label text-muted" style="font-size: 13.5px; font-weight: bold; letter-spacing: 0.75px;">
        {{$title}}
    </label>
    <output class="text-muted fw-bold" for="{{$rangeInput}}" id="{{$rangeValue}}" aria-hidden="true"></output>
    <input
        type="range"
        name="{{$name}}"
        class="form-range"
        min="{{$min}}"
        max="{{$max}}"
        value="{{$value}}"
        step="0.01"
        id="{{$rangeInput}}"
    >
</div>

<script>
    (function(){
        const rangeInput = document.getElementById('{{$rangeInput}}');
        const rangeOutput = document.getElementById('{{$rangeValue}}');
        const brl = new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });

        rangeOutput.textContent = brl.format(parseFloat(rangeInput.value));

        rangeInput.addEventListener('input', () => {
            rangeOutput.textContent = brl.format(parseFloat(rangeInput.value));
        });
    })();
</script>
