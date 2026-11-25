{{-- Reportes de los vecinos de un caso: una ficha corta por reporte, con fotos en miniatura. --}}
@php
    $reportes = $getRecord()->reportes()->with(['fotos', 'distrito', 'operador'])->orderBy('fecha_registro')->get();
@endphp

<ol class="fx-reportes">
    @forelse ($reportes as $reporte)
        <li class="fx-reporte" id="reporte-{{ $reporte->id }}">
            <div class="fx-reporte-cabecera">
                <span class="fx-reporte-codigo">{{ $reporte->codigo }}</span>
                @if ($reporte->es_apoyo)
                    <span class="fx-chip">Apoyo</span>
                @endif
                <span class="fx-reporte-meta">
                    {{ ucfirst(str_replace('_', ' ', $reporte->tipo_incidencia)) }}
                    · {{ $reporte->canal_origen?->getLabel() }}@if ($reporte->operador), {{ $reporte->operador->getFilamentName() }}@endif
                    · <time datetime="{{ $reporte->fecha_registro?->toIso8601String() }}">{{ $reporte->fecha_registro?->format('d/m/Y H:i') }}</time>
                </span>
            </div>

            @if (filled($reporte->descripcion))
                <p class="fx-reporte-texto">{{ $reporte->descripcion }}</p>
            @endif

            @php
                $datos = array_filter([
                    'Distrito' => $reporte->distrito?->nombre,
                    'Dirección' => $reporte->direccion,
                    'Referencia' => $reporte->referencia,
                    'Luminarias' => $reporte->numero_luminarias,
                    'Sin foto porque' => $reporte->motivo_sin_foto,
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
                            <img src="{{ $foto->url }}" alt="Foto del reporte {{ $reporte->codigo }}" loading="lazy">
                        </a>
                    @endforeach
                </div>
            @endif
        </li>
    @empty
        <li class="fx-vacio">Todavía no hay reportes asociados a este caso.</li>
    @endforelse
</ol>
