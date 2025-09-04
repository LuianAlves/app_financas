@extends('layouts.templates.new_layout')

@section('new-content')
    @push('styles')
        <style>
            /* Skeleton blocks */
            .skel {
                position: relative;
                overflow: hidden;
                border-radius: .5rem;
                background: #e5e7eb
            }

            /* neutral-200 */
            .dark .skel {
                background: #262626
            }

            /* neutral-800 */

            /* Shimmer nos skeletons */
            .skel::after {
                content: "";
                position: absolute;
                inset: 0;
                transform: translateX(-100%);
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .55), transparent);
                animation: skel 1.1s infinite
            }

            @keyframes skel {
                100% {
                    transform: translateX(100%)
                }
            }

            /* Overlay shimmer por cima dos cards reais (quando há cache) */
            .grid-loading {
                position: relative
            }

            .grid-loading::after {
                content: "";
                position: absolute;
                inset: 0;
                pointer-events: none;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .5), transparent);
                animation: skel 1.1s infinite;
                opacity: .35
            }

            .dark .grid-loading::after {
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .08), transparent);
                opacity: .6
            }
        </style>
    @endpush

    <section id="contas-page" class="mt-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold">Contas bancárias</h2>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">Acompanhe saldos por banco e acesse o extrato
                    de cada conta.</p>
            </div>
            <div class="hidden md:flex items-center gap-2">
                <button data-open-modal="acc"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white shadow-soft">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Nova conta
                </button>
            </div>
        </div>

        <!-- Lista dinâmica -->
        <div id="accGrid" class="grid grid-cols-1 lg:grid-cols-2 gap-4"></div>

        <!-- FAB (mobile) -->
        <button id="accFab" type="button" data-open-modal="acc"
                class="md:hidden fixed bottom-20 right-4 z-[80] size-14 rounded-2xl grid place-items-center
         text-white shadow-lg bg-brand-600 hover:bg-brand-700 active:scale-95 transition"
                aria-label="Nova conta">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
        </button>

        <!-- Modal -->
        <div id="accModal" class="fixed inset-0 z-[60] hidden" role="dialog" aria-modal="true"
             aria-labelledby="accModalTitle">
            <div id="accOverlay" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
            <div
                class="absolute inset-x-0 bottom-0 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-[560px]">
                <div
                    class="rounded-t-3xl md:rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 shadow-soft dark:shadow-softDark p-4 md:p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 id="accModalTitle" class="text-lg font-semibold">Nova conta bancária</h3>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">Informe os detalhes da conta.</p>
                        </div>
                        <button id="accClose"
                                class="size-10 grid place-items-center rounded-xl hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                aria-label="Fechar">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 6 6 18"/>
                                <path d="M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form id="accForm" class="mt-4 grid gap-3">
                        <input type="hidden" id="acc_id" name="id"/>

                        <label class="block">
                            <span class="text-xs text-neutral-500 dark:text-neutral-400">Nome do banco</span>
                            <input id="bank_name" name="bank_name" type="text" placeholder="Ex: Banco do Norte"
                                   class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"
                                   required/>
                        </label>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <label class="block">
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">Valor em conta (R$)</span>
                                <input id="current_balance" name="current_balance" inputmode="decimal"
                                       placeholder="0,00"
                                       class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"
                                       required/>
                            </label>
                            <label class="block">
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">Tipo de conta</span>
                                <div
                                    class="mt-1 inline-flex w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-neutral-50 dark:bg-neutral-800 p-1">
                                    <input type="radio" name="type" value="1" id="accCorr" class="peer/acc1 hidden"
                                           checked>
                                    <label for="accCorr"
                                           class="flex-1 text-center px-3 py-1.5 rounded-lg bg-white dark:bg-neutral-900 shadow-sm cursor-pointer peer-checked/acc1:font-medium">Corrente</label>
                                    <input type="radio" name="type" value="2" id="accPoup" class="peer/acc2 hidden">
                                    <label for="accPoup"
                                           class="flex-1 text-center px-3 py-1.5 rounded-lg cursor-pointer hover:bg-white/70 dark:hover:bg-neutral-900/70">Poupança</label>
                                    <input type="radio" name="type" value="3" id="accInv" class="peer/acc3 hidden">
                                    <label for="accInv"
                                           class="flex-1 text-center px-3 py-1.5 rounded-lg cursor-pointer hover:bg-white/70 dark:hover:bg-neutral-900/70">Investimento</label>
                                </div>
                            </label>
                        </div>

                        <div class="mt-2 flex items-center justify-end gap-2">
                            <button type="button" id="accCancel"
                                    class="px-3 py-2 rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white shadow-soft">
                                Salvar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Transferência -->
        <div id="trModal" class="fixed inset-0 z-[65] hidden" role="dialog" aria-modal="true" aria-labelledby="trTitle">
            <div id="trOverlay" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
            <div
                class="absolute inset-x-0 bottom-0 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-[520px]">
                <div
                    class="rounded-t-3xl md:rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 shadow-soft dark:shadow-softDark p-4 md:p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 id="trTitle" class="text-lg font-semibold">Transferência entre contas</h3>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">Selecione a conta de destino e o
                                valor.</p>
                        </div>
                        <button id="trClose"
                                class="size-10 grid place-items-center rounded-xl hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                aria-label="Fechar">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 6 6 18"/>
                                <path d="M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form id="trForm" class="mt-4 grid gap-3">
                        <div id="trFormErr" class="hidden mb-2 rounded-lg bg-red-50 text-red-700 text-sm px-3 py-2"></div>

                        <input type="hidden" id="trFrom" name="from_id"/>
                        <label class="block">
                            <span class="text-xs text-neutral-500 dark:text-neutral-400">Conta destino</span>
                            <select id="trTo" name="to_id"
                                    class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"
                                    required></select>
                            <p id="trToErr" class="mt-1 text-xs text-red-600 hidden"></p>
                        </label>

                        <label class="block">
                            <span class="text-xs text-neutral-500 dark:text-neutral-400">Valor (R$)</span>
                            <input id="trAmount" name="amount" inputmode="decimal" placeholder="0,00"
                                   class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"
                                   required/>
                            <p id="trAmountErr" class="mt-1 text-xs text-red-600 hidden"></p>
                        </label>

                        <div class="mt-2 flex items-center justify-end gap-2">
                            <button type="button" id="trCancel"
                                    class="px-3 py-2 rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white shadow-soft">
                                Transferir
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Bottom Sheet: ações da conta (mobile) -->
        <div id="accSheet" class="fixed inset-0 z-[70] hidden" aria-modal="true" role="dialog">
            <div id="accSheetOv" class="absolute inset-0 bg-black/40 backdrop-blur-[2px]"></div>
            <div
                class="absolute inset-x-0 bottom-0 rounded-t-2xl border border-neutral-200/60 dark:border-neutral-800/60 bg-white dark:bg-neutral-900 shadow-soft p-2">
                <div class="mx-auto h-1 w-10 rounded-full bg-neutral-300/70 dark:bg-neutral-700/70 mb-2"></div>
                <div class="grid gap-1 p-1">
                    <button data-sheet-action="edit"
                            class="w-full text-left px-4 py-3 rounded-xl hover:bg-neutral-50 dark:hover:bg-neutral-800">
                        Editar
                    </button>

                    <button data-sheet-action="transfer"
                            class="w-full text-left px-4 py-3 rounded-xl hover:bg-neutral-50 dark:hover:bg-neutral-800">
                        Transferir
                    </button>

                    <button data-sheet-action="statement"
                            class="w-full text-left px-4 py-3 rounded-xl hover:bg-neutral-50 dark:hover:bg-neutral-800">
                        Ver extrato
                    </button>

                    <button data-sheet-action="delete"
                            class="w-full text-left px-4 py-3 rounded-xl text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                        Excluir
                    </button>
                </div>
            </div>
        </div>

    </section>

    @push('scripts')
        <script>
            (() => {
                const CSRF = '{{ csrf_token() }}';
                const ROUTES = {
                    index: "{{ route('accounts.index') }}",
                    store: "{{ route('accounts.store') }}",
                    show: "{{ url('/accounts') }}/:id",
                    update: "{{ url('/accounts') }}/:id",
                    destroy: "{{ url('/accounts') }}/:id",
                    savings: "{{ route('savings.index') }}",
                    tx: "{{ route('transaction-view.index') }}",
                    transfer: "{{ route('accounts.transfer') }}"
                };
                const u = (t, id) => t.replace(':id', id);

                const grid = document.getElementById('accGrid');
                const accFab = document.getElementById('accFab');
                const modal = document.getElementById('accModal');
                const overlay = document.getElementById('accOverlay');
                const btnOpeners = document.querySelectorAll('[data-open-modal="acc"]');
                const btnClose = document.getElementById('accClose');
                const btnCancel = document.getElementById('accCancel');
                const form = document.getElementById('accForm');
                const title = document.getElementById('accModalTitle');

                let mode = 'create';
                let currentId = null;
                let suppressUntil = 0;

                const sheet = document.getElementById('accSheet');
                const sheetOv = document.getElementById('accSheetOv');
                let sheetId = null;


                const ACC_CACHE_KEY = 'acc_cache_v1';

                // ==== Transferência - refs
                const trModal = document.getElementById('trModal');
                const trOverlay = document.getElementById('trOverlay');
                const trClose = document.getElementById('trClose');
                const trCancel = document.getElementById('trCancel');
                const trForm = document.getElementById('trForm');
                const trFrom = document.getElementById('trFrom');
                const trTo = document.getElementById('trTo');
                const trAmount = document.getElementById('trAmount');

                function openTransfer(fromId) {
                    clearFormErrors();

                    trForm.reset();
                    trFrom.value = fromId;

                    const cached = readCache();
                    const list = ensureArray(cached?.accounts) ?? [];
                    if (list.length) {
                        fillToSelect(list, fromId);
                    } else {
                        fetch(ROUTES.index, { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }})
                            .then(r => r.json())
                            .then(arr => fillToSelect(ensureArray(arr), fromId))
                            .catch(() => { trTo.innerHTML = '<option value="">Falha ao carregar</option>'; });
                    }

                    trModal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden', 'ui-modal-open');
                }

                trClose.addEventListener('click', ()=>{ clearFormErrors(); closeTransfer(); });
                trCancel.addEventListener('click', ()=>{ clearFormErrors(); closeTransfer(); });
                trOverlay.addEventListener('click', ()=>{ clearFormErrors(); closeTransfer(); });

                trTo.addEventListener('change', ()=> clearFieldError(trTo, 'trToErr'));
                trAmount.addEventListener('input', ()=> clearFieldError(trAmount, 'trAmountErr'));

                function closeTransfer() {
                    trModal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden', 'ui-modal-open');
                }

                function fillToSelect(accounts, fromId) {
                    const opts = accounts
                        .filter(a => String(a.id ?? a.uuid) !== String(fromId))
                        .map(a => {
                            const id = a.id ?? a.uuid;
                            const name = (a.bank_name ?? 'Sem título').toString().toUpperCase();
                            return `<option value="${id}">${name}</option>`;
                        });
                    trTo.innerHTML = opts.length ? opts.join('') : '<option value="">Nenhuma conta disponível</option>';
                }

                trClose.addEventListener('click', closeTransfer);
                trCancel.addEventListener('click', closeTransfer);
                trOverlay.addEventListener('click', closeTransfer);
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && !trModal.classList.contains('hidden')) closeTransfer();
                });

