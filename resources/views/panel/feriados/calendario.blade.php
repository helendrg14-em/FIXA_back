<x-filament-widgets::widget>
    <section class="fx-cal" aria-labelledby="fx-cal-titulo">
        <header class="fx-cal-cabecera">
            <div>
                <h2 id="fx-cal-titulo" class="fx-cal-titulo">{{ $titulo }}</h2>
                <p class="fx-cal-ayuda">
                    {{ $total === 0 ? 'Sin feriados este mes.' : ($total === 1 ? '1 feriado este mes.' : "{$total} feriados este mes.") }}
                    Toca un día para agregar un feriado.
                </p>
            </div>
            <nav class="fx-cal-nav" aria-label="Cambiar de mes">
                <button type="button" class="fx-cal-boton" wire:click="anterior" aria-label="Mes anterior">
                    <x-filament::icon icon="heroicon-m-chevron-left" class="fx-cal-icono" />
                </button>
                <button type="button" class="fx-cal-boton fx-cal-hoy" wire:click="hoy">Hoy</button>
                <button type="button" class="fx-cal-boton" wire:click="siguiente" aria-label="Mes siguiente">
                    <x-filament::icon icon="heroicon-m-chevron-right" class="fx-cal-icono" />
                </button>
            </nav>
        </header>

        <div class="fx-cal-rejilla" role="grid" aria-labelledby="fx-cal-titulo" wire:loading.class="fx-cal-cargando">
            @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $nombreDia)
                <div class="fx-cal-dia-semana" role="columnheader">{{ $nombreDia }}</div>
            @endforeach

            @foreach ($dias as $dia)
                @php
                    $clases = collect([
                        'fx-cal-dia',
                        $dia['delMes'] ? null : 'fx-cal-fuera',
                        $dia['finDeSemana'] ? 'fx-cal-finde' : null,
                        $dia['hoy'] ? 'fx-cal-es-hoy' : null,
                        $dia['feriado'] ? 'fx-cal-feriado' : null,
                    ])->filter()->implode(' ');
                    $etiqueta = \Illuminate\Support\Carbon::parse($dia['fecha'])->locale('es')->isoFormat('dddd D [de] MMMM');
                @endphp

                @if ($dia['feriado'])
                    <button type="button" role="gridcell" class="{{ $clases }}"
                            wire:click="$dispatch('editar-feriado', { id: {{ $dia['feriado']->id }} })"
                            title="{{ $dia['feriado']->nombre }} · tocar para editar"
                            aria-label="{{ $etiqueta }}: {{ $dia['feriado']->nombre }}. Editar">
                        <span class="fx-cal-numero">{{ $dia['numero'] }}</span>
                        <span class="fx-cal-nombre">{{ $dia['feriado']->nombre }}</span>
                    </button>
                @elseif ($dia['delMes'])
                    <button type="button" role="gridcell" class="{{ $clases }}"
                            wire:click="$dispatch('crear-feriado', { fecha: '{{ $dia['fecha'] }}' })"
                            aria-label="{{ $etiqueta }}. Agregar feriado">
                        <span class="fx-cal-numero">{{ $dia['numero'] }}</span>
                        <span class="fx-cal-mas" aria-hidden="true">+</span>
                    </button>
                @else
                    <div role="gridcell" class="{{ $clases }}" aria-hidden="true">
                        <span class="fx-cal-numero">{{ $dia['numero'] }}</span>
                    </div>
                @endif
            @endforeach
        </div>

        <ul class="fx-cal-leyenda" aria-label="Leyenda">
            <li><span class="fx-cal-muestra fx-cal-muestra-feriado"></span>Feriado</li>
            <li><span class="fx-cal-muestra fx-cal-muestra-finde"></span>Fin de semana, no cuenta como día hábil</li>
            <li><span class="fx-cal-muestra fx-cal-muestra-hoy"></span>Hoy</li>
        </ul>
    </section>
</x-filament-widgets::widget>
