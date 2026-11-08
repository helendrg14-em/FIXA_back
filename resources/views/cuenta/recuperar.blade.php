@extends('layouts.publico')

@section('titulo', 'Recupera tu contraseña | FIXA')
@section('noindex', true)

@section('contenido')
<div class="acceso">
    @include('cuenta.partes.marca')

    <main class="acceso-pagina">
        <div class="acceso-tarjeta">
            @if (session('enviado'))
                <svg class="listo-icono" viewBox="0 0 64 64" aria-hidden="true">
                    <circle cx="32" cy="32" r="32"/>
                    <path d="m20 33 8 8 16-17"/>
                </svg>
                <h1>Revisa tu correo</h1>
                <p role="status">Si <strong>{{ session('enviado') }}</strong> es el correo de una cuenta activa de FIXA, te enviamos un enlace para crear una contraseña nueva. Vence en 60 minutos.</p>
                <p>¿No llegó? Revisa la carpeta de spam o pide ayuda al administrador.</p>
                <a class="boton boton-oscuro" href="{{ url('/admin/login') }}">Volver al inicio de sesión</a>
            @else
                <h1>Recupera tu contraseña</h1>
                <p>Escribe el correo de tu cuenta y te enviaremos un enlace para crear una contraseña nueva.</p>

                <form method="POST" action="{{ route('cuenta.recuperar.enviar') }}">
                    @csrf
                    <div class="campo">
                        <label for="correo">Correo institucional</label>
                        <div class="campo-control">
                            <input id="correo" name="correo" type="email" autocomplete="email" required maxlength="120"
                                   value="{{ old('correo') }}" @error('correo') aria-invalid="true" aria-describedby="correo-error" @enderror>
                        </div>
                        @error('correo') <span class="error" id="correo-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <button class="boton boton-oscuro" type="submit">Enviar enlace</button>
                </form>
                <p class="acceso-volver"><a href="{{ url('/admin/login') }}">Volver al inicio de sesión</a></p>
            @endif
        </div>
    </main>
</div>
@endsection
