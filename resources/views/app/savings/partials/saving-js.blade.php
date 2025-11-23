<script>
    (() => {

        /* ============================================================
         *  SETUP INICIAL
         * ============================================================ */

        const CSRF = '{{ csrf_token() }}';

        const ROUTES = {
            index:  "{{ route('savings.index') }}",
            store:  "{{ route('savings.store') }}",
            show:   "{{ url('/savings') }}/:id",
            update: "{{ url('/savings') }}/:id",
            destroy:"{{ url('/savings') }}/:id",
            deposit:  "{{ url('/savings') }}/:id/deposit",
            withdraw: "{{ url('/savings') }}/:id/withdraw"
        };
        const u = (route, id) => route.replace(':id', id);

        const modal   = document.getElementById('savModal');
        const form    = document.getElementById('savForm');
        const overlay = document.getElementById('savOverlay');
        const btnClose  = document.getElementById('savClose');
        const btnCancel = document.getElementById('savCancel');
        const fab       = document.getElementById('savFab');

        const sheet   = document.getElementById('savSheet');
        const sheetOv = document.getElementById('savSheetOv');

        /* ============================================================
         *  CORES (LOCALSTORAGE)
         * ============================================================ */

        const COLOR_KEY = 'savingColors';
        function loadColorMap() {
            try { return JSON.parse(localStorage.getItem(COLOR_KEY) || '{}'); }
            catch { return {}; }
        }
        let savingColorMap = loadColorMap();

        function getSavingColor(id, fallback = '#00BFA6') {
            return savingColorMap[id] || fallback;
        }
        function setSavingColor(id, color) {
            savingColorMap[id] = color;
            localStorage.setItem(COLOR_KEY, JSON.stringify(savingColorMap));
        }

        // usada para aplicar cor no NOVO cofrinho após salvar
        let pendingColor = null;

        /* ============================================================
         *  UTILITÁRIOS
         * ============================================================ */

        const brl = n => Number(n ?? 0).toLocaleString('pt-BR', {
            style: 'currency', currency: 'BRL'
        });

        function moneyToNumber(v) {
            if (v == null) return 0;
            if (typeof v === 'number') return v;
            let s = String(v)
                .trim()
                .replace(/[^\d,.-]/g,'')
                .replace(/\.(?=\d{3}(?:\D|$))/g,'')
                .replace(',', '.');
            return parseFloat(s) || 0;
        }

        const dateBR = iso => {
            if (!iso) return '—';
            const d = new Date(iso);
            return isNaN(d) ? '—' : d.toLocaleDateString('pt-BR');
        };

        /* ============================================================
         *  TEMPLATE DOS CARDS
         * ============================================================ */

        function nearestAnniversary(lots) {
            if (!Array.isArray(lots) || !lots.length) return null;
            const list = lots
                .map(l => l.next_yield_date)
                .filter(Boolean)
                .sort();
            return list[0] || null;
        }

        function computePrincipal(sv) {
            if (sv.lots?.length) {
                return sv.lots.reduce((sum, lot) =>
                    sum + moneyToNumber(lot.original_amount ?? 0), 0
                );
            }
            return moneyToNumber(sv.current_amount);
        }

        function computeTotal(sv) {
            return moneyToNumber(sv.current_amount);
        }

        function computeYieldValue(sv) {
            const principal = computePrincipal(sv);
            const total = computeTotal(sv);
            const y = total - principal;
            return y > 0 ? y : 0;
        }

        function cardSkeleton() {
            return `
<article class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-5 shadow-soft">
  <div class="flex items-start justify-between gap-3">
    <div class="flex items-center gap-3">
      <span class="size-12 rounded-xl skel"></span>
      <div class="w-40 space-y-2">
        <div class="h-4 skel"></div>
        <div class="h-3 w-24 skel"></div>
      </div>
    </div>
    <div class="h-8 w-24 rounded-lg skel"></div>
  </div>
  <div class="mt-4 space-y-3">
    <div class="h-7 w-36 skel"></div>
    <div class="h-16 rounded-xl skel"></div>
  </div>
</article>`;
        }

        function savingTemplate(sv) {
            const id = sv.id;
            const color = getSavingColor(id, sv.color_card || '#00BFA6');

            const principal  = computePrincipal(sv);
            const total      = computeTotal(sv);
            const rendimento = computeYieldValue(sv);
            const cdiPercent = sv.cdi_percent ?? 1.0;
            const anniv      = nearestAnniversary(sv.lots || []);
            const annivLabel = anniv ? dateBR(anniv) : '—';

            const accName = sv.account?.bank_name
                ? String(sv.account.bank_name).toUpperCase()
                : 'CONTA NÃO DEFINIDA';

            return `
<article data-id="${id}" class="card-floating rounded-xl shadow-soft overflow-hidden border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900">
  <div class="p-4 flex flex-col gap-3" data-bg style="background:${color};color:white;">

    <div class="flex items-start justify-between">
      <div>
        <p class="font-semibold text-lg">${(sv.name ?? 'COFRINHO').toUpperCase()}</p>
        <p class="text-xs opacity-80">${accName}</p>
      </div>
      <button type="button" data-sheet-open class="inline-grid size-8 place-items-center rounded-lg hover:bg-black/20">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="5" cy="12" r="1.5"></circle>
          <circle cx="12" cy="12" r="1.5"></circle>
          <circle cx="19" cy="12" r="1.5"></circle>
        </svg>
      </button>
    </div>

    <div class="text-center">
        <p class="text-2xl font-bold">${brl(total)}</p>
        <p class="text-[11px] opacity-80">Próx. aniversário: ${annivLabel}</p>
    </div>

    <div class="grid grid-cols-2 gap-3 text-[13px]">
      <div class="rounded-xl border border-white/25 p-2">
        <p class="text-[11px] opacity-80">Aplicado</p>
        <p class="font-medium">${brl(principal)}</p>
      </div>
      <div class="rounded-xl border border-white/25 p-2">
        <p class="text-[11px] opacity-80">Rendimento acumulado</p>
        <p class="font-medium">${brl(rendimento)}</p>
      </div>
    </div>

    <div class="flex items-center justify-between text-[11px] opacity-90">
      <span>${(cdiPercent*100).toLocaleString('pt-BR',{maximumFractionDigits:2})}% do CDI</span>
      <span>Criado em: ${dateBR(sv.start_date || sv.created_at)}</span>
    </div>

    ${sv.notes ? `<p class="text-[11px] mt-1 opacity-90">Obs.: ${sv.notes}</p>` : ''}
  </div>
</article>`;
        }

        /* ============================================================
         *  CRUDLITE
         * ============================================================ */

        if (window.__SAVING_CRUD_INIT__) return;
        window.__SAVING_CRUD_INIT__ = true;

        window.crud = CrudLite({
            key: 'savings',
            routes: {
                index:  ROUTES.index,
                store:  ROUTES.store,
                show:   ROUTES.show,
                update: ROUTES.update,
                destroy:ROUTES.destroy
            },
            selectors: {
                grid:    '#savGrid',
                modal:   '#savModal',
                form:    '#savForm',
                title:   '#savModalTitle',
                overlay: '#savOverlay',
                openers: '[data-open-modal="sav"]',
                btnClose:'#savClose',
                btnCancel:'#savCancel'
            },
            template: savingTemplate,
            skeleton: cardSkeleton,
            skeletonCount: 6,

            onBeforeSubmit(fd){
                // normaliza data
                const sd = fd.get('start_date');
                if (sd) fd.set('start_date', String(sd).slice(0,10));

                // normaliza CDI
                const cp = fd.get('cdi_percent');
                if (cp) {
                    const cleaned = String(cp)
                        .replace(/[^\d,.,-]/g,'')
                        .replace(/\.(?=\d{3}(?:\D|$))/g,'')
                        .replace(',', '.');
                    fd.set('cdi_percent', cleaned);
                }

                // NÃO mandar cor nem aporte inicial para o backend
                fd.delete('color_card');
                fd.delete('current_amount');

                // guarda a cor que o usuário escolheu para usarmos depois
                const chosenColor = form.querySelector('#color_card')?.value || '#00BFA6';
                pendingColor = chosenColor;

                return fd;
            },

            fillForm(formEl, sv){
                const id = sv.id ?? sv.uuid ?? '';

                formEl.querySelector('#sav_id').value = id;
                formEl.name.value        = sv.name ?? '';
                formEl.account_id.value  = sv.account_id ?? sv.account?.id ?? '';
                formEl.cdi_percent.value = sv.cdi_percent ?? 1.00;
                formEl.start_date.value  = (sv.start_date ?? '').slice(0,10);
                formEl.notes.value       = sv.notes ?? '';

                const colorInput = formEl.querySelector('#color_card');
                if (colorInput) {
                    colorInput.value = getSavingColor(id, sv.color_card || '#00BFA6');
                }

                // aporte inicial só faz sentido na criação
                const initialField = formEl.querySelector('#current_amount');
                if (initialField) {
                    initialField.value = '';
                }
            },

            onModeChange(mode, formEl, titleEl){
                const isShow = (mode === 'show');

                if (titleEl) {
                    if (mode === 'edit') titleEl.textContent = 'Editar cofrinho';
                    else if (mode === 'show') titleEl.textContent = 'Detalhes do cofrinho';
                    else titleEl.textContent = 'Novo cofrinho';
                }

                if (!formEl) return;

                formEl.querySelectorAll('input,select,textarea').forEach(el => {
                    el.disabled = isShow;
                });

                const btn = formEl.querySelector('button[type="submit"]');
                if (btn) btn.classList.toggle('hidden', isShow);
            },

            confirmDelete: () => confirm('Excluir este cofrinho?'),

            onAfterRender(list){
                // aplica a cor em todos os cards, e se houver um novo sem cor, usa pendingColor
                const idsSemCor = [];

                list.forEach(sv => {
                    const id = sv.id;
                    if (!savingColorMap[id]) {
                        idsSemCor.push(id);
                    }
                });

                // se houver novos e o usuário escolheu uma cor, aplica a cor nova
                if (idsSemCor.length && pendingColor) {
                    idsSemCor.forEach(id => setSavingColor(id, pendingColor));
                    pendingColor = null;
                }

                // aplica cor na UI
                list.forEach(sv => {
                    const id = sv.id;
                    const color = getSavingColor(id, sv.color_card || '#00BFA6');
                    const cardBg = document.querySelector(`article[data-id="${CSS.escape(id)}"] [data-bg]`);
                    if (cardBg) cardBg.style.background = color;
                });
            }
        });

        /* ============================================================
         *  FECHAR MODAL (visual)
         * ============================================================ */

        function closeModal() {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden','ui-modal-open');
        }
        btnClose?.addEventListener('click', closeModal);
        overlay?.addEventListener('click', closeModal);
        btnCancel?.addEventListener('click', closeModal);

        /* ============================================================
         *  DEPÓSITO / SAQUE
         * ============================================================ */

        async function doDeposit(id) {
            const v = prompt('Valor do depósito (R$):');
            const amount = moneyToNumber(v);
            if (!amount || amount <= 0) return alert('Valor inválido.');

            try {
                const res = await fetch(u(ROUTES.deposit,id),{
                    method:'POST',
                    headers:{
                        'X-CSRF-TOKEN':CSRF,
                        'X-Requested-With':'XMLHttpRequest',
                        'Accept':'application/json',
                        'Content-Type':'application/json'
                    },
                    body:JSON.stringify({amount})
                });
                if (!res.ok) throw new Error('Erro ao depositar.');
                await crud.reload();
            } catch (e) {
                alert(e.message || 'Erro ao depositar');
            }
        }

        async function doWithdraw(id) {
            const v = prompt('Valor do saque (R$):');
            const amount = moneyToNumber(v);
            if (!amount || amount <= 0) return alert('Valor inválido.');

            try {
                const res = await fetch(u(ROUTES.withdraw,id),{
                    method:'POST',
                    headers:{
                        'X-CSRF-TOKEN':CSRF,
                        'X-Requested-With':'XMLHttpRequest',
                        'Accept':'application/json',
                        'Content-Type':'application/json'
                    },
                    body:JSON.stringify({amount})
                });
                if (!res.ok) {
                    let d = null;
                    try { d = await res.json(); } catch {}
                    throw new Error(d?.message || 'Erro ao sacar.');
                }
                await crud.reload();
            } catch (e) {
                alert(e.message || 'Erro ao sacar');
            }
        }

        /* ============================================================
         *  BOTTOM SHEET
         * ============================================================ */

        let sheetId = null;

        function openSheet(id){
            sheetId = id;
            sheet.classList.remove('hidden');
            document.body.classList.add('overflow-hidden','ui-sheet-open');
        }
        function closeSheet(){
            sheet.classList.add('hidden');
            document.body.classList.remove('overflow-hidden','ui-sheet-open');
        }

        sheetOv?.addEventListener('click', closeSheet);

        // abrir sheet
        document.addEventListener('click', e => {
            const btn = e.target.closest('[data-sheet-open]');
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();
            const card = btn.closest('article[data-id]');
            const id   = card?.dataset.id;
            if (id) openSheet(id);
        }, true);

        // ações
        document.getElementById('savSheet')?.addEventListener('click', async e => {
            const b = e.target.closest('[data-sheet-action]');
            if (!b || !sheetId) return;

            const act = b.dataset.sheetAction;
            closeSheet();

            if (act === 'edit') {
                try {
                    const res = await fetch(u(ROUTES.show,sheetId), {
                        headers:{
                            'Accept':'application/json',
                            'X-Requested-With':'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) throw 0;
                    const rec = await res.json();
                    crud.openModal('edit', rec);
                } catch {
                    alert('Erro ao carregar cofrinho');
                }
                return;
            }

            if (act === 'delete') {
                try {
                    await crud.delete(sheetId);   // usa delete do CrudLite (com confirmDelete interno)
                    await crud.reload();
                } catch (e) {
                    alert(e.message || 'Erro ao excluir');
                }
                return;
            }

            if (act === 'deposit') {
                doDeposit(sheetId);
                return;
            }

            if (act === 'withdraw') {
                doWithdraw(sheetId);
                return;
            }
        });

        /* ============================================================
         *  BOOT
         * ============================================================ */

        document.addEventListener('readystatechange', () => {
            if (document.readyState === 'complete') {
                crud.reload();
            }
        });

    })();
</script>
