@extends('layouts.templates.app')
@section('new-content')
    <x-card-header prevRoute="{{ route('card-view.index') }}" iconRight="fa-solid fa-credit-card" title=""
                   description=""></x-card-header>

    {{-- Carrossel de meses --}}
    <div class="icons-carousel" id="months">
        @foreach($invoices as $inv)
            <button class="icon-button nav-link-atalho month-btn {{ $selectedYm === $inv->ym ? 'active' : '' }}" data-ym="{{ $inv->ym }}">
                <span class="bg-{{ $inv->paid ? 'success' : 'danger' }}">{{ $inv->month }}</span>
                <b>{{ $inv->total }}</b>
            </button>
        @endforeach
    </div>

    {{-- Header da fatura selecionada --}}
    <div class="balance-box m-0 mb-3" id="invoiceHeader" data-card="{{ $card->id }}">
        <div class="d-flex align-items-center justify-content-between">
            <span class="fw-bold" id="hdr-month">Fatura de {{ $header['month_label'] }}</span>
            <a href="{{route('invoice-payment.update', [$card->id, $header['ym']])}}">
                <i class="fa-solid fa-check-to-slot text-success fs-5"></i>
            </a>
        </div>
        <strong id="hdr-total">{{ $header['total'] }}</strong>
        <span>Limite disponível <b id="hdr-limit">{{ $header['limit'] }}</b></span>
        <span class="closing-date" id="hdr-close">{!! $header['close_label'] !!}</span>
        <span class="due-date" id="hdr-due">{!! $header['due_label'] !!}</span>
    </div>

    <ul id="invoiceItems" class="swipe-list"></ul>

    <x-modal
        modalId="modalInvoiceItem"
        formId="formInvoiceItem"
        pathForm="app.invoices.invoice_item.invoice_item_form"
        :data="[]"
    />

    <div id="confirmDeleteItem" class="x-confirm" hidden>
        <div class="x-sheet" role="dialog" aria-modal="true" aria-labelledby="xConfirmTitle2">
            <div class="x-head">
                <h5 id="xConfirmTitle2">Remover item</h5>
                <button type="button" class="x-close" data-action="cancel" aria-label="Fechar">×</button>
            </div>
            <div class="x-body">Deseja remover este item da fatura?</div>
            <div class="x-actions">
                <button type="button" class="btn btn-light" data-action="cancel">Cancelar</button>
                <button type="button" class="btn btn-danger" data-action="confirm">Excluir</button>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .icons-carousel {
            padding: 16px;
            margin-bottom: 10px;
            gap: 40px;
            display: flex;
            overflow: auto
        }

        .icon-button {
            background: transparent;
            border: 0;
            cursor: pointer;
            text-align: center
        }

        .icon-button.active span {
            outline: 2px solid #0bb;
        }

        .icon-button span {
            background: #e74c3c;
            color: #fff;
            border-radius: 50%;
            padding: 12px;
            margin: 10px;
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 12px
        }

        .balance-box {
            height: auto;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .swipe-list {
            list-style: none;
            margin: 8px 0;
            padding: 0
        }

        .swipe-item {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            border: 1px solid #eee;
            background: #fff;
        }

        .swipe-content {
            padding: 0 !important;
        }

        .swipe-edit-btn, .swipe-delete-btn {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 96px;
            border: 0;
            color: #fff;
            font-weight: 600;
            z-index: 1
        }

        .swipe-edit-btn {
            left: 0;
            background: #3498db
        }

        .swipe-delete-btn {
            right: 0;
            background: #e74c3c
        }

        .swipe-item.open-left .swipe-content {
            transform: translateX(-96px)
        }

        .swipe-item.open-right .swipe-content {
            transform: translateX(96px)
        }

        .tx-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px
        }

        .tx-title {
            font-weight: 600
        }

        .tx-date {
            color: #6b7280
        }

        .badge {
            border-radius: 6px;
            padding: 2px 6px
        }

        #modalInvoiceItem {
            pointer-events: auto;
        }

        #formInvoiceItem {
            pointer-events: auto;
        }
    </style>
@endpush

