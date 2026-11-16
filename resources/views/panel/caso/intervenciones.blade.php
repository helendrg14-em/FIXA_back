{{-- Trabajo registrado por la cuadrilla, con la foto del después. --}}
@php
    $intervenciones = $getRecord()->intervenciones()->with(['evidencias', 'tecnico'])->orderByDesc('fecha')->get();
    $estados = ['pendiente' => ['Por validar', 'fx-chip-aviso'], 'aprobada' => ['Aprobado', 'fx-chip-ok'], 'rechazada' => ['Rechazado', 'fx-chip-error']];
@endphp

<ol class="fx-reportes">
    @foreach ($intervenciones as $intervencion)
        @php [$texto, $clase] = $estados[$intervencion->estado_validacion] ?? [$intervencion->estado_validacion, '']; @endphp
        <li class="fx-reporte">
            <div class="fx-reporte-cabecera">
                <span class="fx-chip {{ $clase }}">{{ $texto }}</span>
                <span class="fx-reporte-meta">
                    {{ $intervencion->tecnico?->getFilamentName() ?? 'Técnico' }}
                    · {{ $intervencion->fecha?->format('d/m/Y H:i') }}
                </span>
            </div>
            <p class="fx-reporte-texto">{{ $intervencion->trabajo_realizado }}</p>
            @if (filled($intervencion->observacion_validacion))
                <p class="fx-historial-motivo">Observación: {{ $intervencion->observacion_validacion }}</p>
            @endif
            @if ($intervencion->evidencias->whereNotNull('url')->isNotEmpty())
                <div class="fx-fotos">
                    @foreach ($intervencion->evidencias->whereNotNull('url') as $evidencia)
                        <a href="{{ $evidencia->url }}" target="_blank" rel="noopener" class="fx-foto" title="Abrir la evidencia en grande">
                            <img src="{{ $evidencia->url }}" alt="Evidencia del trabajo" loading="lazy">
                        </a>
                    @endforeach
                </div>
            @endif
        </li>
    @endforeach
</ol>
