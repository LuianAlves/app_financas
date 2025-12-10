@extends('layouts.templates.app')

@section('new-content')
    <section class="mt-6">

        <div class="mb-4">
            <h2 class="text-xl font-semibold">Cartões e faturas</h2>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Saiba como cadastrar cartões, controlar limite e acompanhar faturas no app.
            </p>
        </div>

        <div
            class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-2xl p-4 space-y-3 text-sm text-neutral-800 dark:text-neutral-200">

            <p>
                Aqui você explica como funciona o módulo de cartão de crédito:
            </p>

            <ul class="list-disc list-inside space-y-1">
                <li>Como cadastrar um novo cartão (banco, apelido, limite, fechamento, vencimento).</li>
                <li>Como lançar compras na fatura certa.</li>
                <li>Como visualizar o valor fechado da fatura atual e futuras.</li>
                <li>Como funciona a integração com as transações do app.</li>
            </ul>

            <p>
                Use exemplos práticos, como “Compra parcelada”, “Compra à vista no crédito”,
                “Ajustando limite”, etc.
            </p>

            <div class="pt-3 border-t border-dashed border-neutral-200 dark:border-neutral-700 mt-2">
                <p class="text-xs text-neutral-500 mb-1">
                    Veja o passo a passo em vídeo:
                </p>
                <a href="https://www.youtube.com/seu-video-cartao"
                   target="_blank"
                   class="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline">
                    Como usar cartões e faturas
                </a>
            </div>
        </div>

    </section>
@endsection
