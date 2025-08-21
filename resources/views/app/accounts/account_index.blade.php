{{--@extends('layouts.templates.app')--}}
{{--@section('content')--}}
{{--    <x-card-header--}}
{{--        prevRoute="{{ route('dashboard') }}"--}}
{{--        iconRight="fa-solid fa-landmark"--}}
{{--        title="Contas Bancárias"--}}
{{--        description="Gerencie suas contas e saldos."--}}
{{--    ></x-card-header>--}}

{{--    <button id="openAccountModal" class="create-btn"><i class="fa fa-plus text-white"></i></button>--}}

{{--    <div id="confirmDeleteAccount" class="x-confirm" hidden>--}}
{{--        <div class="x-sheet" role="dialog" aria-modal="true" aria-labelledby="xConfirmTitleAccount">--}}
{{--            <div class="x-head">--}}
{{--                <h5 id="xConfirmTitleAccount">Remover conta</h5>--}}
{{--                <button type="button" class="x-close" data-action="cancel" aria-label="Fechar">×</button>--}}
{{--            </div>--}}
{{--            <div class="x-body">Deseja remover esta conta?</div>--}}
{{--            <div class="x-actions">--}}
{{--                <button type="button" class="btn btn-light" data-action="cancel">Cancelar</button>--}}
{{--                <button type="button" class="btn btn-danger" data-action="confirm">Excluir</button>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}

{{--    <!-- Lista -->--}}
{{--    <ul id="accountList" class="swipe-list mt-4"></ul>--}}

{{--    <x-modal--}}
{{--        modalId="modalAccount"--}}
{{--        formId="formAccount"--}}
{{--        pathForm="app.accounts.account_form"--}}
{{--        :data="[]"--}}
{{--    ></x-modal>--}}

{{--    @push('styles')--}}
{{--        <style>--}}
{{--            /* (opcional) estilinho do confirm custom */--}}
{{--            .x-confirm{position:fixed;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;z-index:1050}--}}
{{--            .x-confirm.show{display:flex}--}}
{{--            .x-sheet{background:#fff;border-radius:10px;min-width:320px;max-width:90vw;box-shadow:0 10px 30px rgba(0,0,0,.2)}--}}
{{--            .x-head{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border-bottom:1px solid #eee}--}}
{{--            .x-body{padding:14px}--}}
{{--            .x-actions{display:flex;gap:8px;justify-content:end;padding:10px 14px;border-top:1px solid #eee}--}}
{{--            .x-close{background:transparent;border:0;font-size:22px;line-height:1}--}}
{{--        </style>--}}
{{--    @endpush--}}

{{--    <script>--}}
{{--        window.DATA_CFG = {--}}
{{--            csrf: "{{ csrf_token() }}",--}}
{{--            selectors: {--}}
{{--                list:  "#accountList",--}}
{{--                form:  "#formAccount",--}}
{{--                modal: "#modalAccount",--}}
{{--                plus:  "#openAccountModal",--}}
{{--                confirmId: "confirmDeleteAccount"--}}
{{--            },--}}
{{--            routes: {--}}
{{--                index:   "{{ route('accounts.index') }}",--}}
{{--                show:    "{{ url('/accounts') }}/:id",--}}
{{--                store:   "{{ route('accounts.store') }}",--}}
{{--                update:  "{{ url('/accounts') }}/:id",--}}
{{--                destroy: "{{ url('/accounts') }}/:id"--}}
{{--            }--}}
{{--        };--}}
{{--    </script>--}}

{{--    <script type="module" src="{{ asset('assets/js/views/accounts.js') }}"></script>--}}
{{--@endsection--}}



@extends('layouts.templates.app')
@section('content')
    <x-card-header
        prevRoute="{{route('dashboard')}}"
        iconRight="fa-solid fa-circle-question"
        title="Contas Bancárias"
        description="Para uma melhor projeção, cadastre todas as suas contas bancárias atuais.">
    </x-card-header>

    <!-- Lista -->
    <div id="accountList" class="swipe-list mt-4"></div>

    <!-- Ações -->
    <button id="openModal" class="create-btn"><i class="fa fa-plus text-white"></i></button>
    <a href="{{route('transaction-view.index')}}" class="create-btn create-other" title="Transações">
        <i class="fas fa-retweet text-white"></i>
    </a>

    <!-- Modal + Form -->
    <x-modal modalId="modalAccount" formId="formAccount" pathForm="app.accounts.account_form"></x-modal>

    <!-- Confirm custom -->
    <div id="confirmDeleteAccount" class="x-confirm" hidden>
        <div class="x-sheet" role="dialog" aria-modal="true" aria-labelledby="xConfirmTitleAcc">
            <div class="x-head">
                <h5 id="xConfirmTitleAcc">Remover conta</h5>
                <button type="button" class="x-close" data-action="cancel" aria-label="Fechar">×</button>
            </div>
            <div class="x-body">Você deseja remover?</div>
            <div class="x-actions">
                <button type="button" class="btn btn-light" data-action="cancel">Cancelar</button>
                <button type="button" class="btn btn-danger" data-action="confirm">Excluir</button>
            </div>
        </div>
    </div>

    <style>
        .balance-box {
            padding: 20px !important;
            margin: 0 !important;
        }

        /* Swipe base */
        .swipe-list{list-style:none;margin:0;padding:0}
        .swipe-item{position:relative;overflow:hidden;background:#fff;border-bottom:1px solid #eee;touch-action:pan-y;user-select:none}
        .swipe-content{position:relative;z-index:2;background:#fff;padding:0;transform:translateX(0);transition:transform 160ms ease;will-change:transform}
        .swipe-delete-btn,.swipe-edit-btn{
            position:absolute;top:0;bottom:0;width:96px;border:0;color:#fff;font-weight:600;z-index:5;pointer-events:none
        }
        .swipe-edit-btn{left:0;background:#3498db;transform:translateX(-100%)}
        .swipe-delete-btn{right:0;background:#dc3545;transform:translateX(100%)}
        .swipe-item.open-left  .swipe-content{transform:translateX(-96px)}
        .swipe-item.open-right .swipe-content{transform:translateX(96px)}
        .swipe-item.open-left  .swipe-delete-btn{transform:translateX(0); pointer-events:auto;}
        .swipe-item.open-right .swipe-edit-btn  {transform:translateX(0); pointer-events:auto;}

        .balance-box {background:#fff;border-radius:12px;padding:16px;box-shadow:1px 4px 8px rgba(0,0,0,.08);margin:10px 12px}
        .tx-title{font-weight:700;font-size:16px;color:#333;display:block}
        .tx-date{letter-spacing:.5px;color:#9aa0a6;display:block}
        .tx-amount{font-weight:700;display:block}
        .tx-line{display:flex;justify-content:space-between;gap:12px}

        /* Confirm */
        .x-confirm{position:fixed;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;z-index:1050}
        .x-confirm[hidden]{display:none}
        .x-sheet{background:#fff;border-radius:10px;min-width:320px;max-width:90vw;box-shadow:0 10px 30px rgba(0,0,0,.2)}
        .x-head{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border-bottom:1px solid #eee}
        .x-body{padding:14px}
        .x-actions{display:flex;gap:8px;justify-content:end;padding:10px 14px;border-top:1px solid #eee}
        .x-close{background:transparent;border:0;font-size:22px;line-height:1}

        /* trava scroll quando modal/confirm aberto */
        .modal-open{overflow:hidden}
        #modalAccount{ pointer-events:auto; }
        #formAccount{ pointer-events:auto; }
    </style>

    <script>
        (() => {
            // ===== els/consts =====
            const list    = document.getElementById('accountList');
            const modalEl = document.getElementById('modalAccount');
            const form    = document.getElementById('formAccount');
            const saveBtn = form.querySelector('button[type="submit"]');
            const openBtn = document.getElementById('openModal');
            const closeBtn= document.getElementById('closeModal'); // do x-modal

            const confirmEl = document.getElementById('confirmDeleteAccount');
            const confirmOk = confirmEl.querySelector('[data-action="confirm"]');
            const confirmCancel = confirmEl.querySelectorAll('[data-action="cancel"]');

            const CSRF='{{ csrf_token() }}';
            const ROUTES={
                index:   "{{ route('accounts.index') }}",
                store:   "{{ route('accounts.store') }}",
                show:    "{{ url('/accounts') }}/:id",
                update:  "{{ url('/accounts') }}/:id",
                destroy: "{{ url('/accounts') }}/:id",
            };

            const OPEN_W=96, TH_OPEN=40;

            // ===== state =====
            let currentMode='create'; // create|edit|show
            let currentId=null;
            let swipe={active:null,startX:0,dragging:false};
            let pendingDeleteId=null;
            let suppressShowUntil=0;

            // ===== utils =====
            function u(t,id){ return t.replace(':id', id); }
            function brl(v){
                const n = typeof v==='number' ? v : parseFloat(String(v??'').replace(/[^\d.-]/g,''));
                return isNaN(n) ? 'R$ 0,00' : n.toLocaleString('pt-BR',{style:'currency',currency:'BRL'});
            }
            function setVal(id, val){ const el=form.querySelector('#'+id); if(el) el.value=(val??''); }
            function suppressShow(ms=800){ suppressShowUntil = Date.now()+ms; }

            // ===== modal =====
            let outsideHandler=null, touchBlocker=null, modalOpenedAt=0;

            function setFormMode(mode){
                currentMode = mode;
                const isShow = (mode==='show');
                form.querySelectorAll('input,select,textarea,button').forEach(el=>{
                    if (el.type==='submit') return;
                    el.disabled = isShow;
                });
                saveBtn.classList.toggle('d-none', isShow);
            }

            function fillForm(acc){
                setVal('bank_name', acc.bank_name);
                setVal('account_type', acc.account_type);
                setVal('agency', acc.agency);
                setVal('number', acc.number);
                setVal('opening_balance', acc.opening_balance);
                setVal('color', acc.color);
            }

            function openModal(mode, item){
                setFormMode(mode);
                if (item) fillForm(item); else form.reset();

                modalEl.classList.add('show');
                document.body.classList.add('modal-open');
                modalOpenedAt = Date.now();

                modalEl.addEventListener('click', modalClickCloser, true);

                // clicar fora fecha
                outsideHandler = (ev)=>{
                    if (!modalEl.classList.contains('show')) return;
                    if (Date.now()-modalOpenedAt < 120) return; // anti-bounce
                    const r = form.getBoundingClientRect();
                    const p = ev.touches ? ev.touches[0] : ev;
                    const inside = p.clientX>=r.left && p.clientX<=r.right && p.clientY>=r.top && p.clientY<=r.bottom;
                    if (!inside){ ev.preventDefault(); ev.stopPropagation(); closeModal(); }
                };
                window.addEventListener('pointerdown', outsideHandler, true);
                window.addEventListener('touchstart',  outsideHandler, {capture:true, passive:false});

                // bloquear scroll bleed
                touchBlocker = (e)=>{ if (modalEl.classList.contains('show')) e.preventDefault(); };
                window.addEventListener('touchmove', touchBlocker, {passive:false});
            }

            function modalClickCloser(e){
                if (e.target.matches('#closeModal, .btn-close, .x-close, [data-dismiss="modal"], [data-action="cancel"]')) {
                    e.preventDefault(); closeModal();
                }
            }

            function closeModal(){
                modalEl.classList.remove('show');
                document.body.classList.remove('modal-open');
                modalEl.removeEventListener('click', modalClickCloser, true);
                if(outsideHandler){
                    window.removeEventListener('pointerdown', outsideHandler, true);
                    window.removeEventListener('touchstart', outsideHandler, true);
                    outsideHandler=null;
                }
                if(touchBlocker){
                    window.removeEventListener('touchmove', touchBlocker, true);
                    touchBlocker=null;
                }
            }

            document.addEventListener('keydown', (e)=>{ if(e.key==='Escape' && modalEl.classList.contains('show')) closeModal(); });
            openBtn.addEventListener('click', ()=>{ currentId=null; openModal('create', null); });
            if (closeBtn) closeBtn.addEventListener('click', closeModal);

            // ===== confirm =====
            let confirmOpenedAt=0;
            function openConfirm(){ confirmOpenedAt=Date.now(); confirmEl.hidden=false; confirmEl.classList.add('show'); document.body.classList.add('modal-open'); }
            function closeConfirm(){ confirmEl.classList.remove('show'); confirmEl.hidden=true; document.body.classList.remove('modal-open'); }
            confirmOk.addEventListener('click', async ()=>{ try{ await doDelete(); }catch{ alert('Erro ao excluir'); } finally{ closeConfirm(); }});
            confirmCancel.forEach(b=> b.addEventListener('click', closeConfirm));
            confirmEl.addEventListener('click', (e)=>{ if(e.target===confirmEl){ if(Date.now()-confirmOpenedAt<150) return; closeConfirm(); } });

            // ===== render =====
            function renderAccount(acc){
                console.log(acc)

                const id = acc.id ?? acc.uuid;
                const date = acc.created_at ? String(acc.created_at).slice(0,10) : '';
                const label = acc.account_type || 'conta';
                const color = acc.color || '#666';

                return `
      <div class="swipe-item" data-id="${id}">
        <button class="swipe-edit-btn" type="button">Editar</button>
        <div class="swipe-content">
          <div class="balance-box">
            <div class="tx-line">
              <div class="d-flex justify-content-between flex-column">
                <span class="tx-title">${acc.bank_name ?? 'Sem título'}</span>
<!--                <small class="tx-date">Em ${date}</small>-->
              </div>
              <div class="text-end">
                <span class="tx-amount" style="color:${color}">${acc.total}</span><br>
<!--                <span class="badge" style="font-size:10px;background:${color};color:#fff">${label}</span>-->
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
              <small>
                <b class="text-muted">Na conta </b>
                <div class="d-flex align-items-center">
                  <span>${acc.current_balance}</span>
                </div>
              </small>
              <small>
                <b class="text-muted">Cofrinhos</b>
                <div class="d-flex align-items-center">
                  <span>${acc.saving_amount}</span>
                </div>
              </small>
            </div>

            <a href="#" class="text-color fw-bold" style="text-decoration:none;font-size:13px;">Ver Extrato</a>
          </div>
        </div>
        <button class="swipe-delete-btn" type="button">Excluir</button>
      </div>
    `;
            }
            function storeAccount(acc){ list.insertAdjacentHTML('beforeend', renderAccount(acc)); }

            async function loadAccounts(){
                try{
                    const res = await fetch(ROUTES.index, { headers:{ 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }});
                    if(!res.ok) throw new Error('Erro ao carregar contas.');
                    const data = await res.json();
                    list.innerHTML='';
                    (Array.isArray(data)?data:Object.values(data||{})).forEach(storeAccount);
                }catch(err){ alert(err.message); }
            }

            // ===== submit create/edit =====
            form.addEventListener('submit', async (e)=>{
                e.preventDefault();
                if (currentMode==='edit') form.querySelectorAll('[disabled]').forEach(el=>el.disabled=false);

                const fd = new FormData(form);
                const n = fd.get('opening_balance'); if (n!=null) fd.set('opening_balance', String(n).replace(',','.'));

                let url = ROUTES.store, method='POST';
                if (currentMode==='edit' && currentId){ url = u(ROUTES.update, currentId); fd.append('_method','PUT'); }

                try{
                    const res = await fetch(url, { method, headers:{ 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }, body: fd });
                    if(!res.ok) throw new Error('Erro ao salvar conta.');
                    await res.json().catch(()=>{});
                    closeModal(); await loadAccounts();
                }catch(err){ alert(err.message); }
            });

            // ===== swipe =====
            function closeAll(){ list.querySelectorAll('.swipe-item.open-left,.swipe-item.open-right').forEach(li=>li.classList.remove('open-left','open-right')); }
            function drag(li, px){ const c=li.querySelector('.swipe-content'); c.style.transition='none'; const clamp=Math.max(-OPEN_W,Math.min(OPEN_W,px)); c.style.transform=`translateX(${clamp}px)`; }
            function restore(li){ const c=li.querySelector('.swipe-content'); requestAnimationFrame(()=> c.style.transition='transform 160ms ease'); }

            function onStart(e){
                if (document.body.classList.contains('modal-open')) return;
                const li = e.target.closest('.swipe-item'); if(!li) return;
                closeAll(); swipe.active=li; swipe.dragging=true; swipe.startX=(e.touches?e.touches[0].clientX:e.clientX);
                li.querySelector('.swipe-content').style.transition='none';
            }
            function onMove(e){
                if (document.body.classList.contains('modal-open')) return;
                if(!swipe.dragging || !swipe.active) return;
                const x=(e.touches?e.touches[0].clientX:e.clientX); const dx=x-swipe.startX; let base=0;
                if(swipe.active.classList.contains('open-left')) base=-OPEN_W;
                if(swipe.active.classList.contains('open-right')) base=OPEN_W;
                const move=base+dx; if(move<0) drag(swipe.active, Math.max(move,-OPEN_W)); else drag(swipe.active, Math.min(move,OPEN_W));
            }
            function onEnd(){
                if (document.body.classList.contains('modal-open')) return;
                if(!swipe.dragging || !swipe.active) return;
                const c=swipe.active.querySelector('.swipe-content'); restore(swipe.active);
                const m=new WebKitCSSMatrix(getComputedStyle(c).transform); const finalX=m.m41;
                swipe.active.classList.remove('open-left','open-right');
                if(finalX<=-TH_OPEN) swipe.active.classList.add('open-left'); else if(finalX>=TH_OPEN) swipe.active.classList.add('open-right');
                c.style.transform=''; swipe.dragging=false; swipe.active=null;
            }
            list.addEventListener('touchstart', onStart, {passive:true});
            list.addEventListener('mousedown', onStart);
            window.addEventListener('touchmove', onMove, {passive:false});
            window.addEventListener('mousemove', onMove);
            window.addEventListener('touchend', onEnd);
            window.addEventListener('mouseup', onEnd);
            document.addEventListener('click', (e)=>{ if(!e.target.closest('.swipe-item')) closeAll(); });

            // ===== editar/excluir (captura + supressor) =====
            async function handleEdit(id){
                currentId = id;
                const res = await fetch(u(ROUTES.show, id), { headers:{ 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }});
                if(!res.ok){ alert('Erro ao carregar conta.'); return; }
                const acc = await res.json();
                openModal('edit', acc);
            }
            function handleAskDelete(id){ pendingDeleteId = id; openConfirm(); }

            const actionHandler = (e)=>{
                if (document.body.classList.contains('modal-open')) return;
                const btn = e.target.closest('.swipe-edit-btn, .swipe-delete-btn');
                if (!btn) return;

                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                suppressShow(); // evita cair no SHOW depois

                const li = btn.closest('.swipe-item');
                const id = li?.dataset.id;
                if (!id) return;

                if (btn.classList.contains('swipe-edit-btn')) handleEdit(id);
                else handleAskDelete(id);
            };
            list.addEventListener('touchstart', actionHandler, {capture:true, passive:false});
            list.addEventListener('pointerdown', actionHandler, true);
            list.addEventListener('click', (e)=>{
                if (e.target.closest('.swipe-edit-btn, .swipe-delete-btn')) {
                    e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation();
                }
            }, true);

            // ===== tap = show (retorna cedo se suprimido) =====
            list.addEventListener('click', async (e)=>{
                if (Date.now() < suppressShowUntil) return;

                const content = e.target.closest('.swipe-content'); if(!content) return;
                const li = content.closest('.swipe-item'); if(!li) return;

                if (li.classList.contains('open-left') || li.classList.contains('open-right')) { closeAll(); return; }

                const id = li.dataset.id; currentId=id;
                try{
                    const res = await fetch(u(ROUTES.show, id), { headers:{ 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }});
                    if(!res.ok) throw new Error('Erro ao carregar conta.');
                    const acc = await res.json();
                    openModal('show', acc);
                }catch(err){ alert(err.message); }
            });

            // ===== delete =====
            async function doDelete(){
                if(!pendingDeleteId) return;
                const res = await fetch(u(ROUTES.destroy, pendingDeleteId), {
                    method:'DELETE', headers:{ 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }
                });
                if(!res.ok){ alert('Erro ao excluir'); return; }
                list.querySelector(`.swipe-item[data-id="${pendingDeleteId}"]`)?.remove();
                pendingDeleteId=null;
            }

            // ===== start =====
            window.addEventListener('DOMContentLoaded', loadAccounts);
        })();
    </script>

@endsection
