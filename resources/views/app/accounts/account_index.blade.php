@extends('layouts.templates.mobile')
@section('content-mobile')
    <div class="d-flex justify-content-between mb-3">
        <a href="{{route('dashboard')}}"><i class="fas fa-xmark text-dark" style="font-size: 22px;"></i></a>
        <i class="fa-solid fa-circle-question text-color" style="font-size: 22px;"></i>
    </div>


    <div class="header">
        <h1 class="mb-1">Contas Bancárias</h1>
        <p class="p-0 m-0">Para uma melhor projeção, cadastre todas as suas contas bancárias atuais.</p>
    </div>

    {{--    <div class="balance-box">--}}
    {{--        <span>Nubank</span>--}}
    {{--        <strong>R$ 3.720,00</strong>--}}
    {{--        <div class="d-flex justify-content-between align-items-center mt-2 mb-3">--}}
    {{--            <small>--}}
    {{--                <b class="text-muted">Na conta </b>--}}
    {{--                <div class="d-flex align-items-center">--}}
    {{--                    <span>R$ 1.412,00 </span>--}}
    {{--                    <small class="text-success mx-2"> +17%</small>--}}
    {{--                </div>--}}
    {{--            </small>--}}
    {{--            <small>--}}
    {{--                <b class="text-muted">Cofrinhos</b>--}}
    {{--                <div class="d-flex align-items-center">--}}
    {{--                    <span>R$ 1.971,36 </span>--}}
    {{--                    <small class="text-danger mx-2"> +3%</small>--}}
    {{--                </div>--}}
    {{--            </small>--}}
    {{--        </div>--}}
    {{--        <a href="#" class="text-color fw-bold" style="text-decoration: none; font-size: 13px;">Ver Extrato</a>--}}
    {{--    </div>--}}

    {{--        <div class="balance-box">--}}
    {{--            <span>Nubank</span>--}}
    {{--            <strong>R$ 3.720,00</strong>--}}
    {{--            <div class="d-flex justify-content-between align-items-center mt-2 mb-3">--}}
    {{--                <small>--}}
    {{--                    <b class="text-muted">Na conta </b>--}}
    {{--                    <div class="d-flex align-items-center">--}}
    {{--                        <span>R$ 1.412,00 </span>--}}
    {{--                        <small class="text-success mx-2"> +17%</small>--}}
    {{--                    </div>--}}
    {{--                </small>--}}
    {{--                <small>--}}
    {{--                    <b class="text-muted">Cofrinhos</b>--}}
    {{--                    <div class="d-flex align-items-center">--}}
    {{--                        <span>R$ 1.971,36 </span>--}}
    {{--                        <small class="text-danger mx-2"> +3%</small>--}}
    {{--                    </div>--}}
    {{--                </small>--}}
    {{--            </div>--}}
    {{--            <a href="#" class="text-color fw-bold" style="text-decoration: none; font-size: 13px;">Ver Extrato</a>--}}
    {{--        </div>--}}

    {{--    <div class="balance-box">--}}
    {{--        <span>Nubank</span>--}}
    {{--        <strong>R$ 3.720,00</strong>--}}
    {{--        <div class="d-flex justify-content-between align-items-center mt-2 mb-3">--}}
    {{--            <small>--}}
    {{--                <b class="text-muted">Na conta </b>--}}
    {{--                <div class="d-flex align-items-center">--}}
    {{--                    <span>R$ 1.412,00 </span>--}}
    {{--                    <small class="text-success mx-2"> +17%</small>--}}
    {{--                </div>--}}
    {{--            </small>--}}
    {{--            <small>--}}
    {{--                <b class="text-muted">Cofrinhos</b>--}}
    {{--                <div class="d-flex align-items-center">--}}
    {{--                    <span>R$ 1.971,36 </span>--}}
    {{--                    <small class="text-danger mx-2"> +3%</small>--}}
    {{--                </div>--}}
    {{--            </small>--}}
    {{--        </div>--}}
    {{--        <a href="#" class="text-color fw-bold" style="text-decoration: none; font-size: 13px;">Ver Extrato</a>--}}
    {{--    </div>--}}

    <button id="openModal" class="create-btn"><i class="fa fa-plus text-white"></i></button>

    <!-- Modal com Form -->
    <div id="modalConta" class="custom-modal">
        <div class="custom-modal-content">
            <span id="closeModal" class="close-btn">&times;</span>

            <form id="formConta">
                <div class="balance-box">
                    <span>Adicionar conta</span>
                    <div class="row mt-2">
                        <div class="col-12">
                            <input type="text" class="form-control" name="bank_name" placeholder="Banco ..." required>
                        </div>
                        <div class="col-12 mt-2">
                            <input type="number" step="0.01" class="form-control" name="current_balance"
                                   placeholder="R$ 0,00" required>
                        </div>
                        <div class="col-12 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="corrente">
                                <label class="form-check-label" for="corrente">
                                    Corrente
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="poupanca" checked>
                                <label class="form-check-label" for="poupanca">
                                    Poupança
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="investimento" checked>
                                <label class="form-check-label" for="investimento">
                                    Investimento
                                </label>
                            </div>
                        </div>
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-success">Salvar</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista -->
    <div id="listaContas" class="mt-4"></div>

    <script>
        const modal = document.getElementById('modalConta');
        const openBtn = document.getElementById('openModal');
        const closeBtn = document.getElementById('closeModal');

        openBtn.addEventListener('click', () => {
            modal.classList.add('show');
        });

        closeBtn.addEventListener('click', () => {
            modal.classList.remove('show');
        });

        function brlPrice(valor) {
            return valor.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
        }

        document.getElementById('formConta').addEventListener('submit', async function (e) {
            e.preventDefault();
            const form = e.target;
            const data = new FormData(form);

            try {
                const response = await fetch("{{ route('accounts.store') }}", {
                    method: "POST",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: data
                });

                if (!response.ok) throw new Error('Erro ao salvar conta.');
                const novaConta = await response.json();

                // Fecha o modal e limpa
                modal.classList.remove('show');
                form.reset();

                // Adiciona novo card
                const container = document.getElementById('listaContas');

                const saldoConta = parseFloat(novaConta.current_balance) || 0;
                const saldoCofrinho = (novaConta.savings && novaConta.savings.length > 0)
                    ? parseFloat(novaConta.savings[0].current_amount) || 0
                    : 0;

                const total = saldoConta + saldoCofrinho;

                const card = `
                            <div class="balance-box">
                                <span>${novaConta.bank_name}</span>
                                <strong>${brlPrice(total)}</strong>
                                <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
                                    <small>
                                        <b class="text-muted">Na conta </b>
                                        <div class="d-flex align-items-center">
                                            <span>${brlPrice(saldoConta)}</span>
                                            <small class="text-success mx-2"> +17%</small>
                                        </div>
                                    </small>
                                    <small>
                                        <b class="text-muted">Cofrinhos</b>
                                        <div class="d-flex align-items-center">
                                            <span>${brlPrice(saldoCofrinho)}</span>
                                            <small class="text-danger mx-2"> +3%</small>
                                        </div>
                                    </small>
                                </div>
                                <a href="#" class="text-color fw-bold" style="text-decoration: none; font-size: 13px;">Ver Extrato</a>
                            </div>
                            `;
                container.insertAdjacentHTML('beforeend', card);
            } catch (err) {
                alert(err.message);
            }
        });

        async function carregarContas() {
            try {
                const response = await fetch("{{ route('accounts.index') }}");
                if (!response.ok) throw new Error('Erro ao carregar contas.');

                const contas = await response.json();

                const container = document.getElementById('listaContas');
                container.innerHTML = ''; // limpa antes

                contas.forEach(novaConta => {
                    const card = `
                                       <div class="balance-box">
                                            <span>${novaConta.bank_name}</span>
                                            <strong>${novaConta.total}</strong>
                                            <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
                                                <small>
                                                    <b class="text-muted">Na conta </b>
                                                    <div class="d-flex align-items-center">
                                                        <span>${novaConta.current_balance}</span>
                                                        <small class="text-success mx-2"> +17%</small>
                                                    </div>
                                                </small>
                                                <small>
                                                    <b class="text-muted">Cofrinhos</b>
                                                    <div class="d-flex align-items-center">
                                                        <span>${novaConta.saving_amount}</span>
                                                        <small class="text-danger mx-2"> +3%</small>
                                                    </div>
                                                </small>
                                            </div>
                                            <a href="#" class="text-color fw-bold" style="text-decoration: none; font-size: 13px;">Ver Extrato</a>
                                        </div>
                                    `;
                    container.insertAdjacentHTML('beforeend', card);
                });
            } catch (err) {
                alert(err.message);
            }
        }

        // Carrega assim que abrir a tela
        window.addEventListener('DOMContentLoaded', carregarContas);
    </script>

@endsection
