{{-- Lo que el técnico necesita para ir al lugar: qué reportaron, dónde y las fotos. Sin datos del vecino. --}}
@php
    $reportes = $caso->reportes()->with('fotos')->orderBy('es_apoyo')->orderBy('id')->get();
    $lugar = $caso->reportes()->orderBy('es_apoyo')->orderBy('id')
        ->selectRaw('ST_Y(ubicacion) as lat, ST_X(ubicacion) as lng')->first();
    $rechazo = $caso->intervenciones()->where('estado_validacion', 'rechazada')->latest('fecha')->value('observacion_validacion');
@endphp

<div class="fx-orden">
    @if ($caso->estado === \App\Enums\EstadoCaso::EnAtencion && filled($rechazo))
        <p class="fx-orden-correccion"><strong>El supervisor pidió corregir:</strong> {{ $rechazo }}</p>
    @endif

    @if ($lugar?->lat !== null)
        <a class="fx-orden-llegar" target="_blank" rel="noopener"
           href="https://www.openstreetmap.org/?mlat={{ $lugar->lat }}&mlon={{ $lugar->lng }}#map=18/{{ $lugar->lat }}/{{ $lugar->lng }}">
            Ver la ubicación en el mapa
        </a>
    @endif

    <ol class="fx-reportes">
        @foreach ($reportes as $reporte)
            <li class="fx-reporte">
                <div class="fx-reporte-cabecera">
                    <span class="fx-reporte-codigo">{{ ucfirst(str_replace('_', ' ', $reporte->tipo_incidencia)) }}</span>
                    @if ($reporte->es_apoyo)
                        <span class="fx-chip">Apoyo de otro vecino</span>
                    @endif
                </div>

                @if (filled($reporte->descripcion))
                    <p class="fx-reporte-texto">{{ $reporte->descripcion }}</p>
                @endif

                @php
                    $datos = array_filter([
                        'Dirección' => $reporte->direccion,
                        'Referencia' => $reporte->referencia,
                        'Luminarias' => $reporte->numero_luminarias,
                    ], fn ($valor) => filled($valor));
                @endphp
                @if ($datos)
                    <dl class="fx-reporte-datos">
                        @foreach ($datos as $etiqueta => $valor)
                            <div><dt>{{ $etiqueta }}</dt><dd>{{ $valor }}</dd></div>
                        @endforeach
                    </dl>
                @endif

                @if ($reporte->fotos->isNotEmpty())
                    <div class="fx-fotos">
                        @foreach ($reporte->fotos as $foto)
                            <a href="{{ $foto->url }}" target="_blank" rel="noopener" class="fx-foto" title="Abrir la foto en grande">
                                <img src="{{ $foto->url }}" alt="Foto del problema reportado" loading="lazy">
                            </a>
                        @endforeach
                    </div>
                @endif
            </li>
        @endforeach
    </ol>
</div>
