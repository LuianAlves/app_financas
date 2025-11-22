<script>
    (() => {

        /* ============================================================
         *  PARTE 1 — SETUP INICIAL
         * ============================================================ */

        const CSRF = '{{ csrf_token() }}';

        /* Rotas API */
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

        /* DOM */
        const grid       = document.getElementById('savGrid');
        const modal      = document.getElementById('savModal');
        const form       = document.getElementById('savForm');
        const overlay    = document.getElementById('savOverlay');
        const btnClose   = document.getElementById('savClose');
        const btnCancel  = document.getElementById('savCancel');
        const fab        = document.getElementById('savFab');

        const sheet      = document.getElementById('savSheet');
        const sheetOv    = document.getElementById('savSheetOv');

        /* ============================================================
         *  PARTE 1 — PERSISTÊNCIA DE CORES
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

        /* ============================================================
         *  PARTE 1 — FUNÇÕES UTILITÁRIAS
         * ============================================================ */

        /** BRL format */
        const brl = (n) => Number(n ?? 0).toLocaleString('pt-BR', {
            style: 'currency', currency: 'BRL'
        });

        /** Converte "1.234,56" -> 1234.56 */
        function moneyToNumber(v) {
            if (v == null) return 0;
            if (typeof v === 'number') return v;

            let s = String(v)
                .trim()
                .replace(/[^\d,.-]/g, '')
                .replace(/\.(?=\d{3}(?:\D|$))/g, '')
                .replace(',', '.');

            return parseFloat(s) || 0;
        }

        /** Percentual do CDI → ex: 1.10 = 110% */
        function parsePercent(v) {
            const n = moneyToNumber(v);
            return n || 1;
        }

        /** Calcula diferença de meses respeitando data */
        function monthsBetween(fromISO, to = new Date()) {
            if (!fromISO) return 0;

            const d = new Date(fromISO);
            let m = (to.getFullYear() - d.getFullYear()) * 12 +
                (to.getMonth() - d.getMonth());

            if (to.getDate() < d.getDate()) m -= 1;
            return Math.max(0, m);
        }

        /** Formata data BR */
        const dateBR = (iso) => {
            if (!iso) return '—';
            const d = new Date(iso);
            return isNaN(d) ? '—' : d.toLocaleDateString('pt-BR');
        };

        /** CDI diário fixado para cálculo simplificado (backend usa oficial) */
        const CDI_DIA = 0.000452; // 0,0452% ao dia (exemplo)

        /** cálculo rendimento = valor * (cdi * percentual) * dias */
        function calcRendimento(valor, dias, perc) {
            if (valor <= 0 || dias <= 0 || perc <= 0) return 0;
            const taxa = CDI_DIA * perc;
            return valor * taxa * dias;
        }

        /* ============================================================
         *  PARTE 1 — SKELETON DO GRID
         * ============================================================ */

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
        </article>
        `;
        }

        /* ============================================================
 *  PARTE 2 — TEMPLATE DOS CARDS
 * ============================================================ */

        function nearestAnniversary(lots) {
            if (!Array.isArray(lots) || !lots.length) return null;
            const upcoming = lots
                .map(l => l.next_yield_date)
                .filter(d => !!d)
                .sort();
            return upcoming.length ? upcoming[0] : null;
        }

        function computePrincipal(sv) {
            if (Array.isArray(sv.lots) && sv.lots.length) {
                return sv.lots.reduce((sum, lot) => {
                    return sum + moneyToNumber(lot.original_amount ?? 0);
                }, 0);
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

        function savingTemplate(sv) {
            const id = sv.id;
            const color = getSavingColor(id, sv.color_card || '#00BFA6');
            const accountName = sv?.account?.bank_name
                ? String(sv.account.bank_name).toUpperCase()
                : 'CONTA NÃO DEFINIDA';

            const principal  = computePrincipal(sv);
            const total      = computeTotal(sv);
            const rendimento = computeYieldValue(sv);
            const cdiPercent = sv.cdi_percent ?? 1.0;

            const anniv = nearestAnniversary(sv.lots || []);
            const annivLabel = anniv ? dateBR(anniv) : '—';

            return `
<article data-id="${id}" class="card-floating rounded-xl shadow-soft overflow-hidden border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900">
  <div class="p-4 flex flex-col gap-3" style="background:${color}; color:white;" data-bg>

    <div class="flex items-start justify-between">
      <div>
        <p class="font-semibold text-lg">${(sv.name ?? 'COFRINHO').toUpperCase()}</p>
        <p class="text-xs opacity-80">${accountName}</p>
      </div>
      <button type="button" data-sheet-open class="inline-grid size-8 place-items-center rounded-lg hover:bg-black/20">
        <svg class="size-4 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
      <span>${(cdiPercent*100).toLocaleString('pt-BR', {minimumFractionDigits:0, maximumFractionDigits:2})}% do CDI</span>
      <span>Criado em: ${dateBR(sv.start_date || sv.created_at)}</span>
    </div>

    ${sv.notes ? `<p class="text-[11px] mt-1 opacity-90">Obs.: ${sv.notes}</p>` : ''}
  </div>
</article>
    `;
        }





        /* ============================================================
         *  PARTE 2 — VISIBILIDADE DO FAB / GRID HELPERS
         * ============================================================ */

        function updateFabVisibility() {
            if (!fab) return;
            const isDesktop = window.matchMedia('(min-width:768px)').matches;
            const hasCards = !!document.querySelector('#savGrid article[data-id]');
            if (isDesktop && hasCards) {
                fab.style.display = 'none';
            } else {
                fab.style.display = 'grid';
            }
        }

        function ensureArray(json) {
            if (Array.isArray(json)) return json;
            if (json && Array.isArray(json.data)) return json.data;
            if (json && typeof json === 'object') return Object.values(json);
            return [];
        }

        function parseIndex(json) {
            return ensureArray(json);
        }

        function parseShow(json) {
            if (!json) return json;
            if (typeof json === 'object' && 'data' in json) return json.data;
            return json;
        }

        /* ============================================================
         *  PARTE 2 — MODAL: MODO, PREENCHIMENTO E NORMALIZAÇÃO
         * ============================================================ */

        function onModeChange(mode, formEl, titleEl) {
            const isShow = (mode === 'show');
            if (titleEl) {
                if (mode === 'edit') titleEl.textContent = 'Editar cofrinho';
                else if (mode === 'show') titleEl.textContent = 'Detalhes do cofrinho';
                else titleEl.textContent = 'Novo cofrinho';
            }

            if (!formEl) return;

            const inputs = formEl.querySelectorAll('input,select,textarea');
            inputs.forEach(el => {
                // campos sempre desabilitados em show
                el.disabled = isShow;
            });

            const btn = formEl.querySelector('button[type="submit"]');
            if (btn) btn.classList.toggle('hidden', isShow);
        }

        function fillFormSaving(formEl, sv) {
            if (!formEl || !sv) return;

            const id = sv.id ?? sv.uuid ?? '';
            const colorInput = formEl.querySelector('#color_card');

            (formEl.querySelector('#sav_id') || {}).value = id;
            formEl.name.value        = sv.name ?? '';
            formEl.account_id.value  = sv.account_id ?? sv.account?.id ?? '';
            formEl.cdi_percent.value = sv.cdi_percent ?? 1.00;
            formEl.start_date.value  = (sv.start_date ?? '').slice(0, 10);
            formEl.notes.value       = sv.notes ?? '';

            if (colorInput) {
                colorInput.value = getSavingColor(id, sv.color_card || '#00BFA6');
                colorInput.addEventListener('input', () => {
                    const card = document.querySelector(`article[data-id="${CSS.escape(id)}"] [data-bg]`);
                    if (card) card.style.background = colorInput.value;
                });
            }

            // campo de aporte inicial só faz sentido na criação → limpa em edição
            const initialField = formEl.querySelector('#current_amount');
            if (initialField) {
                initialField.value = '';
            }
        }

        function beforeSubmit(fd) {
            // limpamos o que não queremos enviar diretamente no store/update
            const sd = fd.get('start_date');
            if (sd != null) fd.set('start_date', String(sd).slice(0, 10));

            // converte cdi_percent se vier com vírgula
            const cp = fd.get('cdi_percent');
            if (cp != null) {
                const cleaned = String(cp)
                    .replace(/[^\d,.,-]/g, '')
                    .replace(/\.(?=\d{3}(?:\D|$))/g, '')
                    .replace(',', '.');
                fd.set('cdi_percent', cleaned);
            }

            // não enviar aporte inicial direto para store/update
            fd.delete('current_amount');
            // cor não vai para o backend
            fd.delete('color_card');

            return fd;
        }

        /* ============================================================
 *  PARTE 3 — CRUDLITE (index/show/store/update/destroy)
 * ============================================================ */

        window.crud = CrudLite({
            key: 'savings',
            routes: {
                index: ROUTES.index,
                store: ROUTES.store,
                show: ROUTES.show,
                update: ROUTES.update,
                destroy: ROUTES.destroy
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

            parseIndex,
            parseShow,
            onModeChange,
            fillForm: fillFormSaving,
            onBeforeSubmit: beforeSubmit,

            confirmDelete: () => confirm('Excluir este cofrinho?'),
            onAction: (act, id) => {}
        });

        window.crud.template = savingTemplate;
        window.savingTemplate = savingTemplate;


        /* ============================================================
         *  PARTE 3 — SUBMIT DO FORMULÁRIO
         *  (store + update + depósito inicial)
         * ============================================================ */

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();

            const isEdit = !!form.querySelector('#sav_id').value;
            const idEdit = form.querySelector('#sav_id').value || null;

            const fd = new FormData(form);

            /* normaliza campos */
            beforeSubmit(fd);

            /* rota */
            let url = ROUTES.store;
            let method = 'POST';

            if (isEdit) {
                url = u(ROUTES.update, idEdit);
                fd.append('_method', 'PUT');
            }

            /* salva a cor */
            const chosenColor = form.querySelector('#color_card')?.value || '#00BFA6';

            try {
                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: fd
                });

                if (!res.ok) {
                    let data = null;
                    try { data = await res.json(); } catch {}
                    throw new Error(data?.message || 'Erro ao salvar o cofrinho.');
                }

                const saved = await res.json().catch(() => null);

                const newId = isEdit
                    ? idEdit
                    : (saved?.id ?? saved?.uuid ?? saved?._id ?? saved?.key);

                /* persiste a cor localmente */
                if (newId) {
                    setSavingColor(newId, chosenColor);

                    const card = document.querySelector(
                        `article[data-id="${CSS.escape(newId)}"] [data-bg]`
                    );
                    if (card) card.style.background = chosenColor;
                }

                /* --- DEPÓSITO INICIAL --- */
                const aporte = moneyToNumber(form.querySelector('#current_amount')?.value);
                if (!isEdit && aporte > 0) {
                    await fetch(u(ROUTES.deposit, newId), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ amount: aporte })
                    }).catch(() => {});
                }

                /* fecha modal + recarrega */
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden', 'ui-modal-open');
                await crud.reload();

            } catch (err) {
                const box = document.getElementById('savFormErr');
                if (box) {
                    box.textContent = err.message || 'Erro';
                    box.classList.remove('hidden');
                } else {
                    alert(err.message || 'Erro ao salvar');
                }
            }
        }, { capture: true });

        /* ============================================================
 *  PARTE 4 — DEPÓSITO E SAQUE (MOVIMENTAÇÕES)
 * ============================================================ */

        async function doDeposit(id) {
            const v = prompt('Valor do depósito (R$):');
            const amount = moneyToNumber(v);
            if (!amount || amount <= 0) {
                alert('Valor inválido.');
                return;
            }

            try {
                const res = await fetch(u(ROUTES.deposit, id), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ amount })
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
            if (!amount || amount <= 0) {
                alert('Valor inválido.');
                return;
            }

            try {
                const res = await fetch(u(ROUTES.withdraw, id), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ amount })
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
         *  PARTE 4 — BOTTOM SHEET (EDIT / DELETE / DEPOSIT / WITHDRAW)
         * ============================================================ */

        let sheetId = null;

        function openSheet(id) {
            sheetId = id;
            sheet.classList.remove('hidden');
            document.body.classList.add('overflow-hidden', 'ui-sheet-open');
        }

        function closeSheet() {
            sheet.classList.add('hidden');
            document.body.classList.remove('overflow-hidden', 'ui-sheet-open');
        }

        sheetOv.addEventListener('click', closeSheet);

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && !sheet.classList.contains('hidden')) {
                closeSheet();
            }
        });

        // abre sheet
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-sheet-open]');
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();

            const card = btn.closest('article[data-id]');
            const id = card?.dataset.id;

            if (id) openSheet(id);
        }, true);

        // ações do sheet
        document.getElementById('savSheet').addEventListener('click', async (e) => {
            const b = e.target.closest('[data-sheet-action]');
            if (!b || !sheetId) return;

            const act = b.dataset.sheetAction;
            closeSheet();

            if (act === 'edit') {
                try {
                    const res = await fetch(u(ROUTES.show, sheetId), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
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
                if (!confirm('Excluir este cofrinho?')) return;

                try {
                    const fd = new FormData();
                    fd.append('_method', 'DELETE');

                    const res = await fetch(u(ROUTES.destroy, sheetId), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: fd
                    });

                    if (!res.ok) throw 0;

                    await crud.reload();
                } catch {
                    alert('Erro ao excluir');
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
         *  PARTE 4 — MODAL DE CRIAÇÃO / EDIÇÃO
         * ============================================================ */

        function closeModal() {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden', 'ui-modal-open');
        }

        document.querySelectorAll('[data-open-modal="sav"]')
            .forEach(b => b.addEventListener('click', () => crud.openModal('create')));

        btnClose?.addEventListener('click', closeModal);
        overlay?.addEventListener('click', closeModal);
        btnCancel?.addEventListener('click', closeModal);

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        /* ============================================================
         *  PARTE 4 — INICIALIZAÇÃO FINAL (BOOT)
         * ============================================================ */

        window.addEventListener('DOMContentLoaded', () => {
            crud.reload().then(() => updateFabVisibility());
        });

        window.addEventListener('resize', updateFabVisibility);

    window.savingTemplate = savingTemplate;
    })();

</script>




