@extends('layouts.templates.app')

@section('new-content')
    <section class="mt-6">

        <div class="mb-4">
            <h2 class="text-xl font-semibold">Como usar a parte de Investimentos</h2>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Entenda como registrar, acompanhar e analisar seus investimentos dentro do app.
            </p>
        </div>

        <div
            class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-2xl p-4 space-y-3 text-sm text-neutral-800 dark:text-neutral-200">

            {{-- AQUI VOCÊ ESCREVE O TEXTO REAL --}}
            <p>
                Nesta área você pode explicar, em detalhes, como funciona o módulo de investimentos:
            </p>

            <ul class="list-disc list-inside space-y-1">
                <li>Como cadastrar um novo investimento.</li>
                <li>Quais campos precisam ser preenchidos (valor, taxa, data, tipo, etc.).</li>
                <li>Como o app calcula rentabilidade, projeções e histórico.</li>
                <li>Boas práticas para acompanhar seus investimentos pelo app.</li>
            </ul>

            <p>
                Você pode separar em passos, como:
                “Passo 1 – Cadastro”, “Passo 2 – Acompanhamento”, “Passo 3 – Resgates”, etc.
            </p>

            {{-- LINK DO YOUTUBE --}}
            <div class="pt-3 border-t border-dashed border-neutral-200 dark:border-neutral-700 mt-2">
                <p class="text-xs text-neutral-500 mb-1">
                    Prefere ver em vídeo?
                </p>
                <a href="https://www.youtube.com/seu-video-investimentos"
                   target="_blank"
                   class="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline">
                    Assistir tutorial de investimentos no YouTube
                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M7 17L17 7M7 7h10v10"/>
                    </svg>
                </a>
            </div>
        </div>

    </section>
@endsection
