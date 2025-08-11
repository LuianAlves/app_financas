<div class="row">
    <x-input col="8" set="" type="text" title="Nome" id="name" name="name" value="{{ old('name', $user->name ?? '') }}" placeholder="John Doe" disabled=""></x-input>
    <x-input col="8" set="" type="email" title="E-mail" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" placeholder="john.doe@email.com" disabled=""></x-input>
</div>

<div class="row">
    <x-input col="6" set="" type="password" title="Senha" id="password" name="password" value="" placeholder="********" disabled=""></x-input>
    <x-input col="6" set="" type="password" title="Confirma senha" id="password_confirmation" name="password_confirmation" value="" placeholder="********" disabled=""></x-input>
</div>

<div class="row">
    <div id="imageInputWrap" class="col-6">
        <label class="form-label">Foto</label>
        <input class="form-control" type="file" id="image" name="image" accept="image/*">
    </div>
    <div id="imagePreview" class="col-6" style="display:none"></div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('formUser');
            if (!form) return;

            const fileEl = form.querySelector('#image');
            const wrapEl = form.querySelector('#imageInputWrap');
            const prevEl = form.querySelector('#imagePreview');
            if (!fileEl || !wrapEl || !prevEl) return;

            function showPreview(dataUrl) {
                prevEl.innerHTML = `
      <div class="position-relative" style="max-width:140px">
        <img src="${dataUrl}" alt="preview"
             style="width:140px;height:140px;border-radius:8px;object-fit:cover;">
        <button type="button" id="changeImg"
                class="btn btn-sm btn-light border position-absolute"
                style="right:6px;bottom:6px">Trocar</button>
      </div>`;
                prevEl.style.display = '';
                wrapEl.style.display = 'none';
            }

            function clearPreview() {
                prevEl.innerHTML = '';
                prevEl.style.display = 'none';
                wrapEl.style.display = '';
                fileEl.value = '';
            }

            fileEl.addEventListener('change', () => {
                const f = fileEl.files && fileEl.files[0];
                if (!f) { clearPreview(); return; }
                const r = new FileReader();
                r.onload = () => showPreview(r.result);
                r.readAsDataURL(f);
            });

            prevEl.addEventListener('click', (e) => {
                if (e.target && e.target.id === 'changeImg') {
                    clearPreview();
                    fileEl.click();
                }
            });

            form.addEventListener('reset', () => {
                setTimeout(clearPreview, 0);
            });
        });
    </script>
@endpush
