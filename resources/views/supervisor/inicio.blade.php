@php
    use Filament\Support\Icons\Heroicon;
@endphp

<x-filament-widgets::widget class="fx-inicio">
    <x-fixa.saludo :saludo="$saludo" :nombre="$usuario->nombres" perfil="Supervisor"
                   :detalle="$unidad?->nombre" :fecha="$fecha" />

    @if (! $unidad)
        <x-fixa.bloque titulo="Tu cuenta aún no tiene unidad">
            <x-fixa.vacio titulo="No hay casos que mostrar todavía." :icono="Heroicon::OutlinedBuildingOffice2">
                Pide al administrador que asigne tu unidad responsable. Después verás aquí los casos que te tocan.
            </x-fixa.vacio>
        </x-fixa.bloque>
    @else
        <x-fixa.bloque titulo="Recorrido de los casos"
                       :descripcion="$totalAbiertos === 1 ? '1 caso abierto en tu unidad, según la etapa en que está.' : $totalAbiertos.' casos abiertos en tu unidad, según la etapa en que están.'">
            <ol class="fx-recorrido">
                @foreach ($recorrido as $etapa)
                    <li @class(['fx-recorrido-etapa', 'fx-recorrido-vacia' => $etapa['total'] === 0])
                        style="--valor: {{ $totalAbiertos ? $etapa['total'] / $totalAbiertos : 0 }}; --i: {{ $loop->index }}">
                        <span class="fx-recorrido-total">{{ $etapa['total'] }}</span>
                        <span class="fx-recorrido-nombre">{{ $etapa['nombre'] }}</span>
                        <span class="fx-recorrido-pista"><span class="fx-recorrido-relleno"></span></span>
                    </li>
                @endforeach
            </ol>
        </x-fixa.bloque>

        <div class="fx-rejilla fx-rejilla-supervisor">
            <x-fixa.bloque titulo="Requieren atención" descripcion="Primero los que ya pasaron su plazo, después los más próximos a vencer." class="fx-area-urgentes">
                @forelse ($urgentes as $caso)
                    <div class="fx-fila">
                        <span @class(['fx-marca-estado', 'fx-marca-vencido' => $caso->vencido]) aria-hidden="true"></span>
                        <span class="fx-fila-principal">
                            <strong>Caso N.º {{ $caso->id }} · {{ $caso->categoria?->nombre }}</strong>
                            <span>{{ $caso->estado->getLabel() }}@if ($caso->prioridad) · Prioridad {{ mb_strtolower($caso->prioridad->getLabel()) }}@endif</span>
                        </span>
                        <span class="fx-fila-meta">
                            @if ($caso->vencido)
                                <span class="fx-chip fx-chip-naranja">Fuera de plazo</span>
                            @else
                                Vence {{ $caso->fecha_objetivo->locale('es')->diffForHumans() }}
                            @endif
                        </span>
                    </div>
                @empty
                    <x-fixa.vacio titulo="Nada pendiente con plazo." :icono="Heroicon::OutlinedCheckCircle">
                        Cuando un caso tenga fecha objetivo aparecerá aquí, ordenado por urgencia.
                    </x-fixa.vacio>
                @endforelse
            </x-fixa.bloque>

            <div class="fx-columna">
                <x-fixa.bloque titulo="Plazos">
                    <dl class="fx-datos">
                        <div @class(['fx-datos-alerta' => $vencidos > 0])>
                            <dt>Fuera de plazo</dt>
                            <dd>{{ $vencidos }}</dd>
                        </div>
                        <div>
                            <dt>Vencen en 48 h</dt>
                            <dd>{{ $porVencer }}</dd>
                        </div>
                    </dl>
                </x-fixa.bloque>

                <x-fixa.bloque titulo="Prioridad" descripcion="Casos abiertos según el riesgo que definiste.">
                    @php $maxPrioridad = max(1, $prioridades->max('total'), $sinPrioridad); @endphp
                    <ul class="fx-barras">
                        @foreach ($prioridades as $p)
                            <li class="fx-prioridad-{{ $p['clave'] }}" style="--valor: {{ $p['total'] / $maxPrioridad }}; --i: {{ $loop->index }}">
                                <span class="fx-barras-nombre">{{ $p['nombre'] }}</span>
                                <span class="fx-barras-pista"><span class="fx-barras-relleno"></span></span>
                                <span class="fx-barras-total">{{ $p['total'] }}</span>
                            </li>
                        @endforeach
                        <li class="fx-prioridad-sin" style="--valor: {{ $sinPrioridad / $maxPrioridad }}; --i: 4">
                            <span class="fx-barras-nombre">Sin definir</span>
                            <span class="fx-barras-pista"><span class="fx-barras-relleno"></span></span>
                            <span class="fx-barras-total">{{ $sinPrioridad }}</span>
                        </li>
                    </ul>
                </x-fixa.bloque>

                <x-fixa.bloque titulo="Cuadrillas">
                    @forelse ($cuadrillas as $cuadrilla)
                        <div class="fx-fila">
                            <span class="fx-acceso-icono"><x-filament::icon :icon="Heroicon::OutlinedWrenchScrewdriver" /></span>
                            <span class="fx-fila-principal">
                                <strong>{{ $cuadrilla->nombre }}</strong>
                                <span>Turno {{ mb_strtolower($cuadrilla->turno) }}</span>
                            </span>
                            <span class="fx-fila-meta">{{ $cuadrilla->tecnicos_count }} {{ $cuadrilla->tecnicos_count === 1 ? 'técnico' : 'técnicos' }}</span>
                        </div>
                    @empty
                        <x-fixa.vacio titulo="Tu unidad aún no tiene cuadrillas." :icono="Heroicon::OutlinedUserGroup" />
                    @endforelse
                </x-fixa.bloque>
            </div>
        </div>
    @endif
</x-filament-widgets::widget>
