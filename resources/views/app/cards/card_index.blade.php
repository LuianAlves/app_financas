@extends('layouts.templates.app')
@section('content')
    <x-card-header
        prevRoute="{{ route('dashboard') }}"
        iconRight="fa-solid fa-credit-card"
        title="Cartões de Crédito"
        description="Gerencie seus cartões e acompanhe seus limites e faturas."
    ></x-card-header>

    <button id="openModal" class="create-btn"><i class="fa fa-plus text-white"></i></button>

    <a href="{{route('transaction-view.index')}}" class="create-btn create-other" title="Transações">
        <i class="fas fa-retweet text-white"></i>
    </a>

    <x-modal
        modalId="modalCard"
        formId="formCard"
        pathForm="app.cards.card_form"
        :data="['accounts' => $accounts]"
    ></x-modal>

    {{-- Confirm delete --}}
    <div id="confirmDeleteCard" class="x-confirm" hidden>
        <div class="x-sheet" role="dialog" aria-modal="true" aria-labelledby="xConfirmTitleCard">
            <div class="x-head">
                <h5 id="xConfirmTitleCard">Remover cartão</h5>
                <button type="button" class="x-close" data-action="cancel" aria-label="Fechar">×</button>
            </div>
            <div class="x-body">Deseja remover este cartão?</div>
            <div class="x-actions">
                <button type="button" class="btn btn-light" data-action="cancel">Cancelar</button>
                <button type="button" class="btn btn-danger" data-action="confirm">Excluir</button>
            </div>
        </div>
    </div>

    {{-- Lista com swipe --}}
    <ul id="cardList" class="swipe-list mt-4"></ul>

    @push('styles')
        <style>
            .swipe-list{list-style:none;margin:0;padding:0}
            .swipe-item{position:relative;overflow:hidden;margin:10px 12px;border-radius:12px}
            .swipe-content{position:relative;z-index:2;transform:translateX(0);transition:transform 160ms ease; background: none !important; padding: 0 !important;}
            .swipe-edit-btn,.swipe-delete-btn{
                position:absolute;top:0;bottom:0;width:96px;border:0;color:#fff;font-weight:600;z-index:1
            }
            .swipe-edit-btn{left:0;background:#3498db}
            .swipe-delete-btn{right:0;background:#e74c3c}
            .swipe-item.open-left  .swipe-content{transform:translateX(-96px)}
            .swipe-item.open-right .swipe-content{transform:translateX(96px)}

            .balance-box {
                background-repeat:no-repeat;background-size:cover;background-position:center;
                height:200px;border-radius:12px;color:#fff;padding:16px;position:relative;
                box-shadow:1px 4px 8px rgba(0,0,0,0.2);
                margin-bottom: 0 !important;
            }
            .card-brand{position:absolute;top:16px;left:16px;width:50px}
            .card-chip{position:absolute;top:10px;right:16px;width:40px}
            .card-number,.detail-left,.detail-right{letter-spacing:5px;text-shadow:1px 1px 1px #000}
            .card-number{margin-top:60px;font-size:16px}
            .card-details{
                position:absolute;bottom:12px;left:16px;right:16px;display:flex;flex-direction:column;gap:4px;
                font-size:.8rem;text-shadow:1px 1px 1px #000
            }
            .detail-row{display:flex;justify-content:space-between;width:100%}
            .detail-left{font-size:12px;letter-spacing:2.5px}
            .detail-right{font-size:10px;letter-spacing:1px}

            .open-invoices{
                position:absolute;bottom:12px;right:16px;background:rgba(0,0,0,.35);
                color:#fff;padding:6px 10px;border-radius:8px;text-decoration:none;font-size:12px
            }

            #modalCard{ pointer-events:auto; }
            #formCard{ pointer-events:auto; }
        </style>
    @endpush

    @push('scripts')
        <script>
            (()=>{

                // ====== Consts / maps
                const OPEN_W=96, TH_OPEN=40, CSRF='{{ csrf_token() }}';
                const assetUrl = "{{ asset('assets/img') }}";
                const invoiceBase = "{{ url('/invoice/') }}";
                const brandMap = {1:'Visa',2:'Mastercard',3:'American Express',4:'Discover',5:'Diners Club',6:'JCB',7:'Elo'};

                // ====== Els
                const list    = document.getElementById('cardList');
                const modal   = document.getElementById('modalCard');
                const form    = document.getElementById('formCard');
                const openBtn = document.getElementById('openModal');
                const closeBtn= document.getElementById('closeModal');

                const xConfirm = document.getElementById('confirmDeleteCard');
                const xConfirmBtn = xConfirm.querySelector('[data-action="confirm"]');
                const xCancelBtns = xConfirm.querySelectorAll('[data-action="cancel"]');

                let currentMode='create', currentId=null, pendingDeleteId=null, suppressShowUntil=0;

                // ===== Utils
                function brl(v){ return parseFloat(v||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'}); }
                function brandNameFrom(card){ return card.brand_name || brandMap[card.brand] || 'Visa'; }
                function last4(n){ return '**** **** **** ' + String(n||'').padStart(4,'0'); }
                function suppressShow(ms=800){ suppressShowUntil=Date.now()+ms; }
                function $(sel){ return form?.querySelector(sel); }
                function setVal(id,val){ const el=$('#'+id); if(el) el.value=(val??''); }
                function setCheck(id,on){ const el=$('#'+id); if(el){ el.checked=!!on; el.dispatchEvent(new Event('change')); } }

                // ===== Modal controls
                function resetFormCard(){
                    if(!form) return;
                    form.reset();
                    form.querySelectorAll('input[type="checkbox"],input[type="radio"]').forEach(el=>el.checked=false);
                    form.querySelectorAll('input[type="file"]').forEach(el=>el.value='');
                }
                function openModal(mode, data){
                    currentMode=mode;
                    const isShow=(mode==='show');
                    form.querySelectorAll('input,select,textarea,button').forEach(el=>{
                        if(el.type==='submit') return;
                        el.disabled=isShow;
                    });
                    form.querySelector('button[type="submit"]')?.classList.toggle('d-none', isShow);

                    if(data){
                        // ajuste conforme seu form (ids prováveis)
                        setVal('cardholder_name', data.cardholder_name);
                        setVal('credit_limit', String(data.credit_limit ?? '').replace(/[^\d.-]/g,''));
                        setVal('closing_day', data.closing_day);
                        setVal('due_day', data.due_day);
                        setVal('last_four_digits', data.last_four_digits);
                        setVal('brand', data.brand);
                        setVal('account_id', data.account_id);
                        setVal('color_card', data.color_card);
                    }else{
                        resetFormCard();
                    }

                    modal.classList.add('show'); document.body.classList.add('modal-open');
                }
                function closeModal(){ modal.classList.remove('show'); document.body.classList.remove('modal-open'); }

                document.addEventListener('keydown', (e)=>{ if(e.key==='Escape' && modal.classList.contains('show')) closeModal(); });

                function closeIfOutside(e){
                    if(!modal.classList.contains('show')) return;
                    const r = form.getBoundingClientRect();
                    const p = e.touches ? e.touches[0] : e;
                    const inside = p.clientX>=r.left && p.clientX<=r.right && p.clientY>=r.top && p.clientY<=r.bottom;
                    if(!inside){ e.preventDefault(); e.stopPropagation(); closeModal(); }
                }
                window.addEventListener('pointerdown', closeIfOutside, true);
                window.addEventListener('touchstart', closeIfOutside, {capture:true, passive:false});

                openBtn.addEventListener('click', ()=>{ currentId=null; openModal('create', null); });
                closeBtn?.addEventListener('click', closeModal);

                // ===== Confirm
                function openConfirm(){ xConfirm.hidden=false; xConfirm.classList.add('show'); document.body.classList.add('modal-open'); }
                function closeConfirm(){ xConfirm.classList.remove('show'); xConfirm.hidden=true; document.body.classList.remove('modal-open'); }
                xConfirmBtn.addEventListener('click', async ()=>{ await doDelete(); closeConfirm(); });
                xCancelBtns.forEach(b=>b.addEventListener('click', closeConfirm));
                xConfirm.addEventListener('click', (e)=>{ if(e.target===xConfirm) closeConfirm(); });

                // ===== Render
                function renderCard(card){
                    const brand = brandNameFrom(card);
                    const href  = `${invoiceBase}/${card.id}`;
                    const color = card.color_card || '#2f4f4f';
                    const limit = card.credit_limit ? brl(card.credit_limit) : '—';

                    return `
          <li class="swipe-item" data-id="${card.id}">
            <button class="swipe-edit-btn" type="button">Editar</button>
            <div class="swipe-content">
              <div class="balance-box" style="background:${color}">
                <img src="${assetUrl}/credit_card/chip_card.png" class="card-chip" alt="Chip" />
                <img src="${assetUrl}/brands/${brand}.png" class="card-brand" alt="${brand}" />
                <div class="card-number">${last4(card.last_four_digits)}</div>
                <div class="card-details">
                  <div class="detail-row mb-3">
                    <div class="detail-left">${card.cardholder_name || ''}</div>
                    <div class="detail-right">${card.account ? card.account.bank_name : ''}</div>
                  </div>
                  <div class="detail-row flex-column" style="font-size:12px;letter-spacing:1px;">
                    <div>Fatura atual: ${card.invoice_total ? brl(card.invoice_total) : 'R$ 0,00'}</div>
                    <div>Limite cartão: ${card.credit_limit}</div>
                  </div>
                </div>
                <a class="open-invoices" href="${href}">Ver fatura</a>
              </div>
            </div>
            <button class="swipe-delete-btn" type="button">Excluir</button>
          </li>`;
                }
                function storeCard(card){ list.insertAdjacentHTML('beforeend', renderCard(card)); }
                function refreshList(cards){ list.innerHTML=''; cards.forEach(storeCard); }

                // ===== Swipe
                let swipe={active:null,startX:0,dragging:false};
                function closeAll(){ document.querySelectorAll('.swipe-item.open-left,.swipe-item.open-right').forEach(li=>li.classList.remove('open-left','open-right')); }
                function dragTranslate(item,px){
                    const content=item.querySelector('.swipe-content'); content.style.transition='none';
                    const clamp=Math.max(-OPEN_W, Math.min(OPEN_W, px)); content.style.transform=`translateX(${clamp}px)`;
                }
                function restoreTransition(item){ const c=item.querySelector('.swipe-content'); requestAnimationFrame(()=> c.style.transition='transform 160ms ease'); }
                function onStart(e){
                    if(document.body.classList.contains('modal-open')) return;
                    const li=e.target.closest('.swipe-item'); if(!li) return;
                    closeAll(); swipe.active=li; swipe.dragging=true;
                    swipe.startX=(e.touches?e.touches[0].clientX:e.clientX);
                    li.querySelector('.swipe-content').style.transition='none';
                }
                function onMove(e){
                    if(document.body.classList.contains('modal-open')) return;
                    if(!swipe.dragging || !swipe.active) return;
                    const x=(e.touches?e.touches[0].clientX:e.clientX);
                    const dx=x-swipe.startX; let base=0;
                    if(swipe.active.classList.contains('open-left')) base=-OPEN_W;
                    if(swipe.active.classList.contains('open-right')) base= OPEN_W;
                    const move=base+dx; if(move<0) dragTranslate(swipe.active, Math.max(move,-OPEN_W)); else dragTranslate(swipe.active, Math.min(move, OPEN_W));
                }
                function onEnd(){
                    if(document.body.classList.contains('modal-open')) return;
                    if(!swipe.dragging || !swipe.active) return;
                    const content=swipe.active.querySelector('.swipe-content'); restoreTransition(swipe.active);
                    const m=new WebKitCSSMatrix(getComputedStyle(content).transform);
                    const finalX=m.m41; swipe.active.classList.remove('open-left','open-right');
                    if(finalX<=-TH_OPEN) swipe.active.classList.add('open-left');
                    else if(finalX>=TH_OPEN) swipe.active.classList.add('open-right');
                    content.style.transform=''; swipe.dragging=false; swipe.active=null;
                }
                list.addEventListener('touchstart', onStart, {passive:true});
                list.addEventListener('mousedown', onStart);
                window.addEventListener('touchmove', onMove, {passive:false});
                window.addEventListener('mousemove', onMove);
                window.addEventListener('touchend', onEnd);
                window.addEventListener('mouseup', onEnd);
                document.addEventListener('click', (e)=>{ if(!e.target.closest('.swipe-item')) closeAll(); });

                // ===== Clicks: editar/excluir/visualizar
                list.addEventListener('touchstart', (e)=>{
                    if(document.body.classList.contains('modal-open')) return;
                    const btn=e.target.closest('.swipe-edit-btn,.swipe-delete-btn'); if(!btn) return;
                    e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); suppressShow();
                    const li=btn.closest('.swipe-item'); const id=li?.dataset.id; if(!id) return;
                    if(btn.classList.contains('swipe-edit-btn')) handleEdit(id); else handleAskDelete(id);
                }, {capture:true, passive:false});

                list.addEventListener('pointerdown', (e)=>{
                    if(document.body.classList.contains('modal-open')) return;
                    const btn=e.target.closest('.swipe-edit-btn,.swipe-delete-btn'); if(!btn) return;
                    e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); suppressShow();
                    const li=btn.closest('.swipe-item'); const id=li?.dataset.id; if(!id) return;
                    if(btn.classList.contains('swipe-edit-btn')) handleEdit(id); else handleAskDelete(id);
                }, true);

                list.addEventListener('click', (e)=>{
                    if(document.body.classList.contains('modal-open')) return;
                    if(e.target.closest('.swipe-edit-btn,.swipe-delete-btn')){ e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); }
                }, true);

                list.addEventListener('click', async (e)=>{
                    if(Date.now() < suppressShowUntil) return;
                    if(e.target.closest('.open-invoices')) return; // deixa navegar para faturas
                    const content = e.target.closest('.swipe-content'); if(!content) return;
                    const li=content.closest('.swipe-item'); if(!li) return;
                    if(li.classList.contains('open-left')||li.classList.contains('open-right')){ closeAll(); return; }
                    const id=li.dataset.id; currentId=id;
                    try{
                        const res=await fetch(`{{ url('/cards') }}/${id}`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
                        if(!res.ok) throw new Error('Erro ao carregar cartão');
                        const data=await res.json();
                        openModal('show', data);
                    }catch(err){ alert(err.message); }
                });

                // ===== CRUD
                async function handleEdit(id){
                    currentId=id;
                    const res=await fetch(`{{ url('/cards') }}/${id}`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
                    if(!res.ok){ alert('Erro ao carregar'); return; }
                    const data=await res.json();
                    openModal('edit', data);
                }
                function handleAskDelete(id){ pendingDeleteId=id; openConfirm(); }

                async function doDelete(){
                    if(!pendingDeleteId) return;
                    const res=await fetch(`{{ url('/cards') }}/${pendingDeleteId}`, {
                        method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
                    });
                    if(!res.ok){ alert('Erro ao excluir'); return; }
                    list.querySelector(`.swipe-item[data-id="${pendingDeleteId}"]`)?.remove();
                    pendingDeleteId=null;
                }

                form.addEventListener('submit', async (e)=>{
                    e.preventDefault();
                    if(currentMode==='edit'){ form.querySelectorAll('[disabled]').forEach(el=>el.disabled=false); }
                    const fd=new FormData(form);
                    let url, method='POST';
                    if(currentMode==='edit' && currentId){
                        url=`{{ url('/cards') }}/${currentId}`; fd.append('_method','PUT');
                    }else{
                        url=`{{ route('cards.store') }}`;
                    }
                    const res=await fetch(url,{method,headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:fd});
                    if(!res.ok){ alert('Erro ao salvar'); return; }
                    closeModal();
                    // reload lista
                    await loadCards(true);
                });

                // ===== Data load
                async function loadCards(clear=false){
                    try{
                        const response=await fetch(`{{ route('cards.index') }}`, {headers:{'Accept':'application/json'}});
                        if(!response.ok) throw new Error('Erro ao carregar cartões.');
                        const cards=await response.json();
                        if(clear) list.innerHTML='';
                        refreshList(cards);
                    }catch(err){ alert(err.message); }
                }
                window.addEventListener('DOMContentLoaded', ()=> loadCards());

            })();
        </script>
    @endpush
@endsection
