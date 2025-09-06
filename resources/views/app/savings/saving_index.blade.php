@extends('layouts.templates.app')

@section('new-content')
    <x-card-header
        prevRoute="{{ route('dashboard') }}"
        iconRight="fa-solid fa-piggy-bank"
        title="Cofrinhos"
        description="Gerencie seus cofrinhos e acompanhe suas reservas.">
    </x-card-header>

    <!-- Lista (swipe) -->
    <div id="savingList" class="swipe-list mt-4"></div>

    <!-- Botão criar -->
    <button id="openModal" class="create-btn"><i class="fa fa-plus text-white"></i></button>

    <!-- Modal + Form -->
    <x-modal
        modalId="modalSaving"
        formId="formSaving"
        pathForm="app.savings.saving_form"
        :data="['accounts' => $accounts]">
    </x-modal>

    <!-- Confirm excluir -->
    <div id="confirmDeleteSaving" class="x-confirm" hidden>
        <div class="x-sheet" role="dialog" aria-modal="true" aria-labelledby="xConfirmTitleSaving">
            <div class="x-head">
                <h5 id="xConfirmTitleSaving">Remover cofrinho</h5>
                <button type="button" class="x-close" data-action="cancel" aria-label="Fechar">×</button>
            </div>
            <div class="x-body">Deseja remover este cofrinho?</div>
            <div class="x-actions">
                <button type="button" class="btn btn-light" data-action="cancel">Cancelar</button>
                <button type="button" class="btn btn-danger" data-action="confirm">Excluir</button>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            /* Swipe base */
            .swipe-list{list-style:none;margin:0;padding:0}
            .swipe-item{position:relative;overflow:hidden;background:#fff;border-bottom:1px solid #eee;touch-action:pan-y;user-select:none}
            .swipe-content{position:relative;z-index:2;background:#fff;padding:0 !important;transform:translateX(0);transition:transform 160ms ease;will-change:transform}
            .swipe-edit-btn,.swipe-delete-btn{position:absolute;top:0;bottom:0;width:96px;border:0;color:#fff;font-weight:600;z-index:5;pointer-events:none}
            .swipe-edit-btn{left:0;background:#3498db;transform:translateX(-100%)}
            .swipe-delete-btn{right:0;background:#dc3545;transform:translateX(100%)}
            .swipe-item.open-left  .swipe-content{transform:translateX(-96px)}
            .swipe-item.open-right .swipe-content{transform:translateX(96px)}
            .swipe-item.open-left  .swipe-delete-btn{transform:translateX(0); pointer-events:auto;}
            .swipe-item.open-right .swipe-edit-btn  {transform:translateX(0); pointer-events:auto;}

            /* Card visual (mantém seu layout) */
            .saving-card {background:#fff;border-radius:8px;padding:14px 16px;color:#fff;box-shadow:0 2px 6px rgba(0,0,0,.1);margin: 0 !important;transition:.2s}
            .saving-card:hover {transform:scale(1.01);box-shadow:0 4px 10px rgba(0,0,0,.15)}
            .saving-name{font-size:16px;font-weight:700;margin-bottom:6px;text-shadow:1px 1px 2px rgba(0,0,0,.2)}
            .saving-info{font-size:14px;text-shadow:1px 1px 1px rgba(0,0,0,.2)}

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
            #modalSaving{ pointer-events:auto; }
            #formSaving{ pointer-events:auto; }
        </style>
    @endpush

    <script>
        (() => {
            // ===== elementos
            const list    = document.getElementById('savingList');
            const modalEl = document.getElementById('modalSaving');
            const form    = document.getElementById('formSaving');
            const saveBtn = form.querySelector('button[type="submit"]');
            const openBtn = document.getElementById('openModal');
            const closeBtn= document.getElementById('closeModal'); // do x-modal

            const confirmEl = document.getElementById('confirmDeleteSaving');
            const confirmOk = confirmEl.querySelector('[data-action="confirm"]');
            const confirmCancel = confirmEl.querySelectorAll('[data-action="cancel"]');

            const CSRF='{{ csrf_token() }}';
            const ROUTES={
                index:   "{{ route('savings.index') }}",
                store:   "{{ route('savings.store') }}",
                show:    "{{ url('/savings') }}/:id",
                update:  "{{ url('/savings') }}/:id",
                destroy: "{{ url('/savings') }}/:id",
            };

            const OPEN_W=96, TH_OPEN=40;

            // ===== state
            let currentMode='create'; // create|edit|show
            let currentId=null;
            let swipe={active:null,startX:0,dragging:false};
            let pendingDeleteId=null;
            let suppressShowUntil=0;

            // ===== utils (mantém suas formatações)
            const u = (t,id)=> t.replace(':id', id);

            function brl(valor){
                const numero = Number(valor);
                return isNaN(numero) ? 'R$ 0,00' : numero.toLocaleString('pt-BR',{style:'currency',currency:'BRL'});
            }
            function pct(valor){
                const n = Number(valor);
                return isNaN(n) ? '0,00%' : n.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:4}) + '%';
            }
            function dateBR(iso){
                if (!iso) return '—';
                const d = new Date(iso);
                return isNaN(d) ? '—' : d.toLocaleDateString('pt-BR');
            }
            const upper = (s, fb='—') => (s ?? '').toString().trim() ? s.toString().toUpperCase() : fb;
            const setVal = (id, val)=>{ const el=form.querySelector('#'+id); if(el) el.value=(val??''); };
            const suppressShow = (ms=800)=> { suppressShowUntil = Date.now()+ms; };

            // ===== modal
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

            function fillForm(sv){
                setVal('name', sv.name);
                setVal('account_id', sv.account_id ?? sv.account?.id);
                setVal('current_amount', sv.current_amount);
                setVal('interest_rate', sv.interest_rate);
                setVal('rate_period', sv.rate_period);
                setVal('start_date', sv.start_date);
                setVal('notes', sv.notes ?? '');
                setVal('color_card', sv.color_card ?? '#00BFA6');
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
            closeBtn?.addEventListener('click', closeModal);

            // ===== confirm
            let confirmOpenedAt=0;
            function openConfirm(){ confirmOpenedAt=Date.now(); confirmEl.hidden=false; confirmEl.classList.add('show'); document.body.classList.add('modal-open'); }
            function closeConfirm(){ confirmEl.classList.remove('show'); confirmEl.hidden=true; document.body.classList.remove('modal-open'); }
            confirmOk.addEventListener('click', async ()=>{ try{ await doDelete(); }catch{ alert('Erro ao excluir'); } finally{ closeConfirm(); }});
            confirmCancel.forEach(b=> b.addEventListener('click', closeConfirm));
            confirmEl.addEventListener('click', (e)=>{ if(e.target===confirmEl){ if(Date.now()-confirmOpenedAt<150) return; closeConfirm(); } });

            // ===== render
            function renderSaving(sv){
                const id = sv.id ?? sv.uuid;
                const contaNome =
                    sv?.account?.alias
                        ? upper(sv.account.alias)
                        : (sv?.account?.bank_name ? upper(sv.account.bank_name) : 'CONTA NÃO DEFINIDA');
                const periodSuffix = sv.rate_period === 'yearly' ? 'a.a.' : (sv.rate_period ? 'a.m.' : '');
                const color = sv.color_card ?? '#00BFA6';

                return `
            <div class="swipe-item" data-id="${id}">
                <button class="swipe-edit-btn" type="button">Editar</button>
                <div class="swipe-content">
                    <div class="saving-card" style="background-color:${color}">
                        <div class="saving-name"><strong>${upper(sv.name,'COFRINHO')}</strong></div>
                        <div class="saving-info">Conta debitada: ${contaNome}</div>
                        <div class="saving-info">Valor aplicado: ${brl(sv.current_amount)}</div>
                        <div class="saving-info">Taxa: ${pct(sv.interest_rate)} ${periodSuffix}</div>
                        <div class="saving-info">Início: ${dateBR(sv.start_date)}</div>
                        ${sv.notes ? `<div class="saving-info">Obs.: ${sv.notes}</div>` : ''}
                    </div>
                </div>
                <button class="swipe-delete-btn" type="button">Excluir</button>
            </div>
            `;
            }
            function storeSaving(sv){ list.insertAdjacentHTML('beforeend', renderSaving(sv)); }

            async function loadSavings(){
                try{
                    const res = await fetch(ROUTES.index, { headers:{ 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }});
                    if(!res.ok) throw new Error('Erro ao carregar cofrinhos.');
                    const data = await res.json();
                    list.innerHTML='';
                    (Array.isArray(data)?data:Object.values(data||{})).forEach(storeSaving);
                }catch(err){ alert(err.message); }
            }

            // ===== submit (create/edit)
            form.addEventListener('submit', async (e)=>{
                e.preventDefault();
                if (currentMode==='edit') form.querySelectorAll('[disabled]').forEach(el=>el.disabled=false);

                const fd = new FormData(form);

                let url = ROUTES.store, method='POST';

                if (currentMode==='edit' && currentId){ url = u(ROUTES.update, currentId); fd.append('_method','PUT'); }

                try{
                    const res = await fetch(url, { method, headers:{ 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }, body: fd });
                    if(!res.ok) throw new Error('Erro ao salvar cofrinho.');
                    await res.json().catch(()=>{});
                    closeModal(); await loadSavings();
                }catch(err){ alert(err.message); }
            });

            // ===== swipe
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

            // ===== ações editar/excluir
            async function handleEdit(id){
                currentId = id;
                const res = await fetch(u(ROUTES.show, id), { headers:{ 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }});
                if(!res.ok){ alert('Erro ao carregar cofrinho.'); return; }
                const sv = await res.json();
                openModal('edit', sv);
            }
            function handleAskDelete(id){ pendingDeleteId = id; openConfirm(); }

            const actionHandler = (e)=>{
                if (document.body.classList.contains('modal-open')) return;
                const btn = e.target.closest('.swipe-edit-btn, .swipe-delete-btn');
                if (!btn) return;

                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                suppressShow(); // evita cair no SHOW após tocar no botão

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

            // ===== tap = show (visualizar desabilitado)
            list.addEventListener('click', async (e)=>{
                if (Date.now() < suppressShowUntil) return;
                const content = e.target.closest('.swipe-content'); if(!content) return;
                const li = content.closest('.swipe-item'); if(!li) return;
                if (li.classList.contains('open-left') || li.classList.contains('open-right')) { closeAll(); return; }

                const id = li.dataset.id; currentId=id;
                try{
                    const res = await fetch(u(ROUTES.show, id), { headers:{ 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }});
                    if(!res.ok) throw new Error('Erro ao abrir cofrinho.');
                    const sv = await res.json();
                    openModal('show', sv);
                }catch(err){ alert(err.message); }
            });

            // ===== delete
            async function doDelete(){
                if(!pendingDeleteId) return;
                const res = await fetch(u(ROUTES.destroy, pendingDeleteId), {
                    method:'DELETE',
                    headers:{ 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }
                });
                if(!res.ok){ alert('Erro ao excluir'); return; }
                list.querySelector(`.swipe-item[data-id="${pendingDeleteId}"]`)?.remove();
                pendingDeleteId=null;
            }

            // ===== start
            window.addEventListener('DOMContentLoaded', loadSavings);
        })();
    </script>
@endsection
