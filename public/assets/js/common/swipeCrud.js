// public/assets/js/common/swipeCrud.js
export function createSwipeCrud(config){
    const $L = q(config.selectors.list);
    const $F = q(config.selectors.form);
    const $M = q(config.selectors.modal);
    const $Plus = config.selectors.plus ? q(config.selectors.plus) : null;

    const OPEN_W=96, TH_OPEN=40;
    const state = { mode:'create', id:null, dragging:false, active:null, startX:0, suppressShowUntil:0, pendingDelete:null };

    function q(sel){ return (typeof sel==='string') ? document.querySelector(sel) : sel; }
    function u(pattern, id){ return pattern.replace(':id', id); }
    function suppress(ms=700){ state.suppressShowUntil = Date.now()+ms; }

    function clear(){ $L.innerHTML=''; }
    function render(item){ $L.insertAdjacentHTML('beforeend', config.renderItem(item)); }

    async function load(){
        const res = await fetch(config.routes.index, { headers:{ 'Accept':'application/json' }});
        if(!res.ok) throw new Error('load failed');
        const data = await res.json();
        clear(); (Array.isArray(data)?data:Object.values(data||{})).forEach(render);
    }

    async function openShow(id){
        state.mode='show'; state.id=id;
        const res = await fetch(u(config.routes.show,id), {headers:{'Accept':'application/json'}});
        if(!res.ok) throw new Error('show failed');
        config.openModal('show', await res.json());
    }
    async function openEdit(id){
        state.mode='edit'; state.id=id;
        const res = await fetch(u(config.routes.show,id), {headers:{'Accept':'application/json'}});
        if(!res.ok) throw new Error('edit load failed');
        config.openModal('edit', await res.json());
    }
    function openCreate(){ state.mode='create'; state.id=null; config.openModal('create', null); }

    async function doDelete(){
        const res = await fetch(u(config.routes.destroy, state.pendingDelete), {
            method:'DELETE',
            headers:{'X-CSRF-TOKEN':config.csrf,'Accept':'application/json'}
        });
        if(!res.ok) throw new Error('delete failed');
        $L.querySelector(`.swipe-item[data-id="${state.pendingDelete}"]`)?.remove();
        state.pendingDelete=null;
    }

    // submit
    if($F){
        $F.addEventListener('submit', async (e)=>{
            e.preventDefault();
            const fd = new FormData($F);
            if (config.beforeSubmit) config.beforeSubmit(fd, state.mode);

            let url = config.routes.store, method='POST';
            if (state.mode==='edit' && state.id){
                url = u(config.routes.update, state.id);
                fd.append('_method','PUT'); // laravel-friendly
            }
            const res = await fetch(url,{ method, headers:{'X-CSRF-TOKEN':config.csrf,'Accept':'application/json'}, body:fd });
            if(!res.ok){ alert('Erro ao salvar'); return; }
            await res.json().catch(()=>{});
            config.closeModal();
            await load();
        });
    }

    // swipe core
    function closeAll(){ $L.querySelectorAll('.swipe-item.open-left,.swipe-item.open-right').forEach(li=>li.classList.remove('open-left','open-right')); }
    function drag(li, px){
        const c=li.querySelector('.swipe-content'); c.style.transition='none';
        const clamp=Math.max(-OPEN_W, Math.min(OPEN_W, px)); c.style.transform=`translateX(${clamp}px)`;
    }
    function restore(li){ const c=li.querySelector('.swipe-content'); requestAnimationFrame(()=> c.style.transition='transform 160ms ease'); }

    function onStart(e){
        if (document.body.classList.contains('modal-open')) return;
        const li = e.target.closest('.swipe-item'); if(!li) return;
        closeAll(); state.active=li; state.dragging=true; state.startX=(e.touches?e.touches[0].clientX:e.clientX);
        li.querySelector('.swipe-content').style.transition='none';
    }
    function onMove(e){
        if (document.body.classList.contains('modal-open')) return;
        if(!state.dragging || !state.active) return;
        const x=(e.touches?e.touches[0].clientX:e.clientX), dx=x-state.startX;
        let base=0; if(state.active.classList.contains('open-left')) base=-OPEN_W; if(state.active.classList.contains('open-right')) base=OPEN_W;
        const move=base+dx; if(move<0) drag(state.active, Math.max(move,-OPEN_W)); else drag(state.active, Math.min(move,OPEN_W));
    }
    function onEnd(){
        if (document.body.classList.contains('modal-open')) return;
        if(!state.dragging || !state.active) return;
        const c=state.active.querySelector('.swipe-content'); restore(state.active);
        const m=new WebKitCSSMatrix(getComputedStyle(c).transform); const finalX=m.m41;
        state.active.classList.remove('open-left','open-right');
        if(finalX<=-TH_OPEN) state.active.classList.add('open-left');
        else if(finalX>=TH_OPEN) state.active.classList.add('open-right');
        c.style.transform=''; state.dragging=false; state.active=null;
    }

    $L.addEventListener('touchstart', onStart, {passive:true});
    $L.addEventListener('mousedown', onStart);
    window.addEventListener('touchmove', onMove, {passive:false});
    window.addEventListener('mousemove', onMove);
    window.addEventListener('touchend', onEnd);
    window.addEventListener('mouseup', onEnd);
    document.addEventListener('click', (e)=>{ if(!e.target.closest('.swipe-item')) closeAll(); });

    // botões editar/excluir (parar propagação)
    $L.addEventListener('pointerdown', (e)=>{
        const btn = e.target.closest('.swipe-edit-btn,.swipe-delete-btn'); if(!btn) return;
        e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); suppress();
        const li = btn.closest('.swipe-item'); const id = li?.dataset.id; if(!id) return;
        if (btn.classList.contains('swipe-edit-btn')) openEdit(id).catch(()=>alert('Erro ao carregar'));
        else { state.pendingDelete = id; config.confirm.open(async ()=>{ try{ await doDelete(); }catch{ alert('Erro ao excluir'); } finally{ config.confirm.close(); } }); }
    }, true);
    $L.addEventListener('click', (e)=>{
        if (e.target.closest('.swipe-edit-btn,.swipe-delete-btn')){ e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); }
    }, true);

    // tap = show
    $L.addEventListener('click', (e)=>{
        if (Date.now() < state.suppressShowUntil) return;
        const content = e.target.closest('.swipe-content'); if(!content) return;
        const li = content.closest('.swipe-item'); if(!li) return;
        if(li.classList.contains('open-left')||li.classList.contains('open-right')){ closeAll(); return; }
        openShow(li.dataset.id).catch(()=>alert('Erro ao carregar'));
    });

    if($Plus) $Plus.addEventListener('click', ()=> openCreate());

    return { load, reload:load, openCreate };
}