@push('scripts')
    <script>
        (() => {
            const months = document.getElementById('months');
            const itemsBox = document.getElementById('invoiceItems');
            const hdrMonth = document.getElementById('hdr-month');
            const hdrTotal = document.getElementById('hdr-total');
            const hdrLimit = document.getElementById('hdr-limit');
            const hdrClose = document.getElementById('hdr-close');
            const hdrDue = document.getElementById('hdr-due');
            const cardId = document.getElementById('invoiceHeader').dataset.card;

            const modalEl = document.getElementById('modalInvoiceItem');
            const form = document.getElementById('formInvoiceItem');
            const saveBtn = form?.querySelector('button[type="submit"]');

            const xConfirm = document.getElementById('confirmDeleteItem');
            const xConfirmBtn = xConfirm.querySelector('[data-action="confirm"]');
            const xCancelBtns = xConfirm.querySelectorAll('[data-action="cancel"]');

            const CSRF = '{{ csrf_token() }}';
            const OPEN_W = 96, TH_OPEN = 40;

            let currentMode = 'create'; // create|edit|show
            let currentId = null;
            let pendingDeleteId = null;

            // ====== Utils form
            function $(sel) {
                return form?.querySelector(sel);
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

            function getId(it) {
                return it.uuid || it.id || it._id || it.invoice_item_id;
            }

            function setFormMode(mode) {
                currentMode = mode;
                const isShow = mode === 'show';
                form.querySelectorAll('input,select,textarea,button').forEach(el => {
                    if (el.type === 'submit') return;
                    el.disabled = isShow;
                });
                saveBtn?.classList.toggle('d-none', isShow);
            }

            function fillForm(it) {
                // Ajuste estes IDs conforme seu form partial:
                setVal('title', it.title);
                setVal('amount', it.raw_amount ?? it.amount);
                setVal('date', String(it.date ?? '').slice(0, 10));
                if (it.installments) {
                    setVal('installments', it.installments);
                }
                if (it.current_installment) {
                    setVal('current_installment', it.current_installment);
                }
                // Exemplos de flags
                setCheck('is_projection', !!it.is_projection);
            }

            function clearForm() {
                form.reset();
            }

            function openModal(mode, it) {
                setFormMode(mode);
                if (it) fillForm(it); else clearForm();
                modalEl.classList.add('show');
                document.body.classList.add('modal-open');
            }

            function closeModal() {
                modalEl.classList.remove('show');
                document.body.classList.remove('modal-open');
            }

            function openConfirm() {
                xConfirm.hidden = false;
                xConfirm.classList.add('show');
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
            xCancelBtns.forEach(b => b.addEventListener('click', closeConfirm));
            xConfirm.addEventListener('click', (e) => {
                if (e.target === xConfirm) closeConfirm();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !xConfirm.hidden) closeConfirm();
            });

            // ====== Render
            function renderItem(it) {
                const id = getId(it);
                const inst = (it.installments > 1) ? `<small>${it.current_installment}/${it.installments}</small> ` : '';
                const proj = it.is_projection ? '<small>(proj.)</small>' : '';
                const date = it.date ?? '';
                const amount = it.amount;

                const iconCls = it.icon && it.icon.trim() ? it.icon : 'fa-solid fa-tag';
                const bg = it.color || '#999';

                return `
                          <li class="swipe-item" data-id="${id}">
                                <button class="swipe-edit-btn" type="button">Editar</button>
                                <div class="swipe-content">
                                      <div class="tx-line d-flex justify-content-between">
                                           <div class="d-flex align-items-center">
        <i class="${iconCls} text-white" style="font-size:12px;background:${bg};padding:7.5px;border-radius:50%;"></i>
                                                <div class="title-date mx-3">
                                                    <span class="tx-title">${it.title ?? 'Sem título'}</span>
                                                    <small class="tx-date">${date}</small>
                                                </div>
                                           </div>
                                           <span class="tx-amount price-default">${inst}${amount} ${proj}</span>
                                      </div>
                                </div>
                                <button class="swipe-delete-btn" type="button">Excluir</button>
                          </li>`;
            }

            function paintList(list) {
                itemsBox.innerHTML = list.map(renderItem).join('') || '<div class="p-3 text-muted">Sem lançamentos neste mês.</div>';
            }

            // ====== Swipe handlers (mesma base)
            let swipe = {active: null, startX: 0, dragging: false};

            function closeAll() {
                document.querySelectorAll('.swipe-item.open-left,.swipe-item.open-right').forEach(li => li.classList.remove('open-left', 'open-right'));
            }

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

            itemsBox.addEventListener('touchstart', onStart, {passive: true});
            itemsBox.addEventListener('mousedown', onStart);
            window.addEventListener('touchmove', onMove, {passive: false});
            window.addEventListener('mousemove', onMove);
            window.addEventListener('touchend', onEnd);
            window.addEventListener('mouseup', onEnd);
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.swipe-item')) closeAll();
            });

            // ====== Clicks Edit/Delete + abrir (show)
            let suppressShowUntil = 0;
            const suppressShow = (ms = 800) => {
                suppressShowUntil = Date.now() + ms;
            };

            itemsBox.addEventListener('touchstart', (e) => {
                if (document.body.classList.contains('modal-open')) return;
                const btn = e.target.closest('.swipe-edit-btn,.swipe-delete-btn');
                if (!btn) return;
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                suppressShow();
                const li = btn.closest('.swipe-item');
                const id = li?.dataset.id;
                if (!id) return;
                if (btn.classList.contains('swipe-edit-btn')) handleEdit(id);
                else handleAskDelete(id);
            }, {capture: true, passive: false});

            itemsBox.addEventListener('pointerdown', (e) => {
                if (document.body.classList.contains('modal-open')) return;
                const btn = e.target.closest('.swipe-edit-btn,.swipe-delete-btn');
                if (!btn) return;
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                suppressShow();
                const li = btn.closest('.swipe-item');
                const id = li?.dataset.id;
                if (!id) return;
                if (btn.classList.contains('swipe-edit-btn')) handleEdit(id);
                else handleAskDelete(id);
            }, true);

            itemsBox.addEventListener('click', (e) => {
                if (document.body.classList.contains('modal-open')) return;
                if (e.target.closest('.swipe-edit-btn,.swipe-delete-btn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                }
            }, true);

            itemsBox.addEventListener('click', async (e) => {
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
                    const res = await fetch(`{{ url('/invoice-items') }}/${id}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) throw new Error('Erro ao carregar item');
                    const it = await res.json();
                    openModal('show', it);
                } catch (err) {
                    alert(err.message);
                }
            });

            async function handleEdit(id) {
                currentId = id;
                const res = await fetch(`{{ url('/invoice-items') }}/${id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) {
                    alert('Erro ao carregar');
                    return;
                }
                const it = await res.json();
                openModal('edit', it);
            }

            function handleAskDelete(id) {
                pendingDeleteId = id;
                openConfirm();
            }

            async function doDelete() {
                if (!pendingDeleteId) return;
                const res = await fetch(`{{ url('/invoice-items') }}/${pendingDeleteId}`, {
                    method: 'DELETE',
                    headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
                });
                if (!res.ok) {
                    alert('Erro ao excluir');
                    return;
                }
                itemsBox.querySelector(`.swipe-item[data-id="${pendingDeleteId}"]`)?.remove();
                pendingDeleteId = null;
            }

            form?.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (currentMode === 'edit') {
                    form.querySelectorAll('[disabled]').forEach(el => el.disabled = false);
                }
                const fd = new FormData(form);
                let url, method = 'POST';
                if (currentMode === 'edit' && currentId) {
                    url = `{{ url('/invoice-items') }}/${currentId}`;
                    fd.append('_method', 'PUT');
                } else {
                    url = `{{ route('invoice-items.store') }}`; // criar item avulso (se aplicável)
                }
                const res = await fetch(url, {
                    method,
                    headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                    body: fd
                });
                if (!res.ok) {
                    alert('Erro ao salvar');
                    return;
                }
                closeModal();
                // reload mês atual
                const activeBtn = months.querySelector('.month-btn.active');
                if (activeBtn) activeBtn.click();
            });

            // Fechar modal clicando fora
            function closeIfOutside(e) {
                if (!modalEl.classList.contains('show')) return;
                const r = form.getBoundingClientRect();
                const p = e.touches ? e.touches[0] : e;
                const x = p.clientX, y = p.clientY;
                const inside = x >= r.left && x <= r.right && y >= r.top && y <= r.bottom;
                if (!inside) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeModal();
                }
            }

            window.addEventListener('pointerdown', closeIfOutside, true);
            window.addEventListener('touchstart', closeIfOutside, {capture: true, passive: false});
            document.getElementById('closeModal')?.addEventListener('click', closeModal);

            months.addEventListener('click', async (e) => {
                const btn = e.target.closest('.month-btn');
                if (!btn) return;
                const ym = btn.dataset.ym;
                months.querySelectorAll('.month-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const url = `{{ url('/invoice') }}/${cardId}/${ym}`;
                const res = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
                if (!res.ok) return;
                const data = await res.json();

                hdrMonth.textContent = 'Fatura de ' + data.header.month_label;
                hdrTotal.textContent = data.header.total;
                hdrLimit.innerHTML = data.header.limit;
                hdrClose.innerHTML = data.header.close_label;
                hdrDue.innerHTML = data.header.due_label;

                paintList(data.items || []);
            });

            // ====== Boot inicial com os itens do blade (server-side) — transforma $items em swipe
            (function bootstrapFromServer() {
                const initial = [
                        @foreach($items as $it)
                    {
                        uuid: "{{ $it->uuid ?? '' }}",
                        id: "{{ $it->id ?? '' }}",
                        title: `{!! addslashes($it->title) !!}`,
                        date: "{{ $it->date }}",
                        amount: "{{ $it->amount }}",
                        installments: {{ (int)($it->installments ?? 0) }},
                        current_installment: {{ (int)($it->current_installment ?? 0) }},
                        is_projection: {{ $it->is_projection ? 'true':'false' }},
                        icon: "{{ $it->icon ?? '' }}",
                        color: "{{ $it->color ?? '#999' }}"
                    },
                    @endforeach
                ];
                paintList(initial);
            })();

        })();
    </script>
@endpush
