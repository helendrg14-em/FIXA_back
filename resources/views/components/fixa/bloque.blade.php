@props(['titulo', 'descripcion' => null, 'enlace' => null, 'textoEnlace' => 'Ver todo'])

{{-- Bloque de contenido del escritorio: título, acción opcional y cuerpo. --}}
<section {{ $attributes->class('fx-bloque') }}>
    <header class="fx-bloque-cabecera">
        <div>
            <h2>{{ $titulo }}</h2>
            @if ($descripcion)
                <p>{{ $descripcion }}</p>
            @endif
        </div>
        @if ($enlace)
            <a class="fx-enlace" href="{{ $enlace }}">
                {{ $textoEnlace }}
                <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::ArrowRight" class="fx-enlace-flecha" />
            </a>
        @endif
    </header>
    {{ $slot }}
</section>
