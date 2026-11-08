@extends('layouts.publico')

@section('titulo', 'Contraseña guardada | FIXA')
@section('noindex', true)

@section('contenido')
<div class="acceso">
    @include('cuenta.partes.cabecera')

    <div class="acceso-caja">
        @include('cuenta.partes.marca')

        <main class="acceso-pagina">
            <div class="acceso-tarjeta">
                <svg class="listo-icono" viewBox="0 0 64 64" aria-hidden="true">
                    <circle cx="32" cy="32" r="32"/>
                    <path d="m20 33 8 8 16-17"/>
                </svg>
                <h1>Contraseña guardada</h1>
                <p>Ya puedes entrar al panel con tu correo y tu contraseña.</p>
                <a class="boton boton-oscuro" href="{{ url('/admin/login') }}">Ir al panel</a>
            </div>
        </main>
    </div>
</div>
@endsection
