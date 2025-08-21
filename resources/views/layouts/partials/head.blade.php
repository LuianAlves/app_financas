<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <title>Finanças Cliqis</title>

    <link rel="manifest" href="{{ asset('laravelpwa/manifest.json') }}">
    <meta name="theme-color" content="#f1f1f1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    @stack('styles')

    @php
        $styleCssPath = public_path('assets/css/style.css');
        $crudCssPath = public_path('assets/css/crud_style.css');
    @endphp
    <link href="{{ asset('assets/css/style.css') }}?v={{ file_exists($styleCssPath) ? filemtime($styleCssPath) : time() }}" rel="stylesheet">
    <link href="{{ asset('assets/css/crud_style.css') }}?v={{ file_exists($crudCssPath) ? filemtime($crudCssPath) : time() }}" rel="stylesheet">
</head>
