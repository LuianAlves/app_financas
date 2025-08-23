@extends('layouts.templates.app')
@section('content')
    <x-card-header
        prevRoute="{{ route('dashboard') }}"
        iconRight="fa-solid fa-money-bill-transfer"
        title="Transações"
        description="Acompanhe suas transações financeiras organizadas por categoria e tipo.">
    </x-card-header>

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
            #modalTransaction {
                pointer-events: auto;
            }

            #formTransaction {
                pointer-events: auto;
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

                function renderTx(tx) { /* ... use sua função atual ... */
                    const id = tx.id ?? tx.uuid ?? tx._id ?? tx.transaction_id;
                    const date = tx.date ?? tx.created_at ?? '';
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
                    // 1) tenta cache
                    const cached = store.get(CACHE_KEY);

                    if (cached) {
                        renderList(cached);
                        list.querySelectorAll('.swipe-item').forEach(li => io.observe(li));
                    } else {
                        showSkeleton();
                    }

                    try {
                        const data = await http.get(`{{ route('transactions.index') }}`, {
                            timeout: 8000,
                            headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
                        });
                        renderList(data);
                        store.set(CACHE_KEY, data, 60);
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
                    } catch(e){
                        if (e.message?.includes('401')) { location.href = '/transaction'; return; }
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
