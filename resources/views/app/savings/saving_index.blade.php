@extends('layouts.templates.app')

@section('new-content')
    @push('styles')
        <style>
            /* Skeleton */
            .skel{position:relative;overflow:hidden;border-radius:.5rem;background:#e5e7eb}
            .dark .skel{background:#262626}
            .skel::after{content:"";position:absolute;inset:0;transform:translateX(-100%);background:linear-gradient(90deg,transparent,rgba(255,255,255,.55),transparent);animation:skel 1.1s infinite}
            @keyframes skel{100%{transform:translateX(100%)}}

            /* Overlay shimmer por cima de cards reais (quando há cache) */
            .grid-loading{position:relative}
            .grid-loading::after{content:"";position:absolute;inset:0;pointer-events:none;background:linear-gradient(90deg,transparent,rgba(255,255,255,.5),transparent);animation:skel 1.1s infinite;opacity:.35}
            .dark .grid-loading::after{background:linear-gradient(90deg,transparent,rgba(255,255,255,.08),transparent);opacity:.6}

            #savFab{z-index:80}
            body.ui-modal-open #savFab, body.ui-sheet-open #savFab{z-index:40;pointer-events:none}

            /* Efeito flutuante nos cards */
            .card-floating{transition:transform .2s ease, box-shadow .2s ease;cursor:pointer;}
            .card-floating:hover{transform:translateY(-4px);box-shadow:0 8px 20px rgba(0,0,0,.25)}
            .card-floating:active{transform:translateY(0);box-shadow:0 4px 10px rgba(0,0,0,.2)}
        </style>
    @endpush

    <section id="savings-page" class="mt-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold">Cofrinhos</h2>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">Gerencie seus cofrinhos e acompanhe as reservas.</p>
            </div>
            <div class="hidden md:flex items-center gap-2">
                <button data-open-modal="sav" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white shadow-soft">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Novo cofrinho
                </button>
            </div>
        </div>

        <!-- Lista -->
        <div id="savGrid" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4"></div>

        <!-- Modal Saving -->
        <div id="savModal" class="fixed inset-0 z-[60] hidden" role="dialog" aria-modal="true" aria-labelledby="savModalTitle">
            <div id="savOverlay" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
            <div class="absolute inset-x-0 bottom-0 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-[560px]">
                <div class="rounded-t-3xl md:rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 shadow-soft dark:shadow-softDark p-4 md:p-6 max-h-[92vh] overflow-y-auto">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 id="savModalTitle" class="text-lg font-semibold">Novo cofrinho</h3>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">Informe os dados do cofrinho.</p>
                        </div>
                        <button id="savClose" class="size-10 grid place-items-center rounded-xl hover:bg-neutral-100 dark:hover:bg-neutral-800" aria-label="Fechar">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div id="savFormErr" class="hidden mb-2 rounded-lg bg-red-50 text-red-700 text-sm px-3 py-2"></div>

                    <form id="savForm" class="mt-4 grid gap-3" novalidate>
                        <input type="hidden" id="sav_id" name="id"/>

                        <label class="block">
                            <span class="text-xs text-neutral-500 dark:text-neutral-400">Nome</span>
                            <input id="name" name="name" type="text" placeholder="Ex: Viagem" class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2" required/>
                        </label>

                        <label class="block">
                            <span class="text-xs text-neutral-500 dark:text-neutral-400">Conta debitada</span>
                            <select id="account_id" name="account_id" class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2">
                                <option value="">Nenhuma</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->bank_name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">Valor aplicado (R$)</span>
                                <input id="current_amount" name="current_amount" inputmode="decimal" placeholder="0,00" class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2" required/>
                            </label>
                            <label class="block">
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">Cor do cartão</span>
                                <input id="color_card" name="color_card" type="color" value="#00BFA6" class="mt-1 w-full h-10 rounded-lg border border-neutral-200/70 dark:border-neutral-800/70 cursor-pointer"/>
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">Taxa (%)</span>
                                <input id="interest_rate" name="interest_rate" inputmode="decimal" placeholder="ex: 1 (1%) ou 0,8 (0,8%)" class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"/>
                            </label>
                            <label class="block">
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">Período</span>
                                <div class="mt-1 inline-flex w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-neutral-50 dark:bg-neutral-800 p-1">
                                    <input type="radio" name="rate_period" value="monthly" id="rateMonthly" class="peer/rm hidden" checked>
                                    <label for="rateMonthly" class="flex-1 text-center px-3 py-1.5 rounded-lg bg-white dark:bg-neutral-900 shadow-sm cursor-pointer peer-checked/rm:font-medium">Mensal</label>

                                    <input type="radio" name="rate_period" value="yearly" id="rateYearly" class="peer/ry hidden">
                                    <label for="rateYearly" class="flex-1 text-center px-3 py-1.5 rounded-lg cursor-pointer hover:bg-white/70 dark:hover:bg-neutral-900/70">Anual</label>
                                </div>
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">Início</span>
                                <input id="start_date" name="start_date" type="date" class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"/>
                            </label>
                            <label class="block">
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">Observações</span>
                                <input id="notes" name="notes" type="text" placeholder="Opcional" class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"/>
                            </label>
                        </div>

                        <div class="mt-2 flex items-center justify-end gap-2">
                            <button type="button" id="savCancel" class="px-3 py-2 rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-800">Cancelar</button>
                            <button type="submit" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white shadow-soft">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Bottom Sheet -->
        <div id="savSheet" class="fixed inset-0 z-[70] hidden" aria-modal="true" role="dialog">
            <div id="savSheetOv" class="absolute inset-0 bg-black/40 backdrop-blur-[2px]"></div>
            <div class="absolute inset-x-0 bottom-0 rounded-t-2xl border border-neutral-200/60 dark:border-neutral-800/60 bg-white dark:bg-neutral-900 shadow-soft p-2">
                <div class="mx-auto h-1 w-10 rounded-full bg-neutral-300/70 dark:bg-neutral-700/70 mb-2"></div>
                <div class="grid gap-1 p-1">
                    <button data-sheet-action="edit" class="w-full text-left px-4 py-3 rounded-xl hover:bg-neutral-50 dark:hover:bg-neutral-800">Editar</button>
                    <button data-sheet-action="delete" class="w-full text-left px-4 py-3 rounded-xl text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">Excluir</button>
                </div>
            </div>
        </div>
    </section>

    <!-- FAB (mobile) -->
    <button id="savFab" type="button" data-open-modal="sav" class="md:hidden fixed bottom-20 right-4 z-[80] size-14 rounded-2xl grid place-items-center text-white shadow-lg bg-brand-600 hover:bg-brand-700 active:scale-95 transition" aria-label="Novo cofrinho">
        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
    </button>

    @push('scripts')
        <script src="{{ asset('assets/js/common/crud-model.js') }}"></script>
        <script>
            (()=>{

                /* ============== Rotas/DOM ============== */
                const CSRF='{{ csrf_token() }}';
                const ROUTES={
                    index:  "{{ route('savings.index') }}",
                    store:  "{{ route('savings.store') }}",
                    show:   "{{ url('/savings') }}/:id",
                    update: "{{ url('/savings') }}/:id",
                    destroy:"{{ url('/savings') }}/:id"
                };
                const u=(t,id)=>t.replace(':id', id);

                const grid   = document.getElementById('savGrid');
                const modal  = document.getElementById('savModal');
                const form   = document.getElementById('savForm');
                const overlay= document.getElementById('savOverlay');
                const savFab = document.getElementById('savFab');
                const btnClose=document.getElementById('savClose');
                const btnCancel=document.getElementById('savCancel');

                const sheet   = document.getElementById('savSheet');
                const sheetOv = document.getElementById('savSheetOv');

                /* ============== Cor via localStorage (sem BD) ============== */
                const COLOR_KEY='savingColors';
                function loadColorMap(){ try{ return JSON.parse(localStorage.getItem(COLOR_KEY)||'{}'); }catch{ return {}; } }
                let savingColorMap = loadColorMap();
                function getSavingColor(id, fallback='#00BFA6'){ return (id && savingColorMap[id]) ? savingColorMap[id] : fallback; }
                function setSavingColor(id, color){
                    if(!id||!color) return;
                    savingColorMap[id]=color;
                    localStorage.setItem(COLOR_KEY, JSON.stringify(savingColorMap));
                }

                /* ============== Utils ============== */
                const ensureArray=(d)=>Array.isArray(d)?d:(d?.data ?? (typeof d==='object'?Object.values(d):[]));
                const brl=(n)=> Number(n??0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});

                const moneyToNumber=(v)=>{
                    if (v==null) return 0;
                    if (typeof v==='number') return v;
                    const s = String(v).trim().replace(/[^\d,.,-]/g,'').replace(/\.(?=\d{3}(?:\D|$))/g,'').replace(',', '.' );
                    return parseFloat(s)||0;
                };
                const parseRate=(v)=>{ const n=moneyToNumber(v); return n>=1 ? n/100 : n; };
                const monthsBetween=(fromISO, to=new Date())=>{
                    if(!fromISO) return 0; const d=new Date(fromISO); if(isNaN(d)) return 0;
                    let m=(to.getFullYear()-d.getFullYear())*12 + (to.getMonth()-d.getMonth());
                    if (to.getDate()<d.getDate()) m -= 1; return Math.max(0,m);
                };
                const yearsBetween=(fromISO, to=new Date())=>{
                    if(!fromISO) return 0; const d=new Date(fromISO); if(isNaN(d)) return 0;
                    let y=to.getFullYear()-d.getFullYear();
                    const before=(to.getMonth()<d.getMonth())||(to.getMonth()===d.getMonth()&&to.getDate()<d.getDate());
                    if (before) y -= 1; return Math.max(0,y);
                };
                const dateBR=(iso)=>{
                    if(!iso) return '—';
                    const d=new Date(String(iso));
                    return isNaN(d) ? '—' : d.toLocaleDateString('pt-BR');
                };
                const compoundYield=(P,r,n)=> (P>0 && r>0 && n>0) ? (P*Math.pow(1+r,n)-P) : 0;

                function updateFabVisibility(has){
                    if(!savFab) return;
                    const isDesktop=window.matchMedia('(min-width:768px)').matches;
                    savFab.style.display=(isDesktop && has)?'none':'grid';
                }

                /* ============== Skeleton ============== */
                function cardSkeleton(){
                    return `
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
    <div class="h-16 rounded-xl skel"></div>
  </div>
</article>`}

                /* ============== Template do card ============== */
                function savingTemplate(sv){
                    /* >>> ajuste 1: se vier um ARRAY por engano, renderiza todos */
                    if (Array.isArray(sv)) return sv.map(savingTemplate).join('');

                    /* >>> ajuste 2: id estável com mais fallbacks */
                    const id = sv.id ?? sv.uuid ?? sv._id ?? sv.key;

                    const color = getSavingColor(id, sv.color_card || '#00BFA6'); // usa localStorage
                    const conta = sv?.account?.bank_name ? String(sv.account.bank_name).toUpperCase() : 'CONTA NÃO DEFINIDA';

                    const P = moneyToNumber(sv.current_amount);
                    const r = parseRate(sv.interest_rate);
                    const n = sv.rate_period==='monthly' ? monthsBetween(sv.start_date)
                        : sv.rate_period==='yearly'  ? yearsBetween(sv.start_date) : 0;

                    const rendimento = ('yield_amount' in sv) ? moneyToNumber(sv.yield_amount) : compoundYield(P, r, n);
                    const total      = ('total_amount'  in sv) ? moneyToNumber(sv.total_amount) : (P + rendimento);
                    const suffix = sv.rate_period==='yearly' ? 'a.a.' : (sv.rate_period==='monthly' ? 'a.m.' : '');

                    return `
<article data-id="${id}" class="card-floating rounded-2xl shadow-soft overflow-hidden">
  <div class="aspect-[16/10] rounded-2xl p-4 text-white flex flex-col justify-between shadow-inner relative" data-bg style="background:${color}">
    <div class="flex items-start justify-between">
      <div>
        <p class="font-medium">${String(sv.name ?? 'COFRINHO').toUpperCase()}</p>
        <p class="text-xs opacity-80">${conta}</p>
      </div>
      <button type="button" data-sheet-open class="inline-grid size-8 place-items-center rounded-lg hover:bg-black/20" aria-label="Mais ações">
        <svg class="size-4 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/>
        </svg>
      </button>
    </div>

    <div class="mt-1 text-center">
      <p class="text-2xl font-semibold">${brl(total)}</p>
    </div>

    <div class="mt-2 grid grid-cols-2 gap-3">
      <div class="rounded-xl border border-white/25 p-2">
        <p class="text-[11px] opacity-80">Aplicado</p>
        <p class="text-sm font-medium">${brl(P)}</p>
      </div>
      <div class="rounded-xl border border-white/25 p-2">
        <p class="text-[11px] opacity-80">Rendimento ${suffix}${n ? ` • ${n} ${sv.rate_period==='monthly'?'mês(es)':'ano(s)'}`:''}</p>
        <p class="text-sm font-medium">${brl(rendimento)}</p>
      </div>
    </div>

    <p class="text-[11px] mt-2 opacity-90 flex items-center justify-between">
      <span class="whitespace-nowrap">
        Taxa:
        ${(parseRate(sv.interest_rate)*100 || 0).toLocaleString('pt-BR',{minimumFractionDigits:2, maximumFractionDigits:4})}% ${suffix}
      </span>
      <span class="whitespace-nowrap">Início: ${dateBR(sv.start_date || sv.created_at)}</span>
    </p>

    ${sv.notes ? `<p class="text-[11px] mt-1 opacity-90">Obs.: ${sv.notes}</p>` : ''}
  </div>
</article>`;
                }

                /* ============== Hooks CrudLite ============== */
                function onModeChange(mode, formEl, title){
                    const isShow=(mode==='show');
                    if(title) title.textContent = mode==='edit' ? 'Editar cofrinho' : (isShow ? 'Detalhes do cofrinho' : 'Novo cofrinho');
                    if(formEl){
                        formEl.querySelectorAll('input,select,textarea').forEach(el=> el.disabled = isShow);
                        const btn=formEl.querySelector('button[type="submit"]');
                        if(btn) btn.classList.toggle('hidden', isShow);
                    }
                }

                function fillFormSaving(formEl, sv){
                    if(!formEl||!sv) return;
                    const id = sv.id ?? sv.uuid ?? '';
                    (formEl.querySelector('#sav_id')||{}).value = id;
                    formEl.name.value = sv.name ?? '';
                    formEl.account_id.value = sv.account_id ?? sv.account?.id ?? '';
                    formEl.current_amount.value = typeof sv.current_amount==='number' ? String(sv.current_amount).replace('.', ',') : (sv.current_amount ?? '');
                    formEl.interest_rate.value  = (sv.interest_rate ?? '');
                    const m = formEl.querySelector('#rateMonthly'); const y = formEl.querySelector('#rateYearly');
                    if (sv.rate_period==='yearly'){ if(y) y.checked=true; } else { if(m) m.checked=true; }
                    formEl.start_date.value = (sv.start_date ?? '').slice(0,10);
                    formEl.notes.value = sv.notes ?? '';

                    const colorInput = formEl.querySelector('#color_card');
                    if (colorInput) colorInput.value = getSavingColor(id, sv.color_card || '#00BFA6');
                    colorInput?.addEventListener('input', ()=>{
                        const card = document.querySelector(`article[data-id="${CSS.escape(id)}"] [data-bg]`);
                        if (card) card.style.background = colorInput.value;
                    });
                }

                function beforeSubmit(fd){
                    const cm = fd.get('current_amount');
                    if(cm!=null){
                        const cleaned=String(cm).replace(/[^\d,.,-]/g,'').replace(/\.(?=\d{3}(?:\D|$))/g,'').replace(',', '.');
                        fd.set('current_amount', cleaned);
                    }
                    const ir = fd.get('interest_rate');
                    if(ir!=null){
                        const cleaned=String(ir).replace(/[^\d,.,-]/g,'').replace(/\.(?=\d{3}(?:\D|$))/g,'').replace(',', '.');
                        fd.set('interest_rate', cleaned);
                    }
                    const sd = fd.get('start_date');
                    if(sd!=null) fd.set('start_date', String(sd).slice(0,10));
                    fd.delete('color_card'); // não envia cor pro backend
                    return fd;
                }

                /* ============== CrudLite init ============== */
                const crud = CrudLite({
                    key: 'savings',
                    routes: {
                        index: ROUTES.index,
                        store: ROUTES.store,
                        show:  ROUTES.show,
                        update:ROUTES.update,
                        destroy:ROUTES.destroy
                    },
                    selectors: {
                        grid: '#savGrid',
                        modal: '#savModal',
                        form:  '#savForm',
                        title: '#savModalTitle',
                        overlay:'#savOverlay',
                        openers:'[data-open-modal="sav"]',
                        btnClose: '#savClose',
                        btnCancel:'#savCancel'
                    },
                    template: savingTemplate,
                    skeleton: cardSkeleton,
                    skeletonCount: 6,

                    /* >>> ajuste 3: parseIndex super robusto */
                    parseIndex: (json) => {
                        if (Array.isArray(json)) return json;
                        if (json && Array.isArray(json.data)) return json.data;
                        if (json && typeof json === 'object') return Object.values(json);
                        return [];
                    },

                    parseShow:  (json)=> (json && typeof json==='object' && 'data' in json) ? json.data : json,
                    onModeChange,
                    fillForm: fillFormSaving,
                    onBeforeSubmit: beforeSubmit,
                    confirmDelete: (id)=> confirm('Excluir este cofrinho?'),
                    onAction: (act, id)=>{}
                });

                /* ============== Pós-submit: persistir cor ============== */
                document.querySelectorAll('[data-open-modal="sav"]').forEach(b=>{
                    b.addEventListener('click', ()=>{
                        setTimeout(()=>{
                            const colorInput = form.querySelector('#color_card');
                            if (colorInput) colorInput.value = '#00BFA6';
                        }, 0);
                    });
                });

                form.addEventListener('submit', async (e)=>{
                    e.stopImmediatePropagation();
                    e.preventDefault();

                    const chosenColor = form.querySelector('#color_card')?.value || '#00BFA6';
                    const fd = new FormData(form);
                    const isEdit = !!(form.querySelector('#sav_id')?.value);
                    const idEdit = form.querySelector('#sav_id')?.value || null;

                    const norm = (name)=>{
                        const v = fd.get(name); if(v==null) return;
                        const cleaned=String(v).replace(/[^\d,.,-]/g,'').replace(/\.(?=\d{3}(?:\D|$))/g,'').replace(',', '.');
                        fd.set(name, cleaned);
                    };
                    norm('current_amount'); norm('interest_rate');
                    const sd = fd.get('start_date'); if(sd!=null) fd.set('start_date', String(sd).slice(0,10));
                    fd.delete('color_card');

                    let url = ROUTES.store, method='POST';
                    if (isEdit) { url = u(ROUTES.update, idEdit); fd.append('_method','PUT'); }

                    try{
                        const res = await fetch(url, {
                            method,
                            headers:{ 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' },
                            body: fd
                        });
                        if(!res.ok){
                            let data=null; try{ data=await res.json(); }catch{}
                            throw new Error(data?.message || 'Erro ao salvar cofrinho.');
                        }
                        const saved = await res.json().catch(()=>null);

                        if (isEdit) {
                            setSavingColor(idEdit, chosenColor);
                            const bg = document.querySelector(`article[data-id="${CSS.escape(idEdit)}"] [data-bg]`);
                            if (bg) bg.style.background = chosenColor;
                        } else {
                            const newId = saved?.id ?? saved?.uuid ?? saved?._id ?? saved?.key;
                            if (newId) setSavingColor(newId, chosenColor);
                        }

                        modal.classList.add('hidden');
                        document.body.classList.remove('overflow-hidden','ui-modal-open');
                        await crud.reload();
                    }catch(err){
                        const box = document.getElementById('savFormErr');
                        if (box){ box.textContent = err.message || 'Erro'; box.classList.remove('hidden'); }
                        else alert(err.message || 'Erro');
                    }
                }, {capture:true});

                /* ============== Bottom Sheet ============== */
                let sheetId=null;
                function openSheet(id){ sheetId=id; sheet.classList.remove('hidden'); document.body.classList.add('overflow-hidden','ui-sheet-open'); }
                function closeSheet(){ sheet.classList.add('hidden'); document.body.classList.remove('overflow-hidden','ui-sheet-open'); }
                sheetOv.addEventListener('click', closeSheet);
                document.addEventListener('keydown', e=>{ if(e.key==='Escape' && !sheet.classList.contains('hidden')) closeSheet(); });

                document.addEventListener('click', (e) => {
                    const btn = e.target.closest('[data-sheet-open]');
                    if (!btn) return;
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    e.stopPropagation();
                    const cardEl = btn.closest('article[data-id]');
                    const id = cardEl?.dataset.id;
                    if (id) openSheet(id);
                }, true);

                document.getElementById('savSheet').addEventListener('click', async (e)=>{
                    const b=e.target.closest('[data-sheet-action]');
                    if(!b||!sheetId) return;
                    const act=b.dataset.sheetAction;
                    if(act==='edit'){
                        closeSheet();
                        try{
                            const res=await fetch(u(ROUTES.show,sheetId),{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
                            if(!res.ok) throw 0; const rec=await res.json();
                            crud.openModal('edit', rec);
                        }catch{ alert('Erro ao carregar cofrinho'); }
                        return;
                    }
                    if(act==='delete'){
                        closeSheet();
                        if(!confirm('Excluir este cofrinho?')) return;
                        try{
                            const fd=new FormData(); fd.append('_method','DELETE'); fd.append('id', sheetId);
                            const res=await fetch(u(ROUTES.destroy, encodeURIComponent(sheetId)),{
                                method:'POST', headers:{'X-CSRF-TOKEN': CSRF,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}, body: fd
                            });
                            if(!res.ok) throw 0;
                            await crud.reload();
                        }catch{ alert('Erro ao excluir'); }
                        return;
                    }
                });

                /* ============== Modal básicos ============== */
                function closeModal(){ modal.classList.add('hidden'); document.body.classList.remove('overflow-hidden','ui-modal-open'); }
                document.querySelectorAll('[data-open-modal="sav"]').forEach(b=> b.addEventListener('click', ()=> crud.openModal('create')));
                btnClose?.addEventListener('click', closeModal);
                overlay?.addEventListener('click', closeModal);
                btnCancel?.addEventListener('click', closeModal);
                document.addEventListener('keydown', e=>{ if(e.key==='Escape' && !modal.classList.contains('hidden')) closeModal(); });

                /* ============== Boot ============== */
                window.addEventListener('DOMContentLoaded', ()=>{ updateFabVisibility(false); });
                window.addEventListener('resize', ()=>{ const has=!!document.querySelector('#savGrid article[data-id]'); updateFabVisibility(has); });
            })();
        </script>
    @endpush
@endsection
