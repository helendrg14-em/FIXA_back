<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @hasSection('noindex')<meta name="robots" content="noindex">@endif
    <title>@yield('titulo')</title>
    @hasSection('descripcion')<meta name="description" content="@yield('descripcion')">@endif
    <meta name="theme-color" content="#0b2418">
    <link rel="icon" href="{{ recurso('images/fixa-marca.png') }}" type="image/png">
    @include('partes.precarga-fuentes')
    <link rel="stylesheet" href="{{ recurso('css/fixa-fuentes.css') }}">
    <link rel="stylesheet" href="{{ recurso('css/fixa-portal.css') }}">
    <link rel="stylesheet" href="{{ recurso('movil/css/portal.css') }}">
</head>
<body class="@yield('clase-body')">
@yield('contenido')
@stack('scripts')
</body>
</html>
