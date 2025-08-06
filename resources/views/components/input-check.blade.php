<div class="col-{{ $col }} offset-{{ $set }}">
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="{{ $name }}" id="{{ $id }}"
            {{ $checked ? 'checked=""' : '' }} {{ $disabled ? 'disabled' : '' }}>
        <label class="form-check-label" for="{{ $id }}">{{ $title }}</label>
    </div>
</div>
