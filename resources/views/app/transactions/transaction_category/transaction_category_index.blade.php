@extends('layouts.templates.app')

@section('new-content')
    @push('styles')
        <style>
            /* reaproveite seu CSS skel/brand/icon se quiser... */
            .skel {
                position: relative;
                overflow: hidden;
                border-radius: .5rem;
                background: #e5e7eb
            }

            .dark .skel {
                background: #262626
            }

            .skel::after {
                content: "";
                position: absolute;
                inset: 0;
                transform: translateX(-100%);
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .55), transparent);
                animation: skel 1.1s infinite
            }

            @keyframes skel {
                100% {
                    transform: translateX(100%)
                }
            }

            .grid-loading {
                position: relative
            }

            .grid-loading::after {
                content: "";
                position: absolute;
                inset: 0;
                pointer-events: none;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .5), transparent);
                animation: skel 1.1s infinite;
                opacity: .35
            }

            .dark .grid-loading::after {
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .08), transparent);
                opacity: .6
            }
        </style>
    @endpush

    <section class="mt-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold">Categorias de transação</h2>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">Cadastre e gerencie suas categorias.</p>
            </div>
            <div class="hidden md:flex items-center gap-2">
                <button data-open-modal="tcat"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white shadow-soft">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Nova categoria
                </button>
            </div>
        </div>

        <div id="tcatGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>

        <x-fab id="tcatFab" target="tcat"/>

        {{-- Menu flutuante opcional --}}
        <div id="tcatMenu"
             class="hidden fixed z-[75] min-w-40 rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 shadow-soft p-1">
            <button data-menu-action="edit"
                    class="w-full text-left px-4 py-2 rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-800">Editar
            </button>
            <button data-menu-action="show"
                    class="w-full text-left px-4 py-2 rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-800">Ver
                detalhes
            </button>
            <button data-menu-action="delete"
                    class="w-full text-left px-4 py-2 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                Excluir
            </button>
        </div>

        {{-- Modal CRUD genérico --}}
        <x-modal id="tcatModal" titleCreate="Nova categoria" titleEdit="Editar categoria"
                 titleShow="Detalhes da categoria" submitLabel="Salvar">
            <input type="hidden" name="id"/>
            <input type="hidden" name="icon" value="fa-solid fa-tags"/>
            <input type="hidden" name="has_limit" value="0"/>

            <label class="block">
                <span class="text-xs text-neutral-500 dark:text-neutral-400">Categoria</span>
                <input name="name" type="text" placeholder="Salário, Aluguel ..."
                       class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"
                       required/>
                <p class="field-error mt-1 text-xs text-red-600 hidden"></p>
            </label>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label class="block">
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">Cor</span>
                    <div class="mt-1 flex items-center gap-3">
                        <input id="tcat_color" name="color" type="color" value="#3b82f6"
                               class="h-9 w-14 rounded-lg border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 p-1"/>
                        <input id="tcat_color_hex" type="text" placeholder="#3b82f6"
                               class="flex-1 rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"/>
                    </div>
                    <p class="field-error mt-1 text-xs text-red-600 hidden"></p>
                </label>

                <label class="block">
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">Tipo</span>
                    <div class="mt-1 inline-flex w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-neutral-50 dark:bg-neutral-800 p-1">
                        <input type="radio" name="type" value="entrada" id="ctEnt" class="peer hidden" checked>
                        <label for="ctEnt"
                               class="flex-1 text-center px-3 py-1.5 rounded-lg bg-white dark:bg-neutral-900 shadow-sm cursor-pointer peer-checked:font-medium">Entrada</label>
                        <input type="radio" name="type" value="despesa" id="ctDesp" class="peer hidden">
                        <label for="ctDesp"
                               class="flex-1 text-center px-3 py-1.5 rounded-lg cursor-pointer hover:bg-white/70 dark:hover:bg-neutral-900/70">Despesa</label>
                        <input type="radio" name="type" value="investimento" id="ctInv" class="peer hidden">
                        <label for="ctInv"
                               class="flex-1 text-center px-3 py-1.5 rounded-lg cursor-pointer hover:bg-white/70 dark:hover:bg-neutral-900/70">Investimento</label>
                    </div>
                    <p class="field-error mt-1 text-xs text-red-600 hidden"></p>
                </label>
            </div>

            <div id="tcat_limitWrap" class="rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 p-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">Esta categoria terá um limite?</p>
                        <p class="text-[11px] text-neutral-400">Disponível para Despesa/Investimento</p>
                    </div>
                    <label class="inline-flex items-center cursor-pointer">
                        <input id="tcat_limitSwitch" type="checkbox" class="peer hidden">
                        <span class="w-11 h-6 bg-neutral-200 rounded-full relative transition
              after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:size-5 after:bg-white after:rounded-full after:transition
              peer-checked:bg-brand-600 peer-checked:after:left-5"></span>
                    </label>
                </div>
                <div id="tcat_limitField" class="mt-3 hidden">
                    <label class="block">
                        <span class="text-xs text-neutral-500 dark:text-neutral-400">Limite mensal (R$)</span>
                        <input name="monthly_limit" inputmode="decimal" placeholder="0,00"
                               class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2"/>
                        <p class="field-error mt-1 text-xs text-red-600 hidden"></p>
                    </label>
                </div>
            </div>

            {{-- Ícone simples (sem picker pra encurtar; se quiser, reuse seu picker) --}}
            <label class="block">
                <span class="text-xs text-neutral-500 dark:text-neutral-400">Ícone (classe FA)</span>
                <input name="icon" value="fa-solid fa-tags"
                       class="mt-1 w-full rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white/90 dark:bg-neutral-900/70 px-3 py-2">
                <p class="field-error mt-1 text-xs text-red-600 hidden"></p>
            </label>
        </x-modal>
    </section>

    @push('scripts')
        <script src="{{ asset('assets/js/common/crud-model.js') }}"></script>
        <script>
            // Helpers locais
            const moneyToNumber = (v) => {
                if (v == null) return 0;
                if (typeof v === 'number') return v;
                const s = String(v).trim().replace(/[^\d,.-]/g, '');
                if (s.includes(',') && s.includes('.')) return parseFloat(s.replace(/\./g, '').replace(',', '.')) || 0;
                if (s.includes(',')) return parseFloat(s.replace(',', '.')) || 0;
                return parseFloat(s) || 0;
            };
            const brl = (n) => (isNaN(n) ? 'R$ 0,00' : Number(n).toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }));
            const toHexOrDefault = (v) => /^#[0-9a-fA-F]{6}$/.test(String(v || '').trim()) ? String(v).trim().toLowerCase() : '#3b82f6';

            function typeBadgeLabel(t) {
                t = String(t || '').toLowerCase();
                if (t === 'entrada' || t === '1') return 'Entrada';
                if (t === 'investimento' || t === '3') return 'Investimento';
                return 'Despesa';
            }

            function cardTemplate(cat) {
                const id = cat.id ?? cat.uuid ?? cat.category_id;
                const name = cat.name ?? 'Sem nome';
                const colorHex = toHexOrDefault(cat.color);
                const typeLabel = typeBadgeLabel(cat.type);
                const limitNum = moneyToNumber(cat.monthly_limit);
                const limitTxt = limitNum ? (typeof cat.monthly_limit === 'string' ? cat.monthly_limit : brl(limitNum)) : '—';
                let colorType, bgType;
                if (typeLabel === 'Entrada') {
                    colorType = '#00d679';
                    bgType = '#00d6791a';
                } else if (typeLabel === 'Despesa') {
                    colorType = '#e46c6c';
                    bgType = '#e46c6c1a';
                } else {
                    colorType = '#d6c400';
                    bgType = '#d6c4001a';
                }
                const ic = (cat.icon || 'fa-solid fa-tags');

                return `
<article data-id="${id}" class="rounded-2xl border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-900 p-5 shadow-soft">
  <div class="flex items-start justify-between gap-3">
    <div class="flex items-center gap-3">
      <span class="size-12 grid place-items-center rounded-xl bg-neutral-100 dark:bg-neutral-800">
        <i class="${ic} fa-fw" style="color:${colorHex};"></i>
      </span>
      <div>
        <p class="font-semibold">${name}</p>
        <p class="text-xs text-neutral-500 dark:text-neutral-400">${typeLabel}</p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <span class="inline-flex items-center h-8 px-2 rounded-lg text-[11px] font-medium" style="color:${colorType};border:1px solid ${colorType};background:${bgType}">${typeLabel}</span>
      <button data-action="more" class="inline-grid size-10 place-items-center rounded-lg border border-neutral-200/70 dark:border-neutral-800/70 hover:bg-neutral-50 dark:hover:bg-neutral-800" aria-label="Mais ações">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
      </button>
    </div>
  </div>
  <div class="mt-4 grid grid-cols-2 gap-3">
    <div class="rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 p-3">
      <p class="text-xs text-neutral-500 dark:text-neutral-400">Limite mensal</p>
      <p class="text-lg font-medium">${limitNum ? limitTxt : '—'}</p>
    </div>
    <div class="rounded-xl border border-neutral-200/70 dark:border-neutral-800/70 p-3">
      <p class="text-xs text-neutral-500 dark:text-neutral-400">Cor</p>
      <div class="mt-1 flex items-center gap-2">
        <span class="inline-block size-4 rounded" style="background:${colorHex};border:1px solid #00000014;"></span>
        <span class="text-sm">${colorHex}</span>
      </div>
    </div>
  </div>
</article>`;
            }

            // Liga o CRUD
            const catCrud = CrudLite({
                key: 'tcat',
                csrf: '{{ csrf_token() }}',
                routes: {
                    index: "{{ route('transaction-categories.index') }}",
                    store: "{{ route('transaction-categories.store') }}",
                    show: "{{ url('/transaction-categories') }}/:id",
                    update: "{{ url('/transaction-categories') }}/:id",
                    destroy: "{{ url('/transaction-categories') }}/:id"
                },
                selectors: {
                    grid: '#tcatGrid',
                    modal: '#tcatModal',
                    form: '#tcatModal form',
                    title: '#tcatModal [data-crud-title]',
                    overlay: '#tcatModal [data-crud-overlay]',
                    openers: '[data-open-modal="tcat"]',
                    btnClose: '#tcatModal [data-crud-close]',
                    btnCancel: '#tcatModal [data-crud-cancel]',
                    fab: '#tcatFab',
                    menu: '#tcatMenu',
                    formError: '#tcatModal [data-form-error]'
                },
                template: cardTemplate,
                onModeChange: (m, form, titleEl) => {
                    if (titleEl) titleEl.textContent = m === 'edit' ? 'Editar categoria' : (m === 'show' ? 'Detalhes da categoria' : 'Nova categoria');
                    form.querySelectorAll('input,[type="radio"]').forEach(el => el.disabled = (m === 'show'));
                    const submit = form.querySelector('button[type="submit"]');
                    if (submit) submit.classList.toggle('hidden', m === 'show');
                },
                fillForm: (form, cat) => {
                    const mapped = {
                        id: cat.id ?? cat.uuid ?? '',
                        name: cat.name ?? '',
                        type: (String(cat.type || '').toLowerCase().includes('entrada') ? 'entrada' : String(cat.type || '').toLowerCase().includes('invest') ? 'investimento' : 'despesa'),
                        color: toHexOrDefault(cat.color),
                        icon: cat.icon ?? 'fa-solid fa-tags',
                        has_limit: (!!moneyToNumber(cat.monthly_limit) && !String(cat.type).toLowerCase().includes('entrada')) ? '1' : '0',
                        monthly_limit: (typeof cat.monthly_limit === 'number') ? String(cat.monthly_limit).replace('.', ',') : (cat.monthly_limit ?? '')
                    };
                    // preenche
                    Object.entries(mapped).forEach(([name, val]) => {
                        const els = form.querySelectorAll(`[name="${name}"]`);
                        els.forEach(el => {
                            if (el.type === 'radio') el.checked = (String(el.value) === String(val));
                            else el.value = val ?? '';
                        });
                    });
                    // cor espelhada
                    const color = document.getElementById('tcat_color');
                    const colorHex = document.getElementById('tcat_color_hex');
                    if (color) color.value = mapped.color;
                    if (colorHex) colorHex.value = mapped.color;

                    // limite UI
                    refreshLimitUI();
                    if (mapped.has_limit === '1') document.getElementById('tcat_limitField')?.classList.remove('hidden');
                    document.getElementById('tcat_limitSwitch').checked = (mapped.has_limit === '1');
                },
                clearForm: (form) => {
                    form.reset();
                    form.querySelector('[name="id"]')?.setAttribute('value', '');
                    form.querySelectorAll('[name="type"]').forEach(r => r.checked = (r.value === 'entrada'));
                    const color = document.getElementById('tcat_color');
                    const colorHex = document.getElementById('tcat_color_hex');
                    if (color) color.value = '#3b82f6';
                    if (colorHex) colorHex.value = '#3b82f6';
                    form.querySelector('[name="icon"]').value = 'fa-solid fa-tags';
                    form.querySelector('[name="has_limit"]').value = '0';
                    document.getElementById('tcat_limitSwitch').checked = false;
                    document.getElementById('tcat_limitField').classList.add('hidden');
                },
                onBeforeSubmit: (fd) => {
                    // cor
                    const colorHex = document.getElementById('tcat_color_hex')?.value?.trim();
                    if (colorHex) fd.set('color', colorHex);
                    // limite
                    const hasLim = fd.get('has_limit') === '1';
                    const lim = fd.get('monthly_limit');
                    if (hasLim && lim != null) {
                        const cleaned = String(lim).replace(/[^\d,.,-]/g, '').replace(/\.(?=\d{3}(?:\D|$))/g, '').replace(',', '.');
                        fd.set('monthly_limit', cleaned);
                    } else {
                        fd.set('monthly_limit', '');
                    }
                    return fd;
                },
                confirmDelete: () => confirm('Excluir esta categoria?')
            });

            // UI: cor/limite
            (function wireUI() {
                const color = document.getElementById('tcat_color');
                const colorHex = document.getElementById('tcat_color_hex');
                color?.addEventListener('input', () => {
                    if (colorHex) colorHex.value = color.value;
                });
                colorHex?.addEventListener('input', () => {
                    if (/^#[0-9a-fA-F]{6}$/.test(colorHex.value)) color.value = colorHex.value;
                });

                const limitSwitch = document.getElementById('tcat_limitSwitch');
                const limitField = document.getElementById('tcat_limitField');
                const hasLimitInp = document.querySelector('#tcatModal [name="has_limit"]');
                limitSwitch?.addEventListener('change', () => {
                    if (hasLimitInp) hasLimitInp.value = limitSwitch.checked ? '1' : '0';
                    if (limitField) limitField.classList.toggle('hidden', !limitSwitch.checked);
                    if (!limitSwitch.checked) {
                        const ml = document.querySelector('#tcatModal [name="monthly_limit"]');
                        if (ml) ml.value = '';
                    }
                });
                document.querySelectorAll('#tcatModal input[name="type"]').forEach(r => {
                    r.addEventListener('change', refreshLimitUI);
                });
            })();

            function refreshLimitUI() {
                const limitSwitch = document.getElementById('tcat_limitSwitch');
                const limitField = document.getElementById('tcat_limitField');
                const hasLimitInp = document.querySelector('#tcatModal [name="has_limit"]');
                const checked = document.querySelector('#tcatModal input[name="type"]:checked')?.value || 'entrada';
                const allow = (checked === 'despesa' || checked === 'investimento');
                limitSwitch.disabled = !allow;
                if (!allow) {
                    limitSwitch.checked = false;
                    if (hasLimitInp) hasLimitInp.value = '0';
                    if (limitField) limitField.classList.add('hidden');
                    const ml = document.querySelector('#tcatModal [name="monthly_limit"]');
                    if (ml) ml.value = '';
                } else {
                    if (limitSwitch.checked && limitField) limitField.classList.remove('hidden');
                }
            }
        </script>
    @endpush
@endsection
