@extends('layouts.templates.app')

@section('new-content')
    <section class="mt-6">

        <div class="mb-4">
            <h2 class="text-xl font-semibold">Como usar a parte de Contas</h2>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Aqui você encontra uma explicação detalhada sobre o módulo de contas.
            </p>
        </div>

        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-2xl p-4 space-y-3 text-sm text-neutral-800 dark:text-neutral-200">

            {{-- AQUI VOCÊ ESCREVE O TEXTO QUE QUISER --}}
            <p>
                (Escreva aqui, em texto simples, como funciona a criação de contas,
                edição, exclusão, saldos, etc. Você pode separar em tópicos, passo a passo,
                o que achar melhor.)
            </p>

            <p>
                Exemplo:
                <br>- Para adicionar uma nova conta, vá em "Contas" e toque no botão "+".
                <br>- Preencha o nome da conta, saldo inicial e tipo.
                <br>- Salve para começar a usar.
            </p>

            {{-- LINK DO YOUTUBE NO FINAL --}}
            <div class="pt-3 border-t border-dashed border-neutral-200 dark:border-neutral-700 mt-2">
                <p class="text-xs text-neutral-500 mb-1">
                    Prefere ver em vídeo?
                </p>
                <a href="https://www.youtube.com/seu-video-aqui"
                   target="_blank"
                   class="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline">
                    Assistir tutorial no YouTube
                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M7 17L17 7M7 7h10v10"/>
                    </svg>
                </a>
            </div>

        </div>

    </section>
@endsection
