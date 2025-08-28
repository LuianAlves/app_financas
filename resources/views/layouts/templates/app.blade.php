<!DOCTYPE html>
<html lang="pt-BR">

<!-- Include:head -->
@include('layouts.partials.head')

<body>

<div class="app-container">
    <!-- Include:head -->
    @auth
        @include('layouts.partials.sidenav')
    @endauth

    <main id="app-main" class="content-area scroll-content" data-skeleton="tx-list">
        @yield('content')

        @auth
            <div class="bottom-nav">
                <a href="{{route('dashboard')}}" class="bottom-nav-link" data-nav>
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="{{ route('transaction-view.index') }}" class="bottom-nav-link" data-nav>
                    <i class="fa-solid fa-cart-plus"></i>
                    <span>Transações</span>
                </a>
                <a href="{{ route('push.debug') }}" class="bottom-nav-link" data-nav>
                    <i class="fa-solid fa-arrow-up-right-dots"></i>
                    <span>Projeções</span>
                </a>
                <a href="{{route('user-view.index')}}" class="bottom-nav-link" data-nav>
                    <i class="fas fa-user"></i>
                    <span>Perfil</span>
                </a>
            </div>
        @endauth
    </main>
</div>

<!-- Include:scripts -->
@include('layouts.partials.scripts')

<script>
    window.__SPA_LITE__ = true;
</script>





{{--<script src="{{asset('assets/js/cache/app-nav.js')}}"></script>--}}
{{--<script src="{{asset('assets/js/cache/http.js')}}"></script>--}}
{{--<script src="{{asset('assets/js/cache/storage.js')}}"></script>--}}

{{--@php--}}
{{--    $installPath = public_path('assets/js/install.js');--}}
{{--@endphp--}}

{{--<script src="{{ asset('assets/js/install.js') }}?v={{ file_exists($installPath) ? filemtime($installPath) : time() }}" defer></script>--}}

{{--<div id="net-banner"--}}
{{--     style="display:none;position:fixed;left:50%;transform:translateX(-50%);bottom:85px;z-index:1200;background:#222;color:#fff;padding:6px 10px;border-radius:8px;font-size:12px;">--}}
{{--    Conexão lenta — exibindo dados em cache…--}}
{{--</div>--}}


@stack('scripts')

{{--<script>--}}
{{--    (() => {--}}
{{--        function onReady(fn){--}}
{{--            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn, {once:true});--}}
{{--            else fn();--}}
{{--        }--}}
{{--        onReady(() => {--}}
{{--            const ua = navigator.userAgent || '';--}}
{{--            const platform = navigator.platform || '';--}}
{{--            const mtp = navigator.maxTouchPoints || 0;--}}
{{--            const isStandalone = (typeof matchMedia==='function' && matchMedia('(display-mode: standalone)').matches) || (navigator.standalone === true);--}}
{{--            const isIOSLike = (/(iPad|iPhone|iPod)/.test(ua) && !window.MSStream) || (platform === 'MacIntel' && mtp > 1);--}}
{{--            const isAndroid = /Android/i.test(ua);--}}

{{--            const box = document.createElement('div');--}}
{{--            box.style.cssText = 'position:fixed;left:8px;right:8px;bottom:8px;z-index:9999;background:#111;color:#0f0;font:12px/1.35 monospace;padding:10px;border-radius:8px;opacity:.95;max-height:45vh;overflow:auto';--}}
{{--            box.innerHTML = `--}}
{{--<strong>A2HS DEBUG</strong> — toque para fechar<br>--}}
{{--isStandalone: ${isStandalone}<br>--}}
{{--isIOSLike: ${isIOSLike}<br>--}}
{{--isAndroid: ${isAndroid}<br>--}}
{{--UA: ${ua}<br>--}}
{{--platform: ${platform}<br>--}}
{{--maxTouchPoints: ${mtp}<br>--}}
{{--beforeinstallprompt fired? <span id="a2hs-bip">no</span><br>--}}
{{--Decision: ${--}}
{{--                isStandalone ? 'INSTALLED — nada a mostrar' :--}}
{{--                    isIOSLike ? 'iOS — mostrar banner iOS (Compartilhar → Adicionar à Tela de Início)' :--}}
{{--                        'Android/desktop — aguardar beforeinstallprompt p/ mostrar botão'--}}
{{--            }--}}
{{--`;--}}
{{--            box.addEventListener('click', () => box.remove());--}}
{{--            document.body.appendChild(box);--}}

{{--            // Só p/ marcar visualmente quando o evento chegar--}}
{{--            window.addEventListener('beforeinstallprompt', () => {--}}
{{--                const s = document.getElementById('a2hs-bip'); if (s) s.textContent = 'yes';--}}
{{--            });--}}
{{--        });--}}
{{--    })();--}}
{{--</script>--}}
</body>

</html>
