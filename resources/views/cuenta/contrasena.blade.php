@extends('layouts.publico')

@section('titulo', ($nueva ? 'Crea tu contraseña' : 'Cambia tu contraseña').' | FIXA')
@section('noindex', true)

@section('contenido')
<div class="acceso">
    @include('cuenta.partes.marca')

    <main class="acceso-pagina">
        <div class="acceso-tarjeta">
            @if ($nueva)
                <h1>Crea tu contraseña</h1>
                <p>La usarás para entrar al panel de FIXA con tu correo institucional.</p>
            @else
                <h1>Cambia tu contraseña</h1>
                <p>Desde ahora entrarás al panel con esta contraseña. La anterior dejará de funcionar.</p>
            @endif

            <form method="POST" action="{{ route('cuenta.contrasena.guardar', ['usuario' => $usuario]) }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="campo">
                    <label for="password">Contraseña nueva</label>
                    <div class="campo-control">
                        <input id="password" name="password" type="password" autocomplete="new-password" required minlength="12"
                               aria-describedby="password-ayuda" @error('password') aria-invalid="true" @enderror>
                        @include('cuenta.partes.ver-contrasena', ['campo' => 'password'])
                    </div>
                    <ul class="requisitos" id="password-ayuda" aria-live="polite">
                        <li data-regla="largo">Al menos 12 caracteres</li>
                        <li data-regla="letra">Una letra o más</li>
                        <li data-regla="numero">Un número o más</li>
                    </ul>
                    @error('password') <span class="error" role="alert">{{ $message }}</span> @enderror
                </div>

                <div class="campo">
                    <label for="password_confirmation">Repite la contraseña</label>
                    <div class="campo-control">
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                               aria-describedby="confirmacion-ayuda">
                        @include('cuenta.partes.ver-contrasena', ['campo' => 'password_confirmation'])
                    </div>
                    <ul class="requisitos" id="confirmacion-ayuda" aria-live="polite">
                        <li data-regla="igual">Las dos contraseñas coinciden</li>
                    </ul>
                </div>

                <button class="boton boton-oscuro" type="submit">Guardar contraseña</button>
            </form>
        </div>
    </main>
</div>
@endsection

@push('scripts')
<script>
    // Marca cada requisito a medida que se cumple; el servidor vuelve a validar todo al guardar.
    (() => {
        const clave = document.getElementById('password');
        const repetida = document.getElementById('password_confirmation');
        const reglas = {
            largo: () => clave.value.length >= 12,
            letra: () => /\p{L}/u.test(clave.value),
            numero: () => /\d/.test(clave.value),
            igual: () => repetida.value !== '' && repetida.value === clave.value,
        };
        const revisar = () => document.querySelectorAll('[data-regla]').forEach((li) => {
            li.classList.toggle('cumple', reglas[li.dataset.regla]());
        });
        clave.addEventListener('input', revisar);
        repetida.addEventListener('input', revisar);

        document.querySelectorAll('.campo-ver').forEach((boton) => {
            boton.addEventListener('click', () => {
                const campo = document.getElementById(boton.dataset.campo);
                const visible = campo.type === 'password';
                campo.type = visible ? 'text' : 'password';
                boton.setAttribute('aria-pressed', String(visible));
            });
        });
    })();
</script>
@endpush
