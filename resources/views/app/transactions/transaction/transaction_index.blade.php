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

    <a href="{{route('transactionCategory-view.index')}}" class="create-btn create-other">
        <i class="fa-solid fa-tags text-white"></i>
    </a>

    <a href="{{route('account-view.index')}}" class="create-btn create-other-2">
        <i class="fas fa-landmark text-white"></i>
    </a>

    <a href="{{route('card-view.index')}}" class="create-btn create-other-3">
        <i class="fas fa-credit-card text-white"></i>
    </a>

    <x-modal
        modalId="modalTransaction"
        formId="formTransaction"
        pathForm="app.transactions.transaction.transaction_form"
        :data="['cards' => $cards, 'categories' => $categories, 'accounts' => $accounts]"
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
            #modalTransaction{ pointer-events:auto; }
            #formTransaction{ pointer-events:auto; }
        </style>
    @endpush

    @push('scripts')
        <script>
            (() => {
                const list = document.getElementById('transactionList');
                const form = document.getElementById('formTransaction');
                const modalEl = document.getElementById('modalTransaction');
                const saveBtn = form.querySelector('button[type="submit"]');

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
                    if (e.target === xConfirm) { closeConfirm() };

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
                    const date = tx.date ?? tx.created_at ?? '';
                    const type = tx.type ?? 'money';
                    const color = TYPE_COLOR[type] || '#777';
                    const label = TYPE_LABEL[type] || type;
                    return `
                      <li class="swipe-item" data-id="${id}">
                        <button class="swipe-edit-btn" type="button">Editar</button>
                        <div class="swipe-content">
                          <div class="tx-line">
                            <div class="d-flex justify-content-between flex-column">
                              <span class="tx-title">${tx.title ?? 'Sem título'}</span>
                              <small class="tx-date">Em ${date}</small>
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

                function storeTransaction(tx) {
                    list.insertAdjacentHTML('afterbegin', renderTx(tx));
                }

                function clearList() {
                    list.innerHTML = '';
                }

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

                if (list.dataset.bound === '1') return;
                list.dataset.bound = '1';

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

                function closeIfOutside(e){
                    if (!modalEl.classList.contains('show')) return;

                    const r = form.getBoundingClientRect();
                    const p = e.touches ? e.touches[0] : e;
                    const x = p.clientX, y = p.clientY;

                    const inside = x >= r.left && x <= r.right && y >= r.top && y <= r.bottom;
                    if (!inside){
                        e.preventDefault();
                        e.stopPropagation();
                        closeTxModal();
                    }
                }

                window.addEventListener('pointerdown', closeIfOutside, true);
                window.addEventListener('touchstart', closeIfOutside, { capture:true, passive:false });

                async function loadTransactions() {
                    const res = await fetch("{{ route('transactions.index') }}", {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) {
                        alert('Erro ao carregar');
                        return;
                    }
                    const data = await res.json();
                    clearList();
                    data.forEach(storeTransaction);
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

                    // ok (200/201/204) – ignora body
                    closeTxModal();
                    await loadTransactions();
                });

                function closeAll() {
                    document.querySelectorAll('.swipe-item.open-left,.swipe-item.open-right').forEach(li => li.classList.remove('open-left', 'open-right'));
                }

                let suppressShowUntil = 0;
                const suppressShow = (ms=800) => { suppressShowUntil = Date.now() + ms; };

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
                    currentId = id;
                    const res = await fetch(`{{ url('/transactions') }}/${id}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) {
                        alert('Erro ao carregar');
                        return;
                    }
                    const tx = await res.json();
                    openTxModal('edit', tx);
                }

                function handleAskDelete(id) {
                    pendingDeleteId = id;
                    openConfirm();
                }

                list.addEventListener('touchstart', (e)=>{
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
                }, { capture:true, passive:false });

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
                    if (Date.now() < suppressShowUntil);

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
                        const res = await fetch(`{{ url('/transactions') }}/${id}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (!res.ok) throw new Error('Erro ao carregar');
                        const tx = await res.json();
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
                    const cEl = document.getElementById('confirmDeleteModal');
                    if (window.bootstrap && cEl) window.bootstrap.Modal.getInstance(cEl)?.hide();
                    pendingDeleteId = null;
                }

                const confirmBtn = document.getElementById('confirmDeleteBtn');
                if (confirmBtn) confirmBtn.addEventListener('click', doDelete);

                const openBtn = document.getElementById('openModal');
                if (openBtn) openBtn.addEventListener('click', () => {
                    currentId = null;
                    openTxModal('create', null);
                });
                const closeBtn = document.getElementById('closeModal');
                if (closeBtn) closeBtn.addEventListener('click', closeTxModal);

                window.addEventListener('DOMContentLoaded', loadTransactions);
            })();
        </script>
    @endpush
@endsection
