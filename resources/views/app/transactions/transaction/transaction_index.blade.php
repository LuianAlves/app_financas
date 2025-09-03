@extends('layouts.templates.app')
@section('content')
    <x-card-header
        prevRoute="{{ route('dashboard') }}"
        iconRight="fa-solid fa-money-bill-transfer"
        title="Transações"
        description="Acompanhe suas transações financeiras organizadas por categoria e tipo.">
    </x-card-header>

    <div class="st-filters mt-4" id="stFilters">
        <div class="row mt-2">
            <div class="col-6">
                <label class="k">Início</label>
                <input type="date" id="stStart" class="form-control" style="max-width:170px">
            </div>
            <div class="col-6">
                <label class="k">Fim</label>
                <input type="date" id="stEnd" class="form-control" style="max-width:170px">
            </div>
        </div>


                <div class="tx-tabs pb-1" id="stTabs">
                    <button type="button" class="tx-tab active" data-type="all">Todos</button>
                    <button type="button" class="tx-tab" data-type="entrada">Entradas</button>
                    <button type="button" class="tx-tab" data-type="despesa">Despesas</button>
                    <button type="button" class="tx-tab" data-type="investimento">Investimentos</button>
                </div>


        <div id="stSubcats" class="tx-chips mt-2 d-flex flex-wrap gap-2"></div>

        <button id="stApply" class="btn bg-color border-none ms-auto">
            <i class="fa fa-magnifying-glass me-1" style="font-size:12px;"></i>
            <span style="letter-spacing:.5px;font-size:14px;margin-left:2.5px;">Aplicar</span>
        </button>
    </div>

    <ul id="transactionList" class="swipe-list mt-4"></ul>

    <button id="openModal" class="create-btn">
        <i class="fa fa-plus text-white"></i>
    </button>

    <a href="{{route('transactionCategory-view.index')}}" class="create-btn create-other" data-nav>
        <i class="fa-solid fa-tags text-white"></i>
    </a>

    <a href="{{route('account-view.index')}}" class="create-btn create-other-2" data-nav>
        <i class="fas fa-landmark text-white"></i>
    </a>

    <a href="{{route('card-view.index')}}" class="create-btn create-other-3" data-nav>
        <i class="fas fa-credit-card text-white"></i>
    </a>

    <x-modal
        modalId="modalTransaction"
        formId="formTransaction"
        pathForm="app.transactions.transaction.transaction_form"
        :data="['cards' => $cards, 'categories' => $categories, 'accounts' => $accounts, 'savings' => $savings]"
    />

    <!-- CONFIRM CUSTOM -->
    <div id="confirmDelete" class="x-confirm" hidden>
        <div class="x-sheet" role="dialog" aria-modal="true" aria-labelledby="xConfirmTitle">
            <div class="x-head">
                <h5 id="xConfirmTitle">Remover</h5>
                <button type="button" class="x-close" data-action="cancel" aria-label="Fechar">×</button>
            </div>
            <div class="x-body">Você deseja remover?</div>
            <div class="x-actions">
                <button type="button" class="btn btn-light" data-action="cancel">Cancelar</button>
                <button type="button" class="btn btn-danger" data-action="confirm">Excluir</button>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            :root {
                --bg: #F6F7FA;
                --card: #FFF;
                --ink: #1F2937;
                --muted: #6B7280;
                --line: #E5E9F0;
                --soft: #F3F6FA;
                --pos: #1e00cf;
                --neg: #980000;
                --accent: #00BFA6;
            }

            .st-filters {
                display: flex;
                gap: 10px;
                align-items: flex-start;
                flex-direction: column;
                padding: 8px 10px;
                border-radius: 10px;
                background: var(--card);
                border: 1px solid var(--line);
            }

            .st-filters label.k {
                font-size: .8rem;
                color: var(--muted);
                font-weight: 500;
                margin: 0 2px
            }

            .st-filters .form-control {
                height: 36px;
                border: 1px solid var(--line);
                border-radius: 8px;
                font-size: .85rem
            }

            .sticky-summary > div {
                min-width: 0;
                display: flex;
                justify-content: space-between;
            }

            #stTabs {
                display: flex;
                gap: 8px;
                padding: 4px;
                background: #fff;
                border-radius: 12px;
            }

            .tx-tabs button {
                font-size: 12.5px !important;
                letter-spacing: .75px !important;
                font-weight: 500 !important;
                background: none !important;
                border: none !important;
                color: var(--muted);
                padding: 0 !important;
            }

            .tx-tabs button.active {
                color: var(--accent) !important;
            }

            #stTabs, #stSubcats {
                overflow-x: scroll;
                max-width: calc(100% - 5%) !important;
            }

            #stTabs .tx-tab {
                flex: 1;
                border: 0;
                background: transparent;
                padding: 10px 12px;
                border-radius: 8px;
                font-weight: 600;
                font-size: .9rem;
                color: var(--muted);
                transition: background .2s ease, color .2s ease, box-shadow .2s ease, transform .05s;
            }

            #stTabs .tx-tab:hover {
                color: var(--ink);
            }

            #stTabs .tx-tab:active {
                transform: scale(.98);
            }

            #stTabs .tx-tab.active {
                background: var(--accent);
                color: #fff;
            }

            /* ——— Subcategory chips ——— */
            #stSubcats {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                margin-top: 10px;
                padding-bottom: 2px;
                overflow-x: auto; /* desliza em telas pequenas */
                -webkit-overflow-scrolling: touch;
            }

            #stSubcats::-webkit-scrollbar {
                height: 6px;
            }

            #stSubcats::-webkit-scrollbar-thumb {
                background: rgba(0, 0, 0, .1);
                border-radius: 10px;
            }

            #stSubcats .chip {
                font-size: 12px !important;
                letter-spacing: .75px !important;
                font-weight: 500 !important;
                --ring: rgba(0, 191, 166, .18);
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 3.5px 7.5px;
                border: 1px solid var(--line);
                background: #fff;
                color: #334155;
                border-radius: 5px;
                cursor: pointer;
                user-select: none;
                transition: border-color .2s, background .2s, color .2s, box-shadow .2s;
            }


            #stSubcats .chip:hover {
                border-color: var(--accent);
            }

            #stSubcats .chip:focus-visible {
                outline: 2px solid var(--accent);
                outline-offset: 2px;
            }

            #stSubcats .chip.active {
                background: rgba(0, 191, 166, .10);
                color: var(--accent);
                border-color: var(--accent);
            }

            #stSubcats .chip .dot {
                width: 8px;
                height: 8px;
                border-radius: 999px;
                background: currentColor;
                display: inline-block;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            (() => {
                if (window.__TX_PAGE_BOUND__) return;
                window.__TX_PAGE_BOUND__ = true;

                window.store ??= {
                    set(k, v, ttl = 60) {
                        const exp = Date.now() + ttl * 1000;
                        sessionStorage.setItem(k, JSON.stringify({exp, v}));
                    },
                    get(k) {
                        const raw = sessionStorage.getItem(k);
                        if (!raw) return null;
                        try {
                            const {exp, v} = JSON.parse(raw);
                            if (Date.now() > exp) {
                                sessionStorage.removeItem(k);
                                return null;
                            }
                            return v;
                        } catch {
                            return null;
                        }
                    }
                };
                window.http ??= {
                    async get(url, {timeout = 8000, headers = {}} = {}) {
                        const ctrl = new AbortController();
                        const t = setTimeout(() => ctrl.abort('timeout'), timeout);
                        try {
                            const r = await fetch(url, {headers, signal: ctrl.signal});
                            if (!r.ok) throw new Error('HTTP ' + r.status);
                            const ct = r.headers.get('content-type') || '';
                            return ct.includes('json') ? r.json() : r.text();
                        } finally {
                            clearTimeout(t);
                        }
                    }
                };

// ====== FILTROS (escopados no segundo card) ======
                const API_URL = `{{ route('transactions.index') }}`;
                const ALL_CATS = @json($categories->map(fn($c)=>['id'=>$c->id,'name'=>$c->name,'type'=>$c->type])->values());

// estado
                const state = {type: 'all', catIds: new Set(), start: '', end: ''};

// pega elementos DENTRO do segundo card
                const fx = document.getElementById('stFilters');
                const tabsEl = fx.querySelector('#stTabs');
                const subcatsEl = fx.querySelector('#stSubcats');
                const inStart = fx.querySelector('#stStart');
                const inEnd = fx.querySelector('#stEnd');

// monta querystring a partir do estado
                function qsFromState() {
                    const p = new URLSearchParams();
                    if (state.type !== 'all') p.set('type', state.type);
                    if (state.start) p.set('start', state.start);
                    if (state.end) p.set('end', state.end);
                    if (state.catIds.size) [...state.catIds].forEach(id => p.append('category_ids[]', id));
                    return p.toString();
                }

// render dos subfiltros (categorias)
                function renderSubcats() {
                    subcatsEl.innerHTML = '';
                    if (state.type === 'all') return;

                    const cats = ALL_CATS.filter(c => c.type === state.type);
                    if (!cats.length) {
                        subcatsEl.innerHTML = '<small class="text-muted">Sem categorias</small>';
                        return;
                    }

                    const all = document.createElement('button');
                    all.className = 'chip ' + (state.catIds.size ? '' : 'active');
                    all.textContent = 'Todas';
                    all.addEventListener('click', () => {
                        state.catIds.clear();
                        renderSubcats();
                    });
                    subcatsEl.appendChild(all);

                    for (const c of cats) {
                        const b = document.createElement('button');
                        b.className = 'chip ' + (state.catIds.has(c.id) ? 'active' : '');
                        b.textContent = c.name;
                        b.addEventListener('click', () => {
                            if (state.catIds.has(c.id)) state.catIds.delete(c.id);
                            else state.catIds.add(c.id);
                            renderSubcats();
                        });
                        subcatsEl.appendChild(b);
                    }
                }

// troca de aba
                tabsEl.addEventListener('click', (e) => {
                    const btn = e.target.closest('.tx-tab');
                    if (!btn) return;
                    tabsEl.querySelectorAll('.tx-tab').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    state.type = btn.dataset.type || 'all';
                    state.catIds.clear();
                    renderSubcats();
                });

// atalhos de período (escopados)
                fx.querySelectorAll('[data-range]').forEach(b => {
                    b.addEventListener('click', () => {
                        const addM = parseInt(b.dataset.range, 10) || 1;
                        const base = inStart.value ? new Date(inStart.value) : new Date();
                        const start = new Date(base.getFullYear(), base.getMonth(), 1);
                        const end = new Date(start.getFullYear(), start.getMonth() + addM, 0);
                        // NÃO use toISOString() para não “pular” dia
                        const iso = d => [
                            d.getFullYear(),
                            String(d.getMonth() + 1).padStart(2, '0'),
                            String(d.getDate()).padStart(2, '0')
                        ].join('-');
                        inStart.value = iso(start);
                        inEnd.value = iso(end);
                    });
                });

// aplicar filtros
                fx.querySelector('#stApply').addEventListener('click', () => {
                    state.start = inStart.value || '';
                    state.end = inEnd.value || '';
                    sessionStorage.removeItem(CACHE_KEY);
                    loadTransactions();
                });

// render inicial
                renderSubcats();

                const list = document.getElementById('transactionList');
                const form = document.getElementById('formTransaction');
                const modalEl = document.getElementById('modalTransaction');
                const saveBtn = form.querySelector('button[type="submit"]');
                const CACHE_KEY = 'tx:list:v1';

                function $(sel) {
                    return form.querySelector(sel);
                }

                function setVal(id, val) {
                    const el = $('#' + id);
                    if (el) el.value = (val ?? '');
                }

                function setCheck(id, on) {
                    const el = $('#' + id);
                    if (el) {
                        el.checked = !!on;
                        el.dispatchEvent(new Event('change'));
                    }
                }

                const TYPE_COLOR = {pix: '#2ecc71', card: '#3498db', money: '#f39c12'};
                const TYPE_LABEL = {pix: 'Pix', card: 'Cartão', money: 'Dinheiro'};
                const OPEN_W = 96, TH_OPEN = 40;

                let swipe = {active: null, startX: 0, dragging: false};
                let currentMode = 'create'; // create|edit|show
                let currentId = null;
                let pendingDeleteId = null;

                const xConfirm = document.getElementById('confirmDelete');
                const xConfirmBtn = xConfirm.querySelector('[data-action="confirm"]');
                const xCancelBtn = xConfirm.querySelectorAll('[data-action="cancel"]');

                function openConfirm() {
                    xConfirm.classList.add('show');
                    xConfirm.hidden = false;
                    document.body.classList.add('modal-open');
                }

                function closeConfirm() {
                    xConfirm.classList.remove('show');
                    xConfirm.hidden = true;
                    document.body.classList.remove('modal-open');
                }

                xConfirmBtn.addEventListener('click', async () => {
                    await doDelete();
                    closeConfirm();
                });

                xCancelBtn.forEach(btn => btn.addEventListener('click', closeConfirm));

                xConfirm.addEventListener('click', (e) => {
                    if (e.target === xConfirm) {
                        closeConfirm()
                    }
                    ;

                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && !xConfirm.hidden) closeConfirm();
                });

                function brl(v) {
                    const n = typeof v === 'number' ? v : parseFloat(String(v).replace(/[^\d.-]/g, ''));
                    if (isNaN(n)) return 'R$ 0,00';
                    return n.toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
                }

                function renderTx(tx) {
                    const id = tx.id ?? tx.uuid ?? tx._id ?? tx.transaction_id;

                    const src = tx.create_date ?? tx.date;
                    const d = src ? new Date(src) : null;

                    const date = d && !isNaN(d)
                        ? (() => {
                            const meses = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];
                            const dayPt  = d.toLocaleString('pt-BR', { day: '2-digit', timeZone: 'America/Sao_Paulo' });
                            const longPt = d.toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', timeZone: 'America/Sao_Paulo' });
                            const mShort = meses[d.getMonth()];
                            return `${dayPt} ${mShort}`; // ex: 04 nov (04 de novembro)
                        })()
                        : '';

                    const type = tx.type ?? 'money';
                    const color = ({pix: '#2ecc71', card: '#3498db', money: '#f39c12'})[type] || '#777';
                    const label = ({pix: 'Pix', card: 'Cartão', money: 'Dinheiro'})[type] || type;
                    return `
    <li class="swipe-item" data-id="${id}">
      <button class="swipe-edit-btn" type="button">Editar</button>
      <div class="swipe-content">
        <div class="tx-line">
          <div class="d-flex justify-content-between flex-column">
            <span class="tx-title">${tx.title ?? 'Sem título'}</span>
            <small class="tx-date">${date}</small>
          </div>
          <div class="text-end">
            <span class="tx-amount">${tx.amount}</span><br>
            <span class="badge" style="font-size:10px;background:${color};color:#fff">${label}</span>
          </div>
        </div>
      </div>
      <button class="swipe-delete-btn" type="button">Excluir</button>
    </li>`;
                }

                function clearList() {
                    list.innerHTML = '';
                }

                function renderList(data) {
                    clearList();
                    data.forEach(tx => list.insertAdjacentHTML('beforeend', renderTx(tx)));
                }

                function showSkeleton() {
                    list.innerHTML = `{!! str_replace("\n","", addslashes(view('partials._skeleton-tx-list')->render())) !!}`;
                }

                const showUrl = id => `{{ url('/transactions') }}/${id}`;
                const detailCache = new Map();
                const io = new IntersectionObserver((entries) => {
                    entries.forEach(ent => {
                        if (!ent.isIntersecting) return;
                        const li = ent.target;
                        const id = li.dataset.id;
                        if (!id || detailCache.has(id)) return;
                        fetch(showUrl(id), {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(r => r.ok ? r.json() : null).then(json => {
                            if (json) detailCache.set(id, json);
                        }).catch(() => {
                        });
                        io.unobserve(li);
                    });
                }, {rootMargin: '200px 0px'});

                function setFormMode(mode) {
                    currentMode = mode;
                    const isShow = (mode === 'show');
                    form.querySelectorAll('input,select,textarea,button').forEach(el => {
                        if (el.type === 'submit') return;
                        el.disabled = isShow;
                    });
                    saveBtn.classList.toggle('d-none', isShow);
                }

                function fillForm(tx) {
                    setVal('title', tx.title);
                    setVal('description', tx.description);
                    setVal('amount', tx.amount);
                    setVal('date', String(tx.date ?? '').slice(0, 10));
                    setVal('transaction_category_id', tx.transaction_category_id);

                    const type = tx.type || 'pix';
                    setCheck('pix', type === 'pix');
                    setCheck('card', type === 'card');
                    setCheck('money', type === 'money');

                    if (tx.account_id) setVal('account_id', tx.account_id);
                    if (tx.card_id) setVal('card_id', tx.card_id);

                    if (tx.type_card) {
                        setCheck('credit', tx.type_card === 'credit');
                        setCheck('debit', tx.type_card === 'debit');
                    }

                    const rec = tx.recurrence_type || 'unique';
                    setCheck('unique', rec === 'unique');
                    setCheck('monthly', rec === 'monthly');
                    setCheck('yearly', rec === 'yearly');
                    setCheck('custom', rec === 'custom');

                    if (tx.custom_occurrences) setVal('custom_occurrences', tx.custom_occurrences);
                    if (tx.installments) setVal('installments', tx.installments);
                }

                function clearForm() {
                    form.reset();
                    ['pix', 'card', 'money', 'credit', 'debit', 'unique', 'monthly', 'yearly', 'custom'].forEach(id => {
                        const el = $('#' + id);
                        if (el) el.dispatchEvent(new Event('change'));
                    });
                }

                function openTxModal(mode, tx) {
                    setFormMode(mode);
                    if (tx) fillForm(tx); else clearForm();
                    modalEl.classList.add('show');

                    document.body.classList.add('modal-open');   // <- adiciona
                }

                function closeTxModal() {
                    modalEl.classList.remove('show');
                    document.body.classList.remove('modal-open'); // <- remove
                }

                function closeIfOutside(e) {
                    if (!modalEl.classList.contains('show')) return;

                    const r = form.getBoundingClientRect();
                    const p = e.touches ? e.touches[0] : e;
                    const x = p.clientX, y = p.clientY;

                    const inside = x >= r.left && x <= r.right && y >= r.top && y <= r.bottom;
                    if (!inside) {
                        e.preventDefault();
                        e.stopPropagation();
                        closeTxModal();
                    }
                }

                window.addEventListener('pointerdown', closeIfOutside, true);
                window.addEventListener('touchstart', closeIfOutside, {capture: true, passive: false});

                async function loadTransactions() {
                    const cacheKey = CACHE_KEY + (qsFromState() || 'all');
                    const cached = store.get(cacheKey);

                    if (cached) {
                        renderList(cached);
                        list.querySelectorAll('.swipe-item').forEach(li => io.observe(li));
                    } else {
                        showSkeleton();
                    }

                    try {
                        const params = qsFromState();
                        const url = params ? `${API_URL}?${params}` : API_URL;

                        const data = await http.get(url, {
                            timeout: 8000,
                            headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
                        });

                        renderList(data);

                        store.set(cacheKey, data, 60);

                        list.querySelectorAll('.swipe-item').forEach(li => io.observe(li));
                    } catch (e) {
                        if (!cached) {
                            list.innerHTML = `<p style="padding:8px;color:#666">Sem conexão. Tente novamente.</p>`;
                        }
                    }
                }

                async function readBodySafe(res) {
                    const ct = res.headers.get('content-type') || '';
                    if (ct.includes('application/json')) {
                        try {
                            return await res.json();
                        } catch {
                            return null;
                        }
                    }
                    try {
                        return await res.text();
                    } catch {
                        return null;
                    }
                }

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();

                    // garante modo edit não deixa nada desabilitado
                    if (currentMode === 'edit') {
                        form.querySelectorAll('[disabled]').forEach(el => el.disabled = false);
                    }

                    const fd = new FormData(form);
                    const isEdit = currentMode === 'edit' && currentId;

                    let url, method = 'POST';
                    if (isEdit) {
                        url = `{{ url('/transactions') }}/${currentId}`;
                        fd.append('_method', 'PUT'); // spoof seguro para Laravel
                    } else {
                        url = `{{ route('transactions.store') }}`;
                    }

                    let res;
                    try {
                        res = await fetch(url, {
                            method,
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: fd
                        });
                    } catch (err) {
                        alert('Falha de rede ao salvar');
                        return;
                    }

                    if (!res.ok) {
                        const body = await readBodySafe(res);
                        const msg = (body && body.message) ? body.message
                            : (typeof body === 'string' ? body : null);
                        alert(msg || 'Erro ao salvar');
                        return;
                    }

                    sessionStorage.removeItem(CACHE_KEY);

                    closeTxModal();
                    await loadTransactions();
                });

                function closeAll() {
                    document.querySelectorAll('.swipe-item.open-left,.swipe-item.open-right').forEach(li => li.classList.remove('open-left', 'open-right'));
                }

                let suppressShowUntil = 0;
                const suppressShow = (ms = 800) => {
                    suppressShowUntil = Date.now() + ms;
                };

                function dragTranslate(item, px) {
                    const content = item.querySelector('.swipe-content');
                    content.style.transition = 'none';
                    const clamp = Math.max(-OPEN_W, Math.min(OPEN_W, px));
                    content.style.transform = `translateX(${clamp}px)`;
                }

                function restoreTransition(item) {
                    const content = item.querySelector('.swipe-content');
                    requestAnimationFrame(() => content.style.transition = 'transform 160ms ease');
                }

                function onStart(e) {

                    if (document.body.classList.contains('modal-open')) return;

                    const li = e.target.closest('.swipe-item');
                    if (!li) return;
                    closeAll();
                    swipe.active = li;
                    swipe.dragging = true;
                    swipe.startX = (e.touches ? e.touches[0].clientX : e.clientX);
                    li.querySelector('.swipe-content').style.transition = 'none';
                }

                function onMove(e) {
                    if (document.body.classList.contains('modal-open')) return;

                    if (!swipe.dragging || !swipe.active) return;
                    const x = (e.touches ? e.touches[0].clientX : e.clientX);
                    const dx = x - swipe.startX;
                    let base = 0;
                    if (swipe.active.classList.contains('open-left')) base = -OPEN_W;
                    if (swipe.active.classList.contains('open-right')) base = OPEN_W;
                    const move = base + dx;
                    if (move < 0) dragTranslate(swipe.active, Math.max(move, -OPEN_W));
                    else dragTranslate(swipe.active, Math.min(move, OPEN_W));
                }

                function onEnd() {
                    if (document.body.classList.contains('modal-open')) return;

                    if (!swipe.dragging || !swipe.active) return;
                    const content = swipe.active.querySelector('.swipe-content');
                    restoreTransition(swipe.active);
                    const m = new WebKitCSSMatrix(getComputedStyle(content).transform);
                    const finalX = m.m41;
                    swipe.active.classList.remove('open-left', 'open-right');
                    if (finalX <= -TH_OPEN) swipe.active.classList.add('open-left');
                    else if (finalX >= TH_OPEN) swipe.active.classList.add('open-right');
                    content.style.transform = '';
                    swipe.dragging = false;
                    swipe.active = null;
                }

                list.addEventListener('touchstart', onStart, {passive: true});
                list.addEventListener('mousedown', onStart);
                window.addEventListener('touchmove', onMove, {passive: false});
                window.addEventListener('mousemove', onMove);
                window.addEventListener('touchend', onEnd);
                window.addEventListener('mouseup', onEnd);
                document.addEventListener('click', (e) => {
                    if (!e.target.closest('.swipe-item')) closeAll();
                });

                async function handleEdit(id) {
                    const cached = detailCache.get(id);
                    if (cached) openTxModal('edit', cached);
                    try {
                        const tx = await http.get(showUrl(id), {
                            timeout: 6000,
                            headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
                        });
                        openTxModal('edit', tx);
                        detailCache.set(id, tx);
                    } catch (e) {
                        if (e.message?.includes('401')) {
                            location.href = '/transaction';
                            return;
                        }
                        if (!cached) list.innerHTML = `<p style="padding:8px;color:#666">Sem conexão. Tente novamente.</p>`;
                    }
                }

                function handleAskDelete(id) {
                    pendingDeleteId = id;
                    openConfirm();
                }

                list.addEventListener('touchstart', (e) => {
                    if (document.body.classList.contains('modal-open')) return;
                    const btn = e.target.closest('.swipe-edit-btn, .swipe-delete-btn');
                    if (!btn) return;

                    e.preventDefault(); // evita click fantasma
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    suppressShow(); // idem

                    const li = btn.closest('.swipe-item');
                    const id = li?.dataset.id;
                    if (!id) return;

                    if (btn.classList.contains('swipe-edit-btn')) handleEdit(id);
                    else handleAskDelete(id);
                }, {capture: true, passive: false});

                list.addEventListener('pointerdown', (e) => {
                    if (document.body.classList.contains('modal-open')) return; // <- aqui

                    const btn = e.target.closest('.swipe-edit-btn, .swipe-delete-btn');
                    if (!btn) return;

                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    suppressShow();

                    const li = btn.closest('.swipe-item');
                    const id = li?.dataset.id;
                    if (!id) return;

                    if (btn.classList.contains('swipe-edit-btn')) {
                        handleEdit(id);
                    } else {
                        handleAskDelete(id);
                    }
                }, true);

                list.addEventListener('click', (e) => {
                    if (document.body.classList.contains('modal-open')) return; // <- aqui

                    if (e.target.closest('.swipe-edit-btn, .swipe-delete-btn')) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                    }
                }, true);

                list.addEventListener('click', async (e) => {
                    if (Date.now() < suppressShowUntil) return;

                    const content = e.target.closest('.swipe-content');

                    if (!content) return;

                    const li = content.closest('.swipe-item');
                    if (!li) return;

                    if (li.classList.contains('open-left') || li.classList.contains('open-right')) {
                        closeAll();
                        return;
                    }

                    const id = li.dataset.id;
                    currentId = id;

                    try {
                        const tx = await http.get(`{{ url('/transactions') }}/${id}`, {
                            timeout: 6000,
                            headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
                        });

                        openTxModal('show', tx);
                    } catch (err) {
                        alert(err.message);
                    }
                });

                async function doDelete() {
                    if (!pendingDeleteId) return;

                    const res = await fetch(`{{ url('/transactions') }}/${pendingDeleteId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!res.ok) {
                        alert('Erro ao excluir');
                        return;
                    }

                    const li = list.querySelector(`.swipe-item[data-id="${pendingDeleteId}"]`);

                    if (li) li.remove();
                    pendingDeleteId = null;

                    sessionStorage.removeItem(CACHE_KEY);
                    detailCache.delete(pendingDeleteId);

                    const cEl = document.getElementById('confirmDeleteModal');

                    if (window.bootstrap && cEl) window.bootstrap.Modal.getInstance(cEl)?.hide();

                    pendingDeleteId = null;
                }

                const confirmBtn = document.getElementById('confirmDeleteBtn');
                if (confirmBtn) confirmBtn.addEventListener('click', doDelete);

                const openBtn = document.getElementById('openModal');

                openBtn?.addEventListener('click', () => {
                    currentId = null;
                    openTxModal('create', null);
                });

                const closeBtn = document.getElementById('closeModal');
                if (closeBtn) closeBtn.addEventListener('click', closeTxModal);

                loadTransactions();
            })();
        </script>
    @endpush
@endsection

