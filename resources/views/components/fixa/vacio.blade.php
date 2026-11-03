@props(['titulo', 'icono' => \Filament\Support\Icons\Heroicon::OutlinedSparkles])

<div {{ $attributes->class('fx-vacio') }}>
    <span class="fx-vacio-icono"><x-filament::icon :icon="$icono" /></span>
    <p class="fx-vacio-titulo">{{ $titulo }}</p>
    @if (trim($slot) !== '')
        <p class="fx-vacio-texto">{{ $slot }}</p>
    @endif
</div>
