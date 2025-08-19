// public/assets/js/views/accounts.js
import { createSwipeCrud } from '/assets/js/common/swipeCrud.js';

const CFG = window.ACCOUNTS_CFG;

// ---- helpers específicos da tela ----
const $modal = document.querySelector(CFG.selectors.modal);
const $form  = document.querySelector(CFG.selectors.form);

const $confirm = document.getElementById(CFG.selectors.confirmId);

function setVal(id, v){ const el=$form.querySelector('#'+id); if(el) el.value=(v??''); }
function brl(v){ const n=parseFloat(v||0); return isNaN(n)?'R$ 0,00': n.toLocaleString('pt-BR',{style:'currency',currency:'BRL'}); }

// Preenche o form no show/edit
function fillForm(acc){
    setVal('bank_name', acc.bank_name);
    setVal('account_type', acc.account_type);
    setVal('agency', acc.agency);
    setVal('number', acc.number);
    setVal('opening_balance', acc.opening_balance);
    setVal('color', acc.color);
}

// Renderiza 1 item da lista
function renderAccount(acc){
    const id = acc.id ?? acc.uuid;
    const date = acc.created_at ? String(acc.created_at).slice(0,10) : '';
    const amount = brl(acc.balance ?? acc.opening_balance ?? 0);
    const label = acc.account_type || 'conta';
    const color = acc.color || '#666';
    return `
    <li class="swipe-item" data-id="${id}">
      <button class="swipe-edit-btn" type="button">Editar</button>
      <div class="swipe-content">
        <div class="tx-line">
          <div class="d-flex justify-content-between flex-column">
            <span class="tx-title">${acc.bank_name ?? 'Sem título'}</span>
            <small class="tx-date">Em ${date}</small>
          </div>
          <div class="text-end">
            <span class="tx-amount" style="color:${color}">${amount}</span><br>
            <span class="badge" style="font-size:10px;background:${color};color:#fff">${label}</span>
          </div>
        </div>
      </div>
      <button class="swipe-delete-btn" type="button">Excluir</button>
    </li>
  `;
}

// Adapter do modal principal (x-modal)
let outsideHandler = null;
let touchBlocker   = null;

function openModal(mode, item){
    const isShow = (mode==='show');

    $form.querySelectorAll('input,select,textarea').forEach(el => el.disabled = isShow);
    const saveBtn = $form.querySelector('button[type="submit"]');
    if (saveBtn) saveBtn.classList.toggle('d-none', isShow);

    if (item) fillForm(item); else $form.reset();

    // abre
    $modal.classList.add('show');
    document.body.classList.add('modal-open');  // trava scroll (ver CSS abaixo)

    // fechar pelo X dentro do modal
    $modal.addEventListener('click', modalClickCloser, true);

    // fechar clicando fora
    outsideHandler = (ev)=>{
        if (!$modal.classList.contains('show')) return;
        const r = $form.getBoundingClientRect();
        const p = ev.touches ? ev.touches[0] : ev;
        const inside = p.clientX>=r.left && p.clientX<=r.right && p.clientY>=r.top && p.clientY<=r.bottom;
        if (!inside) { ev.preventDefault(); ev.stopPropagation(); closeModal(); }
    };
    window.addEventListener('pointerdown', outsideHandler, true);
    window.addEventListener('touchstart',  outsideHandler, {capture:true, passive:false});

    // bloquear “scroll bleed” no mobile
    touchBlocker = (e)=>{ if ($modal.classList.contains('show')) e.preventDefault(); };
    window.addEventListener('touchmove', touchBlocker, {passive:false});
}

function modalClickCloser(e){
    if (
        e.target.matches('#closeModal, .btn-close, .x-close, [data-dismiss="modal"], [data-action="cancel"]')
    ) {
        e.preventDefault();
        closeModal();
    }
}

function closeModal(){
    $modal.classList.remove('show');
    document.body.classList.remove('modal-open');
    $modal.removeEventListener('click', modalClickCloser, true);

    if (outsideHandler){
        window.removeEventListener('pointerdown', outsideHandler, true);
        window.removeEventListener('touchstart',  outsideHandler, true);
        outsideHandler = null;
    }
    if (touchBlocker){
        window.removeEventListener('touchmove', touchBlocker, true);
        touchBlocker = null;
    }
}

// ESC
document.addEventListener('keydown', (e)=>{
    if(e.key==='Escape' && $modal.classList.contains('show')) closeModal();
});

// Adapter do confirm custom
function makeXConfirm(el){
    const ok = el.querySelector('[data-action="confirm"]');
    const cancels = el.querySelectorAll('[data-action="cancel"]');
    let cb = null;
    function open(onConfirm){
        cb = onConfirm; el.hidden=false; el.classList.add('show'); document.body.classList.add('modal-open');
    }
    function close(){
        el.classList.remove('show'); el.hidden=true; document.body.classList.remove('modal-open'); cb=null;
    }
    ok.addEventListener('click', ()=>{ if(cb) cb(); });
    cancels.forEach(b=> b.addEventListener('click', close));
    el.addEventListener('click', (e)=>{ if(e.target===el) close(); });
    return { open, close };
}

// Instância do CRUD
const accountsCrud = createSwipeCrud({
    csrf: CFG.csrf,
    selectors: CFG.selectors,
    routes: CFG.routes,

    renderItem: renderAccount,

    openModal,
    closeModal,

    confirm: makeXConfirm($confirm),

    beforeSubmit: (fd, mode)=>{
        const n = fd.get('opening_balance');
        if (n!=null) fd.set('opening_balance', String(n).replace(',','.'));
    }
});

// Start
accountsCrud.load().catch(()=> alert('Erro ao carregar contas'));
