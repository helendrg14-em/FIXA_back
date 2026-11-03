@php
    use App\Enums\EstadoCaso;
    use Filament\Support\Icons\Heroicon;

    $detalle = $cuadrilla ? collect([$cuadrilla->nombre, $cuadrilla->unidad?->sigla])->filter()->implode(' · ') : null;
@endphp

<x-filament-widgets::widget class="fx-inicio">
    <x-fixa.saludo :saludo="$saludo" :nombre="$usuario->nombres" perfil="Técnico" :detalle="$detalle" :fecha="$fecha" />

    @if (! $cuadrilla)
        <x-fixa.bloque titulo="Aún no perteneces a una cuadrilla">
            <x-fixa.vacio titulo="No hay trabajo asignado todavía." :icono="Heroicon::OutlinedUserGroup">
                Pide al administrador que te asigne a una cuadrilla. Después verás aquí los casos programados.
            </x-fixa.vacio>
        </x-fixa.bloque>
    @else
        <div class="fx-rejilla fx-rejilla-tecnico">
            <x-fixa.bloque titulo="Agenda de la cuadrilla" descripcion="Casos programados y en atención, por fecha prevista." class="fx-area-agenda">
                @forelse ($agenda as $caso)
                    @php
                        $esHoy = $caso->fecha_prevista->isToday();
                        $atrasado = $caso->fecha_prevista->isPast() && ! $esHoy;
                    @endphp
                    <div @class(['fx-fila fx-agenda-fila', 'fx-agenda-hoy' => $esHoy, 'fx-agenda-atrasada' => $atrasado])>
                        <span class="fx-fecha" aria-hidden="true">
                            <span>{{ $caso->fecha_prevista->format('j') }}</span>
                            <span>{{ $caso->fecha_prevista->locale('es')->translatedFormat('M') }}</span>
                        </span>
                        <span class="fx-fila-principal">
                            <strong>Caso N.º {{ $caso->id }} · {{ $caso->categoria }}</strong>
                            <span>
                                @if ($esHoy) Hoy @elseif ($atrasado) Previsto {{ $caso->fecha_prevista->locale('es')->diffForHumans() }} @else {{ ucfirst($caso->fecha_prevista->locale('es')->translatedFormat('l j \d\e F')) }} @endif
                                @if ($caso->prioridad) · Prioridad {{ mb_strtolower(\App\Enums\Prioridad::from($caso->prioridad)->getLabel()) }} @endif
                            </span>
                        </span>
                        <span class="fx-fila-meta">
                            <span @class(['fx-chip', 'fx-chip-lima' => $caso->estado === EstadoCaso::EnAtencion])>{{ $caso->estado->getLabel() }}</span>
                        </span>
                    </div>
                @empty
                    <x-fixa.vacio titulo="Tu cuadrilla no tiene casos programados." :icono="Heroicon::OutlinedCalendar">
                        Cuando el supervisor programe un caso, aparecerá aquí con su fecha.
                    </x-fixa.vacio>
                @endforelse

                @if ($totalAgenda > $agenda->count())
                    <p class="fx-ayuda">Se muestran los {{ $agenda->count() }} más próximos de {{ $totalAgenda }}.</p>
                @endif
            </x-fixa.bloque>

            <x-fixa.bloque titulo="Tu semana" class="fx-area-semana">
                <dl class="fx-datos fx-datos-columna">
                    <div @class(['fx-datos-destacado' => $paraHoy > 0])>
                        <dt>Para hoy o atrasados</dt>
                        <dd>{{ $paraHoy }}</dd>
                    </div>
                    <div>
                        <dt>Próximos 7 días</dt>
                        <dd>{{ $estaSemana }}</dd>
                    </div>
                    <div>
                        <dt>En atención ahora</dt>
                        <dd>{{ $enAtencion }}</dd>
                    </div>
                    <div>
                        <dt>Tus intervenciones, últimos 30 días</dt>
                        <dd>{{ $intervenciones }}</dd>
                    </div>
                </dl>
            </x-fixa.bloque>
        </div>
    @endif
</x-filament-widgets::widget>
