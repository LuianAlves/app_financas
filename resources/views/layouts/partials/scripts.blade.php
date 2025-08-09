<script>
    window.AUTH = @json(auth()->check());

    window.PUSH_CFG = {
        vapidKeyUrl: "{{ url('/vapid-public-key') }}",
        subscribeUrl: "{{ url('/push/subscribe') }}",
        swUrl: "{{ asset('sw.js') }}?v={{ filemtime(public_path('sw.js')) }}",
        loginPath: "{{ route('login') }}",
        isIOS: /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream
    };
</script>

@php
    $pushRegisterPath = public_path('assets/js/push-register.js');
@endphp
<script src="{{ asset('assets/js/push-register.js') }}?v={{ file_exists($pushRegisterPath) ? filemtime($pushRegisterPath) : time() }}" defer></script>


<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
<script>
    // ====== Dados vindos do Controller ======
    // Cada evento: { title, start, color, extendedProps: { amount, amount_brl, category_name, type } }
    const calendarEvents = @json($calendarEvents ?? []);

    // ====== Helpers ======
    function formatDateISO(dateObj) {
        const y = dateObj.getFullYear();
        const m = String(dateObj.getMonth() + 1).padStart(2, '0');
        const d = String(dateObj.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }
    function formatBRL(v) {
        if (typeof v === 'string' && v.startsWith('R$')) return v;
        const n = Number(v ?? 0);
        return n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }
    function formatDateBR(dateStr) {
        const [year, month, day] = String(dateStr).slice(0,10).split('-');
        return `${day}/${month}/${year}`;
    }
    function iconHtml(tipo){
        if (tipo === 'entrada')   return '<i class="fas fa-arrow-up text-success"></i>'; // verde para cima
        if (tipo === 'despesa')   return '<i class="fas fa-arrow-down text-danger"></i>'; // vermelha para baixo
        return '<i class="fas fa-chart-line text-primary"></i>'; // investimento/other
    }

    // ====== Converte eventos do controller para cache por dia ======
    // { 'YYYY-MM-DD': [{ tipo, descricao, valor, valor_brl }] }
    const eventosCache = {};
    (calendarEvents || []).forEach(ev => {
        const dia  = String(ev.start).slice(0, 10);
        const tipo = (ev.extendedProps?.type || '').toLowerCase().trim(); // 'entrada' | 'despesa' | 'investimento'
        const item = {
            tipo,
            descricao: ev.title ?? ev.extendedProps?.category_name ?? 'Sem descrição',
            valor: Number(ev.extendedProps?.amount ?? 0),
            valor_brl: ev.extendedProps?.amount_brl ?? formatBRL(ev.extendedProps?.amount ?? 0),
        };
        (eventosCache[dia] ??= []).push(item);
    });

    // ====== Render dos lançamentos do dia (com o MESMO card branco das transações) ======
    function exibirEventos(dataSelecionada) {
        const container = document.getElementById('calendar-results');
        const dataBR = formatDateBR(dataSelecionada);
        const eventos = eventosCache[dataSelecionada] ?? [];

        let html = `<h6>Lançamentos do dia ${dataBR}</h6>`;

        if (!eventos.length){
            html += `
        <div class="transaction-card">
          <div class="transaction-info">
            <div class="icon"><i class="fa-solid fa-sack-dollar"></i></div>
            <div class="details">Nenhum lançamento encontrado.</div>
          </div>
        </div>`;
            container.innerHTML = html;
            return;
        }

        eventos.forEach(ev => {
            const isExpense  = ev.tipo === 'despesa';
            const isIncome   = ev.tipo === 'entrada';
            const amountCls  = isExpense ? 'text-danger' : 'text-success';
            const sinal      = isExpense ? '-' : (isIncome ? '+' : '');
            const icone      = iconHtml(ev.tipo);

            html += `
        <div class="transaction-card">
          <div class="transaction-info">
            <div class="icon">
              ${icone}
            </div>
            <div class="details">
              ${ev.descricao}
              <br>
              <span>${dataBR}</span>
            </div>
          </div>
          <div class="transaction-amount ${amountCls}">
            ${sinal} ${ev.valor_brl}
          </div>
        </div>`;
        });

        container.innerHTML = html;
    }

    // ====== Flatpickr ======
    flatpickr("#calendar", {
        locale: 'pt',
        inline: true,
        defaultDate: "today",
        disableMobile: true,

        // Pontinhos por tipo: entrada (verde), despesa (vermelho), investimento (azul)
        onDayCreate: function (_, __, ___, dayElem) {
            const dateFormatted = formatDateISO(dayElem.dateObj);
            const eventos = eventosCache[dateFormatted];
            if (!Array.isArray(eventos) || eventos.length === 0) return;

            const dotContainer = document.createElement('div');
            dotContainer.style.display = 'flex';
            dotContainer.style.justifyContent = 'center';
            dotContainer.style.gap = '2px';
            dotContainer.style.marginTop = '-10px';

            if (eventos.some(ev => ev.tipo === 'entrada')) {
                const pv = document.createElement('span');
                Object.assign(pv.style, { width:'6px', height:'6px', backgroundColor:'green', borderRadius:'50%' });
                dotContainer.appendChild(pv);
            }
            if (eventos.some(ev => ev.tipo === 'despesa')) {
                const pr = document.createElement('span');
                Object.assign(pr.style, { width:'6px', height:'6px', backgroundColor:'red', borderRadius:'50%' });
                dotContainer.appendChild(pr);
            }
            if (eventos.some(ev => ev.tipo === 'investimento')) {
                const pi = document.createElement('span');
                Object.assign(pi.style, { width:'6px', height:'6px', backgroundColor:'#0ea5e9', borderRadius:'50%' });
                dotContainer.appendChild(pi);
            }
            if (dotContainer.childElementCount) dayElem.appendChild(dotContainer);
        },

        onChange: function (sd) {
            if (!sd || !sd[0]) return;
            exibirEventos(formatDateISO(sd[0]));
        },

        onReady: function (sd) {
            exibirEventos(formatDateISO(sd[0] || new Date()));
        }
    });

    // Fallback inicial
    exibirEventos(formatDateISO(new Date()));
</script>
