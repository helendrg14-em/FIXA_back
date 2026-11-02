<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Contraseña guardada | FIXA</title>
    <link rel="icon" href="{{ asset('images/fixa-marca.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/fixa-fuentes.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fixa-portal.css') }}">
</head>
<body>
<main class="acceso-pagina">
    <div class="acceso-tarjeta">
        <img src="{{ asset('images/fixa-logo.svg') }}" alt="FIXA" width="118" height="40">
        <h1>Contraseña guardada</h1>
        <p>Ya puedes entrar al panel con tu correo y tu contraseña.</p>
        <a class="boton boton-oscuro" href="{{ url('/admin/login') }}">Ir al panel</a>
    </div>
</main>
</body>
</html>
