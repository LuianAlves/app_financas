<head>
    <meta charset="utf-8"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
{{--    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">--}}

    <title>Finanças Cliqis</title>

    <!-- Webapp -->
    <link rel="manifest" href="{{ asset('laravelpwa/manifest.json') }}">
    <meta name="theme-color" content="#fff">
    <meta name="color-scheme" content="light dark"/>

    <!-- Dark Mode -->
    <script>
        (function () {
            try {
                const saved = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const isDark = saved ? saved === 'dark' : prefersDark;
                document.documentElement.classList.toggle('dark', isDark);
            } catch (e) {
            }
        })();
    </script>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a'
                        }
                    },
                    boxShadow: {
                        soft: '0 2px 10px rgba(0,0,0,.06)',
                        softDark: '0 6px 24px rgba(0,0,0,.35)'
                    }
                }
            }
        };
    </script>

    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cpath fill='%233b82f6' d='M16 2l14 8v12l-14 8-14-8V10z'/%3E%3Cpath fill='%23fff' d='M16 7l9 5v8l-9 5-9-5v-8z'/%3E%3C/svg%3E"/>

    @stack('styles')

    @php
        $styleCssPath = public_path('assets/css/style.css');
    @endphp

    <link href="{{ asset('assets/css/style.css') }}?v={{ file_exists($styleCssPath) ? filemtime($styleCssPath) : time() }}" rel="stylesheet">
</head>
