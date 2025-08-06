<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Financial App UI</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Flatpickr -->
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

    @stack('styles')

    <link href="{{asset('assets/css/style.css')}}" rel="stylesheet">
</head>
<body>

<div class="app-container">
    <div class="scroll-content">
        @yield('content-mobile')
    </div>
</div>

<!-- Flatpickr -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
<script>
    // Utilitário para formatar datas no padrão yyyy-mm-dd
    function formatDateISO(dateObj) {
        const y = dateObj.getFullYear();
        const m = String(dateObj.getMonth() + 1).padStart(2, '0');
        const d = String(dateObj.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    // Dados de lançamentos simulados
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

    // Exibe os lançamentos de um dia
    function exibirEventos(dataSelecionada) {
        const container = document.getElementById('calendar-results');
        container.innerHTML = `<h6>Lançamentos do dia ${dataSelecionada}</h6>`;

        const eventos = eventosFake[dataSelecionada] || [];

        if (eventos.length === 0) {
            container.innerHTML += '' +
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
        </div>
      `;
        });
    }

    // Inicializa o calendário
    flatpickr("#calendar", {
        locale: 'pt',
        inline: true,
        defaultDate: "today",
        disableMobile: true,

        onDayCreate: function (_, __, ___, dayElem) {
            const dateFormatted = formatDateISO(dayElem.dateObj);
            const eventos = eventosFake[dateFormatted];

            if (Array.isArray(eventos)) {
                let hasEntrada = eventos.some(ev => ev.tipo === 'entrada');
                let hasSaida = eventos.some(ev => ev.tipo === 'saida');

                if (hasEntrada || hasSaida) {
                    const dotContainer = document.createElement('div');
                    dotContainer.style.display = 'flex';
                    dotContainer.style.justifyContent = 'center';
                    dotContainer.style.gap = '2px';
                    dotContainer.style.marginTop = '-10px';

                    if (hasEntrada) {
                        const pontoVerde = document.createElement('span');
                        pontoVerde.style.width = '6px';
                        pontoVerde.style.height = '6px';
                        pontoVerde.style.backgroundColor = 'green';
                        pontoVerde.style.borderRadius = '50%';
                        dotContainer.appendChild(pontoVerde);
                    }

                    if (hasSaida) {
                        const pontoVermelho = document.createElement('span');
                        pontoVermelho.style.width = '6px';
                        pontoVermelho.style.height = '6px';
                        pontoVermelho.style.backgroundColor = 'red';
                        pontoVermelho.style.borderRadius = '50%';
                        dotContainer.appendChild(pontoVermelho);
                    }

                    dayElem.appendChild(dotContainer);
                }
            }
        },

        onChange: function (selectedDates) {
            const dataSelecionada = formatDateISO(selectedDates[0]);
            setTimeout(() => exibirEventos(dataSelecionada), 100);
        }
    });

    // Exibe eventos de hoje ao carregar
    const hoje = formatDateISO(new Date());
    exibirEventos(hoje);
</script>

@stack('scripts')

</body>
</html>
