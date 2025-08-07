<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Financial App UI</title>

    <link rel="manifest" href="{{ asset('laravelpwa/manifest.json') }}">
    <meta name="theme-color" content="#00bfa6">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Flatpickr -->
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

    @stack('styles')

    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
</head>
<body>

<div class="app-container">
    <aside class="sidebar d-none d-sm-block">
        <div class="logo">Financeiro</div>
        <nav>
            <a href="#" class="active"><i class="fa fa-home"></i>Início</a>
            <a href="#"><i class="fa fa-university"></i>Bancos</a>
            <a href="#"><i class="fa fa-exchange-alt"></i>Transações</a>
            <a href="#"><i class="fa fa-credit-card"></i>Cartões</a>
            <a href="#"><i class="fa fa-chart-line"></i>Investimentos</a>
        </nav>
    </aside>

    <main class="content-area scroll-content">
        @yield('content-mobile')
    </main>
</div>

<!-- Flatpickr -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
<script>
    function formatDateISO(dateObj) {
        const y = dateObj.getFullYear();
        const m = String(dateObj.getMonth() + 1).padStart(2, '0');
        const d = String(dateObj.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    const eventosFake = {
        "2025-08-15": [
            {tipo: 'entrada', descricao: 'Pagamento Cliente A', valor: 1200.00},
            {tipo: 'entrada', descricao: 'Reembolso Empresa', valor: 350.00},
            {tipo: 'saida', descricao: 'Aluguel Escritório', valor: 800.00},
            {tipo: 'saida', descricao: 'Energia', valor: 240.00},
            {tipo: 'saida', descricao: 'Internet', valor: 100.00},
        ],
        "2025-08-20": [
            {tipo: 'entrada', descricao: 'PIX Recebido', valor: 400.00}
        ]
    };

    function exibirEventos(dataSelecionada) {
        const container = document.getElementById('calendar-results');
        container.innerHTML = `<h6>Lançamentos do dia ${dataSelecionada}</h6>`;
        const eventos = eventosFake[dataSelecionada] || [];
        if (eventos.length === 0) {
            container.innerHTML +=
                '<div class="transaction-card"><div class="transaction-info"><div class="icon"><i class="fa-solid fa-sack-dollar"></i></div><div class="details">Nenhum lançamento encontrado.</div></div></div>';
            return;
        }
        eventos.forEach(ev => {
            const cor = ev.tipo === 'entrada' ? 'text-success' : 'text-danger';
            const sinal = ev.tipo === 'entrada' ? '+' : '-';
            container.innerHTML += `
        <div class="d-flex justify-content-between border-bottom py-2">
          <div>${ev.descricao}</div>
          <div class="${cor}">${sinal} R$ ${ev.valor.toFixed(2)}</div>
        </div>`;
        });
    }

    flatpickr("#calendar", {
        locale: 'pt',
        inline: true,
        defaultDate: "today",
        disableMobile: true,
        onDayCreate: function (_, __, ___, dayElem) {
            const dateFormatted = formatDateISO(dayElem.dateObj);
            const eventos = eventosFake[dateFormatted];
            if (Array.isArray(eventos)) {
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
                if (eventos.some(ev => ev.tipo === 'saida')) {
                    const pr = document.createElement('span');
                    Object.assign(pr.style, { width:'6px', height:'6px', backgroundColor:'red', borderRadius:'50%' });
                    dotContainer.appendChild(pr);
                }
                if (dotContainer.childElementCount) dayElem.appendChild(dotContainer);
            }
        },
        onChange: function (sd) {
            setTimeout(() => exibirEventos(formatDateISO(sd[0])), 100);
        }
    });

    exibirEventos(formatDateISO(new Date()));
</script>

@stack('scripts')

<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker
            .register('{{ asset('laravelpwa/sw.js') }}')
            .then(() => console.log('SW registrado'))
            .catch(e => console.error('SW falhou', e));
    }
</script>
</body>
</html>
