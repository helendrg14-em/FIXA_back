@props(['saludo', 'nombre', 'perfil', 'fecha', 'detalle' => null])

{{-- Cabecera del inicio de cada perfil. --}}
<section {{ $attributes->class('fx-saludo') }}>
    <x-fixa.ciudad class="fx-saludo-ciudad" />

    <div class="fx-saludo-texto">
        <h1>{{ $saludo }}, {{ $nombre }}</h1>
        <p class="fx-saludo-linea">
            <span class="fx-chip fx-chip-lima">{{ $perfil }}</span>
            @if ($detalle)
                <span>{{ $detalle }}</span>
                <span class="fx-saludo-punto" aria-hidden="true"></span>
            @endif
            <span>{{ $fecha }}</span>
        </p>
    </div>

    @isset($acciones)
        <div class="fx-saludo-acciones">{{ $acciones }}</div>
    @endisset
</section>
