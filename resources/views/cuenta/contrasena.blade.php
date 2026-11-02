<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Crea tu contraseña | FIXA</title>
    <link rel="icon" href="{{ asset('images/fixa-marca.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/fixa-fuentes.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fixa-portal.css') }}">
</head>
<body>
<main class="acceso-pagina">
    <div class="acceso-tarjeta">
        <img src="{{ asset('images/fixa-logo.svg') }}" alt="FIXA" width="118" height="40">
        <h1>Crea tu contraseña</h1>
        <p>La usarás para entrar al panel de FIXA.</p>
        <form method="POST" action="{{ route('cuenta.contrasena.guardar', ['usuario' => $usuario]) }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="campo">
                <label for="password">Contraseña nueva</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required minlength="12"
                       aria-describedby="password-ayuda" @error('password') aria-invalid="true" @enderror>
                <small id="password-ayuda">Al menos 12 caracteres, con letras y números.</small>
                @error('password') <span class="error" role="alert">{{ $message }}</span> @enderror
            </div>
            <div class="campo">
                <label for="password_confirmation">Repite la contraseña</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
            </div>
            <button class="boton boton-oscuro" type="submit">Guardar contraseña</button>
        </form>
    </div>
</main>
</body>
</html>
