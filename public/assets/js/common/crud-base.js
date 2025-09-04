/* crud-base.js - feito sob medida para seus dois scripts (contas + categorias) */
;(() => {
    const $  = (s, ctx=document)=>ctx.querySelector(s);
    const $$ = (s, ctx=document)=>Array.from(ctx.querySelectorAll(s));
    const u  = (tpl, id)=> tpl.replace(':id', id);
    const ensureArray = (d)=> Array.isArray(d) ? d : (d?.data ?? (typeof d==='object' ? Object.values(d) : []));
    const moneyToNumber = (v)=>{
        if (v==null) return 0;
        if (typeof v==='number') return v;
        const s = String(v).trim().replace(/[^\d,.-]/g,'');
        if (s.includes(',') && s.includes('.')) return parseFloat(s.replace(/\./g,'').replace(',','.')) || 0;
        if (s.includes(',')) return parseFloat(s.replace(',','.')) || 0;
        return parseFloat(s) || 0;
    };
    const brl = (n)=> (isNaN(n) ? 'R$ 0,00' : Number(n).toLocaleString('pt-BR',{style:'currency',currency:'BRL'}));

    function gridOverlay(grid, on){ grid?.classList.toggle('grid-loading', !!on); }
    function renderSkeletons(grid, n, tplFn){
        grid.innerHTML = Array.from({length:n}).map(()=> tplFn()).join('');
    }

    // Validação inline (compatível com .field-error dos seus forms)
    function clearErrors(form){
        form?.querySelectorAll('.field-error').forEach(el=>{ el.textContent=''; el.classList.add('hidden'); });
        form?.querySelectorAll('.ring-red-500, .border-red-500, .ring-red-500\\/40').forEach(el=>{
            el.classList.remove('ring-2','ring-red-500','ring-red-500/40','border-red-500');
        });
    }
    function showErrors422(form, payload){
        const errs = payload?.errors || {};
        Object.entries(errs).forEach(([field, msgs])=>{
            const input = form.querySelector(`[name="${CSS.escape(field)}"]`);
            const holder = input ? (input.closest('label,div,fieldset')?.querySelector('.field-error')) : null;
            if (holder){ holder.textContent = msgs?.[0] || 'Campo inválido'; holder.classList.remove('hidden'); }
            input?.classList.add('ring-2','ring-red-500/40','border-red-500');
        });
    }

    // Modal util
    function openModal(modal){ modal?.classList.remove('hidden'); document.body.classList.add('overflow-hidden','ui-modal-open'); }
    function closeModal(modal){ modal?.classList.add('hidden'); document.body.classList.remove('overflow-hidden','ui-modal-open'); }

    // Painel de ações: tipo "menu" (flutuante) ou "sheet" (bottom sheet)
    const ActionPanel = {
        menu: {
            open(menuEl, anchorBtn){
                const r = anchorBtn.getBoundingClientRect();
                const top  = r.bottom + window.scrollY + 6;
                const left = Math.min(window.scrollX + r.left, window.scrollX + window.innerWidth - 220);
                menuEl.style.top = `${top}px`; menuEl.style.left = `${left}px`;
                menuEl.classList.remove('hidden');
            },
            close(menuEl){ menuEl?.classList.add('hidden'); }
        },
        sheet: {
            open(sheetEl){ sheetEl?.classList.remove('hidden'); document.body.classList.add('overflow-hidden','ui-sheet-open'); },
            close(sheetEl){ sheetEl?.classList.add('hidden'); document.body.classList.remove('overflow-hidden','ui-sheet-open'); }
        }
    };

    // --------- Fábrica CRUD ----------
    function createCRUD(opts){
        const {
            csrf,                                // string
            routes,                              // {index, store, show, update, destroy, ...extra}
            dom,                                 // {grid, skeletonTpl, cacheKey, modal: {...}, panel: {...}}
            mapping = {},                        // {idFrom(item), card(item, ctx), fillForm(item, form), normalizeForm(fd), modeTitle(m)}
            hooks = {}                           // {onListLoaded(ctx), onOpenCreate(), onOpenEdit(item), onOpenShow(item), onDelete(id), extras: {...}}
        } = opts;

        // ---- DOM refs
        const grid = $(dom.grid);
        if (!grid) { console.warn('CRUD Base: grid não encontrado:', dom.grid); }

        // Modal principal
        const modal = $(dom.modal?.el);
        const form  = $(dom.modal?.form);
        const idInp = dom.modal?.idInput ? $(dom.modal.idInput) : null;
        const title = dom.modal?.title ? $(dom.modal.title) : null;
        // botões modal
        $(dom.modal?.close)?.addEventListener('click', ()=> closeModal(modal));
        $(dom.modal?.cancel)?.addEventListener('click', ()=> closeModal(modal));
        $(dom.modal?.overlay)?.addEventListener('click', ()=> closeModal(modal));
        dom.modal?.openers && $$(dom.modal.openers).forEach(b=> b.addEventListener('click', (e)=>{ e.preventDefault(); openCreate(); }));

        // Painel de ações (opcional)
        let panelId = null; // id do card ativo no painel
        const panel = dom.panel; // {type:'menu'|'sheet', el:'#...', overlay?:'#...', menuClick? }
        const panelEl = panel?.el ? $(panel.el) : null;
        const panelOv = panel?.overlay ? $(panel.overlay) : null;

        if (panel && panel.type === 'sheet'){
            panelOv?.addEventListener('click', ()=> ActionPanel.sheet.close(panelEl));
            document.addEventListener('keydown', e=>{ if (e.key==='Escape' && !panelEl?.classList.contains('hidden')) ActionPanel.sheet.close(panelEl); });
        }
        if (panel && panel.type === 'menu'){
            window.addEventListener('scroll', ()=> ActionPanel.menu.close(panelEl), {passive:true});
            window.addEventListener('resize', ()=> ActionPanel.menu.close(panelEl), {passive:true});
            document.addEventListener('click', (e)=>{
                if (!panelEl || panelEl.classList.contains('hidden')) return;
                if (!e.target.closest(panel.el) && !e.target.closest('[data-action="more"]')) ActionPanel.menu.close(panelEl);
            });
        }

        // Cache
        function readCache(){ try{ return JSON.parse(localStorage.getItem(dom.cacheKey)||'null'); }catch{ return null; } }
        function writeCache(payload){ try{ localStorage.setItem(dom.cacheKey, JSON.stringify(payload)); }catch{} }

        // Estado
        let mode = 'create'; // create|edit|show
        let currentId = null;
        let suppressUntil = 0;

        function setMode(m){
            mode = m;
            const isShow = (m==='show');
            if (title) title.textContent = (mapping.modeTitle?.(m) ?? (m==='edit' ? 'Editar' : m==='show' ? 'Detalhes' : 'Novo'));
            form?.querySelectorAll('input,select,textarea,[type="radio"]').forEach(el=> el.disabled = isShow);
            const submit = form?.querySelector('button[type="submit"]');
            submit && submit.classList.toggle('hidden', isShow);
        }

        function openCreate(){
            setMode('create');
            clearErrors(form);
            form?.reset();
            if (idInp) idInp.value = '';
            hooks?.onOpenCreate && hooks.onOpenCreate();
            openModal(modal);
        }
        function openEdit(item){
            setMode('edit');
            clearErrors(form);
            form?.reset();
            mapping.fillForm?.(item, form);
            if (idInp && !idInp.value) idInp.value = mapping.idFrom?.(item) ?? item?.id ?? item?.uuid ?? '';
            hooks?.onOpenEdit && hooks.onOpenEdit(item);
            openModal(modal);
        }
        function openShow(item){
            setMode('show');
            clearErrors(form);
            form?.reset();
            mapping.fillForm?.(item, form);
            if (idInp && !idInp.value) idInp.value = mapping.idFrom?.(item) ?? item?.id ?? item?.uuid ?? '';
            hooks?.onOpenShow && hooks.onOpenShow(item);
            openModal(modal);
        }

        async function fetchJSON(url, init={}){
            const res = await fetch(url, init);
            let data = null; try{ data = await res.json(); }catch{}
            return { ok: res.ok, status: res.status, data };
        }

        // Prime (cache/skeleton)
        (function prime(){
            const cached = readCache();
            if (cached?.list?.length){
                grid.innerHTML = cached.list.map(item=> mapping.card(item, cached.ctx)).join('');
                gridOverlay(grid, true); // overlay shimmer enquanto atualiza
            } else {
                renderSkeletons(grid, dom.skeletonCount || 6, dom.skeletonTpl);
            }
        })();

        // Load
        async function loadList(){
            try{
                const main = await fetchJSON(routes.index, { headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} });
                if (!main.ok) throw new Error('Falha ao carregar');
                const list = ensureArray(main.data);

                // ctx extra (ex.: savingsMap das contas)
                let ctx = {};
                if (hooks?.buildContext) ctx = await hooks.buildContext(list);

                grid.innerHTML = list.length ? list.map(item=> mapping.card(item, ctx)).join('') : `<div class="text-sm text-neutral-500">Nenhum registro.</div>`;
                writeCache({ list, ctx, t: Date.now() });
                hooks?.onListLoaded && hooks.onListLoaded({ list, ctx });
            } catch(e){
                console.error(e);
            } finally {
                gridOverlay(grid, false);
            }
        }

        // Delegação no grid
        grid?.addEventListener('click', async (e)=>{
            const card = e.target.closest('article[data-id]');
            if (!card) return;
            const id = card.dataset.id;

            const more = e.target.closest('[data-action="more"]');
            if (more){
                e.preventDefault();
                suppressUntil = Date.now() + 400;
                panelId = id;
                if (panel?.type === 'menu')       ActionPanel.menu.open(panelEl, more);
                else if (panel?.type === 'sheet') ActionPanel.sheet.open(panelEl);
                return;
            }

            // Botões inline padronizados (edit/delete/statement/transfer)
            const btn = e.target.closest('[data-action]');
            if (btn){
                e.preventDefault();
                suppressUntil = Date.now() + 400;
                const act = btn.dataset.action;

                if (act === 'edit'){
                    const { ok, data } = await fetchJSON(u(routes.show, id), { headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} });
                    if (!ok) return alert('Erro ao carregar');
                    currentId = id; openEdit(data); return;
                }

                if (act === 'delete'){
                    if (!confirm('Excluir este registro?')) return;
                    await doDelete(id);
                    card.remove();
                    hooks?.onDelete && hooks.onDelete(id);
                    return;
                }

                // ganchos custom (ex.: statement, transfer)
                if (hooks?.onAction){
                    const handled = await hooks.onAction(act, { id, card, e });
                    if (handled) return;
                }
            }

            if (Date.now() < suppressUntil) return;

            // clique no card => show
            try{
                const { ok, data } = await fetchJSON(u(routes.show, id), { headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} });
                if (!ok) throw 0;
                currentId = id; openShow(data);
            } catch { alert('Erro ao carregar detalhes'); }
        });

        // Painel de ações → clique nas opções
        panelEl?.addEventListener('click', async (e)=>{
            const actBtn = e.target.closest('[data-sheet-action],[data-menu-action]');
            if (!actBtn || !panelId) return;
            const act = actBtn.dataset.sheetAction || actBtn.dataset.menuAction;

            // padrão
            if (act === 'edit' || act === 'show'){
                const { ok, data } = await fetchJSON(u(routes.show, panelId), { headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} });
                if (!ok) return alert('Erro ao carregar');
                (panel.type==='sheet') ? ActionPanel.sheet.close(panelEl) : ActionPanel.menu.close(panelEl);
                currentId = panelId; (act==='edit' ? openEdit(data) : openShow(data)); return;
            }
            if (act === 'delete'){
                (panel.type==='sheet') ? ActionPanel.sheet.close(panelEl) : ActionPanel.menu.close(panelEl);
                if (!confirm('Excluir este registro?')) return;
                await doDelete(panelId);
                // remove visual
                const el = [...grid.querySelectorAll('article[data-id]')].find(n=> n.dataset.id==panelId);
                el?.remove();
                hooks?.onDelete && hooks.onDelete(panelId);
                return;
            }

            // custom
            if (hooks?.onAction){
                (panel.type==='sheet') ? ActionPanel.sheet.close(panelEl) : ActionPanel.menu.close(panelEl);
                await hooks.onAction(act, { id: panelId, e });
            }
        });

        // Submit
        form?.addEventListener('submit', async (e)=>{
            e.preventDefault();
            clearErrors(form);
            const fd = new FormData(form);
            mapping.normalizeForm?.(fd);

            const id = idInp?.value?.trim();
            const isEdit = !!id;
            const url = isEdit ? u(routes.update, id) : routes.store;
            if (isEdit) fd.append('_method','PUT');

            try{
                gridOverlay(grid, true);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                });
                const data = await (async ()=>{ try{ return await res.json(); }catch{ return null; } })();

                if (!res.ok){
                    if (res.status===422 && data) { showErrors422(form, data); return; }
                    throw new Error(data?.message || 'Erro ao salvar');
                }

                closeModal(modal);
                if (idInp) idInp.value='';
                await loadList();
            } catch(err){
                alert(err.message || 'Falha ao salvar');
            } finally {
                gridOverlay(grid, false);
            }
        });

        // limpar erro ao digitar
        form?.addEventListener('input', (e)=>{
            const el = e.target.closest('input,select,textarea');
            if (!el) return;
            const wrapErr = el.closest('label,div,fieldset')?.querySelector('.field-error');
            wrapErr?.classList.add('hidden'); if (wrapErr) wrapErr.textContent='';
            el.classList.remove('ring-2','ring-red-500/40','border-red-500');
        });

        // Delete
        async function doDelete(rawId){
            const id = (rawId ?? '').toString().trim() || idInp?.value?.trim() || currentId;
            if (!id) throw new Error('ID inválido');
            const res = await fetch(u(routes.destroy, encodeURIComponent(id)), {
                method:'POST',
                headers:{ 'X-CSRF-TOKEN': csrf, 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' },
                body: (()=>{ const f=new FormData(); f.append('_method','DELETE'); f.append('id', id); return f; })()
            });
            if (!res.ok) throw new Error('Falha ao excluir');
        }

        // Boot
        window.addEventListener('DOMContentLoaded', ()=> loadList());

        return {
            reload: loadList,
            openCreate,
            utils: { moneyToNumber, brl }
        };
    }

    // Exponha global
    window.CrudBase = { create: createCRUD, moneyToNumber, brl };
})();
