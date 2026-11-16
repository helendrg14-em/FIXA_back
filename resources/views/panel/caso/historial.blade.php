{{-- Historial del caso como línea de tiempo: lo más reciente arriba. --}}
@php
    use App\Enums\EstadoCaso;

    $eventos = $getRecord()->eventos()->orderByDesc('fecha_hora')->orderByDesc('id')->get();
@endphp

@if ($eventos->isEmpty())
    <p class="fx-vacio">Todavía no hay movimientos.</p>
@else
    <ol class="fx-historial">
        @foreach ($eventos as $evento)
            <li class="fx-historial-item">
                <time class="fx-historial-fecha" datetime="{{ $evento->fecha_hora?->toIso8601String() }}">
                    {{ $evento->fecha_hora?->format('d/m/Y H:i') }}
                </time>
                <p class="fx-historial-detalle">
                    @if ($estado = EstadoCaso::tryFrom((string) $evento->estado_nuevo))
                        @if ($evento->estado_nuevo !== $evento->estado_anterior)
                            <strong>{{ $estado->getLabel() }}.</strong>
                        @endif
                    @endif
                    {{ $evento->detalle }}
                </p>
                @if (filled($evento->motivo))
                    <p class="fx-historial-motivo">{{ $evento->motivo }}</p>
                @endif
            </li>
        @endforeach
    </ol>
@endif