// util
                async function safeJson(res){ try { return await res.json(); } catch { return null; } }


                trForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    clearFormErrors();

                    const fd = new FormData(trForm);
                    const raw = fd.get('amount');
                    const cleaned = String(raw ?? '')
                        .replace(/[^\d,.-]/g, '')
                        .replace(/\.(?=\d{3}(?:\D|$))/g, '')
                        .replace(',', '.');
                    fd.set('amount', cleaned);

                    try {
                        showGridOverlay();

                        const res = await fetch(ROUTES.transfer, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': CSRF,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: fd
                        });

                        if (!res.ok) {
                            let data = null;
                            try { data = await res.json(); } catch {}
                            if (res.status === 422 && data?.errors){
                                // mapeia campos -> mensagens
                                if (data.errors.to_id?.[0])   showFieldError(trTo, 'trToErr', data.errors.to_id[0]);
                                if (data.errors.amount?.[0])  showFieldError(trAmount, 'trAmountErr', data.errors.amount[0]);
                                if (data.errors.from_id?.[0]) showFormError(data.errors.from_id[0]); // from_id é hidden → erro geral
                            } else {
                                showFormError(data?.message || 'Falha na transferência');
                            }
                            return;
                        }

                        closeTransfer();
                        await loadAccounts();
                    } catch (err) {
                        showFormError('Erro ao realizar transferência');
                        console.error(err);
                    } finally {
                        hideGridOverlay();
                    }
                });

                function clearFieldError(inputEl, errId){
                    const errEl = document.getElementById(errId);
                    errEl?.classList.add('hidden');
                    errEl && (errEl.textContent = '');
                    inputEl?.classList.remove('ring-2','ring-red-500/40','border-red-500');
                }

                function showFieldError(inputEl, errId, msg){
                    const errEl = document.getElementById(errId);
                    if (errEl){
                        errEl.textContent = msg || 'Campo inválido';
                        errEl.classList.remove('hidden');
                    }
                    inputEl?.classList.add('ring-2','ring-red-500/40','border-red-500');
                }

                function clearFormErrors(){
                    // campos do modal de transferência
                    clearFieldError(trTo, 'trToErr');
                    clearFieldError(trAmount, 'trAmountErr');
                    const g = document.getElementById('trFormErr');
                    if (g){ g.classList.add('hidden'); g.textContent=''; }
                }

                function showFormError(msg){
                    const g = document.getElementById('trFormErr');
                    if (g){ g.textContent = msg || 'Erro ao enviar'; g.classList.remove('hidden'); }
                }

                function readCache() {
                    try {
                        return JSON.parse(localStorage.getItem(ACC_CACHE_KEY)) || null;
                    } catch {
                        return null;
                    }
                }

                function writeCache(accounts, savings) {
                    try {
                        localStorage.setItem(ACC_CACHE_KEY, JSON.stringify({accounts, savings, t: Date.now()}));
                    } catch {
                    }
                }

                /* 4 skeleton cards */
                function renderSkeletons(n = 4) {
                    const item = `
  <article class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-5 shadow-soft dark:shadow-softDark">
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
      <div class="grid grid-cols-2 gap-3">
        <div class="h-16 rounded-xl skel"></div>
        <div class="h-16 rounded-xl skel"></div>
      </div>
    </div>
  </article>`;
                    grid.innerHTML = Array.from({length: n}).map(() => item).join('');
                }

                /* Overlay shimmer por cima do conteúdo atual */
                function showGridOverlay() {
                    grid.classList.add('grid-loading');
                }

                function hideGridOverlay() {
                    grid.classList.remove('grid-loading');
                }


                function openSheet(id) {
                    sheetId = id;
                    sheet.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden', 'ui-sheet-open'); // << add
                }

                function closeSheet() {
                    sheet.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden', 'ui-sheet-open'); // << add
                }

                grid.addEventListener('click', (e) => {
                    const more = e.target.closest('[data-action="more"]');
                    if (!more) return;
                    const card = more.closest('article[data-id]');
                    if (!card) return;
                    openSheet(card.dataset.id);
                });

                sheet.addEventListener('click', async (e) => {
                    const actBtn = e.target.closest('[data-sheet-action]');
                    if (!actBtn) return;

                    const act = actBtn.dataset.sheetAction;
                    if (act === 'cancel') return closeSheet();
                    if (!sheetId) return;

                    if (act === 'transfer') {
                        closeSheet();
                        openTransfer(sheetId);
                        return;
                    }

                    if (act === 'statement') {
                        closeSheet();
                        window.location.href = ROUTES.tx + '?account=' + encodeURIComponent(sheetId);
                        return;
                    }

                    if (act === 'edit') {
                        try {
                            const res = await fetch(u(ROUTES.show, sheetId), {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            if (!res.ok) throw 0;
                            const acc = await res.json();
                            closeSheet();
                            currentId = sheetId;
                            openModal('edit', acc);
                        } catch {
                            alert('Erro ao carregar conta');
                        }
                        return;
                    }

                    if (act === 'delete') {
                        const id = sheetId; // capture antes de fechar
                        closeSheet();
                        if (!confirm('Excluir esta conta?')) return;
                        try {
                            await doDeleteAccount(id);
                            // Remoção sem querySelector com URL:
                            const el = [...grid.querySelectorAll('article[data-id]')].find(n => n.dataset.id == id);
                            el?.remove();
                        } catch {
                            alert('Erro ao excluir');
                        }
                        return;
                    }
                });

                sheetOv.addEventListener('click', closeSheet);
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && !sheet.classList.contains('hidden')) closeSheet();
                });

                // ===== UTILS
                const moneyToNumber = (v) => {
                    if (v == null) return 0;
                    if (typeof v === 'number') return v;
                    const s = String(v).trim().replace(/[^\d,.-]/g, '');
                    if (s.includes(',') && s.includes('.')) return parseFloat(s.replace(/\./g, '').replace(',', '.')) || 0;
                    if (s.includes(',')) return parseFloat(s.replace(',', '.')) || 0;
                    return parseFloat(s) || 0;
                };

                const brl = (n) => (isNaN(n) ? 'R$ 0,00' : Number(n).toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                }));
                const ensureArray = (d) => Array.isArray(d) ? d : (d?.data ?? (typeof d === 'object' ? Object.values(d) : []));
                const buildSavingsMap = (arr) => {
                    const map = new Map();
                    for (const s of arr) {
                        const id = s.account_id || s.account?.id;
                        if (!id) continue;
                        map.set(id, (map.get(id) || 0) + moneyToNumber(s.current_amount));
                    }
                    return map;
                };
                const typeGradient = (t) => {
                    if (t === 'poupanca') return 'from-emerald-400 to-emerald-600';
                    if (t === 'investimento') return 'from-violet-400 to-violet-600';
                    return 'from-brand-400 to-brand-600';
                };
                const typeLabel = (t) => {
                    if (t === 'poupanca') return 'Poupança';
                    if (t === 'investimento') return 'Investimento';
                    return 'Conta corrente';
                };

                // ===== MODAL
                function setMode(m) {
                    mode = m;
                    const isShow = m === 'show';
                    title.textContent = m === 'edit' ? 'Editar conta' : (m === 'show' ? 'Detalhes da conta' : 'Nova conta bancária');
                    form.querySelectorAll('input, [type="radio"]').forEach(el => el.disabled = isShow);
                    form.querySelector('button[type="submit"]').classList.toggle('hidden', isShow);
                }

                function mapTypeIn(acc) {
                    const v = acc.type ?? acc.account_type ?? acc.account_type_id;
                    if (v === 1 || v === '1' || v === 'corrente') return '1';
                    if (v === 2 || v === '2' || v === 'poupanca') return '2';
                    if (v === 3 || v === '3' || v === 'investimento') return '3';
                    return '1';
                }

                function fillForm(acc) {
                    form.bank_name.value = acc.bank_name ?? '';
                    const raw = acc.current_balance;
                    form.current_balance.value = typeof raw === 'number' ? String(raw).replace('.', ',') : String(raw ?? '');
                    form.acc_id.value = acc.id ?? acc.uuid ?? '';
                    const t = mapTypeIn(acc);
                    form.querySelectorAll('input[name="type"]').forEach(i => i.checked = (i.value === t));
                }

                function resetForm() {
                    form.reset();
                }

                function openModal(m = 'create', data = null) {
                    setMode(m);
                    if (data) fillForm(data); else {
                        form.reset();
                        form.acc_id.value = '';
                    }
                    if ((m === 'edit' || m === 'show') && !form.acc_id.value) form.acc_id.value = currentId ?? '';
                    modal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden', 'ui-modal-open');
                }

                function closeModal() {
                    modal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden', 'ui-modal-open');
                }

                function openCreate(e) {
                    e?.preventDefault();
                    e?.stopPropagation();
                    currentId = null;
                    if (!sheet.classList.contains('hidden')) closeSheet();
                    openModal('create');
                }

                btnOpeners.forEach(b => b.addEventListener('click', openCreate));
                accFab?.addEventListener('click', openCreate, {passive: false});
                accFab?.addEventListener('touchend', openCreate, {passive: false});

                btnOpeners.forEach(b => b.addEventListener('click', () => {
                    currentId = null;
                    openModal('create');
                }));
                btnClose.addEventListener('click', closeModal);
                btnCancel.addEventListener('click', closeModal);
                overlay.addEventListener('click', closeModal);
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
                });

                // ===== RENDER
                function cardTemplate(acc, savingsMap) {
                    const id = acc.id ?? acc.uuid ?? acc.account_id;
                    const t = (acc.type ?? '1');
                    const g = typeGradient(t);
                    const label = typeLabel(t);
                    const inAccNum = moneyToNumber(acc.current_balance);
                    const cofrNum = (savingsMap?.get(id) != null) ? savingsMap.get(id) : moneyToNumber(acc.saving_amount);
                    const totalNum = inAccNum + cofrNum;

                    return `
                        <article data-id="${id}"
                          class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-5 shadow-soft dark:shadow-softDark group">
                          <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                              <span class="size-12 grid place-items-center rounded-xl bg-gradient-to-br ${g} text-white shadow-soft">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <path d="M3 3h18v6H3z"/><path d="M5 9v11h14V9"/>
                                </svg>
                              </span>
                              <div>
                                <p class="font-semibold">${(acc.bank_name ?? 'Sem título')}</p>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400">${label}</p>
                              </div>
                            </div>
                           <div class="flex items-center gap-2">
                              <!-- Desktop -->
                              <button data-action="edit" class="hidden md:inline-flex text-xs px-2 py-1.5 rounded-lg border hover:bg-neutral-50 dark:hover:bg-neutral-800">Editar</button>
                              <button data-action="transfer" class="hidden md:inline-flex text-xs px-2 py-1.5 rounded-lg border hover:bg-neutral-50 dark:hover:bg-neutral-800">Transferir</button>
                              <button data-action="delete" class="hidden md:inline-flex text-xs px-2 py-1.5 rounded-lg border border-red-200/70 text-red-600 hover:bg-red-50 dark:border-red-900/50 dark:text-red-400 dark:hover:bg-red-900/20">Excluir</button>

                               <button data-action="more"
                                      class="inline-grid size-10 place-items-center rounded-lg border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-800"
                                      aria-label="Mais ações">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                  <circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/>
                                </svg>
                              </button>
                        </div>

                          </div>

                          <div class="mt-4">
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">Saldo total</p>
                            <p class="text-3xl font-semibold tracking-tight">${brl(totalNum)}</p>
                          </div>

                          <div class="mt-3 grid grid-cols-2 gap-3">
                            <div class="rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 p-3">
                              <p class="text-xs text-neutral-500 dark:text-neutral-400">Em conta</p>
                              <p class="text-lg font-medium">${(typeof acc.current_balance === 'string') ? acc.current_balance : brl(inAccNum)}</p>
                            </div>
                            <div class="rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 p-3">
                              <p class="text-xs text-neutral-500 dark:text-neutral-400">Cofrinhos</p>
                              <p class="text-lg font-medium">${brl(cofrNum)}</p>
                            </div>
                          </div>
                        </article>`;
                }

                // Primeiro uso o cache (se existir)
                (function primeFromCache() {
                    const cached = readCache();
                    if (cached?.accounts?.length) {
                        const map = buildSavingsMap(ensureArray(cached.savings));
                        grid.innerHTML = cached.accounts.map(a => cardTemplate(a, map)).join('');
                        showGridOverlay(); // shimmer por cima dos dados enquanto atualiza
                    } else {
                        renderSkeletons(); // sem cache -> só skeleton
                    }
                })();

                async function loadAccounts() {
                    try {
                        const [resAcc, resSav] = await Promise.all([
                            fetch(ROUTES.index, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            }),
                            fetch(ROUTES.savings, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                        ]);

                        if (!resAcc.ok) throw new Error('Falha ao carregar contas');

                        const accounts = ensureArray(await resAcc.json());
                        const savings = resSav.ok ? ensureArray(await resSav.json()) : [];
                        const map = buildSavingsMap(savings);

                        grid.innerHTML = accounts.map(a => cardTemplate(a, map)).join('')
                            || `<div class="text-sm text-neutral-500">Nenhuma conta cadastrada.</div>`;

                        writeCache(accounts, savings);   // <-- cache atualizado
                    } catch (e) {
                        console.error(e);
                        // mantém cache/skeleton em caso de erro
                    } finally {
                        hideGridOverlay();               // <-- remove shimmer do overlay, se estava
                    }
                }

                // ===== AÇÕES (delegação)
                grid.addEventListener('click', async (e) => {
                    const card = e.target.closest('article[data-id]');
                    if (!card) return;
                    const id = card.dataset.id;

                    // Evita abrir SHOW ao clicar nos botões
                    const btn = e.target.closest('[data-action]');
                    if (btn) {
                        e.preventDefault();
                        suppressUntil = Date.now() + 400;

                        if (btn.dataset.action === 'statement') {
                            // ajuste se quiser filtrar por conta: ?account=id
                            window.location.href = ROUTES.tx + '?account=' + encodeURIComponent(id);
                            return;
                        }
                        if (btn.dataset.action === 'edit') {
                            const res = await fetch(u(ROUTES.show, id), {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            if (!res.ok) return alert('Erro ao carregar conta');
                            const acc = await res.json();
                            currentId = id;
                            openModal('edit', acc);
                            return;
                        }
                        if (btn.dataset.action === 'delete') {
                            if (!confirm('Excluir esta conta?')) return;
                            try {
                                await doDeleteAccount(id);
                                card.remove();
                            } catch {
                                alert('Erro ao excluir');
                            }
                            return;
                        }
                        return;
                    }

                    // Clique no card abre SHOW
                    if (Date.now() < suppressUntil) return;
                    try {
                        const res = await fetch(u(ROUTES.show, id), {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (!res.ok) throw 0;
                        const acc = await res.json();
                        currentId = id;
                        openModal('show', acc);
                    } catch {
                        alert('Erro ao carregar detalhes');
                    }
                });

                async function doDeleteAccount(rawId) {
                    const id = (rawId ?? '').toString().trim()
                        || form.acc_id?.value?.trim()
                        || currentId
                        || sheetId;
                    if (!id) {
                        alert('ID inválido');
                        return;
                    }

                    const url = u(ROUTES.destroy, encodeURIComponent(id));

                    // usa POST + _method=DELETE (Laravel-friendly)
                    const fd = new FormData();
                    fd.append('_method', 'DELETE');
                    fd.append('id', id);

                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: fd
                    });
                    if (!res.ok) throw new Error('Falha ao excluir');
                }

                // ===== SUBMIT
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const fd = new FormData(form);

                    // normaliza número (remove R$, pontos, mantém sinal; troca vírgula por ponto)
                    const val = fd.get('current_balance');
                    if (val != null) {
                        const cleaned = String(val).replace(/[^\d,.,-]/g, '').replace(/\.(?=\d{3}(?:\D|$))/g, '').replace(',', '.');
                        fd.set('current_balance', cleaned);
                    }

                    // garante ambos os campos se o backend ainda usa account_type string
                    const t = fd.get('type') || '1';
                    fd.set('type', t); // 1|2|3
                    fd.set('account_type', t === '2' ? 'poupanca' : t === '3' ? 'investimento' : 'corrente');

                    const id = form.acc_id.value?.trim();
                    const isEdit = !!id;

                    let url = isEdit ? u(ROUTES.update, id) : ROUTES.store;
                    const method = 'POST';
                    if (isEdit) fd.append('_method', 'PUT');

                    try {
                        const res = await fetch(url, {
                            method,
                            headers: {
                                'X-CSRF-TOKEN': CSRF,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: fd
                        });
                        if (!res.ok) throw 0;
                        closeModal();
                        form.acc_id.value = '';
                        await loadAccounts();
                    } catch {
                        alert('Erro ao salvar');
                    }
                });

                window.addEventListener('DOMContentLoaded', () => {
                    accFab?.classList.remove('hidden');
                    loadAccounts().catch(() => {/*já lidamos acima*/
                    });
                });

            })
                ();
        </script>
    @endpush
@endsection

