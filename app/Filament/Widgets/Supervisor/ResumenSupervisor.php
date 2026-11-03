<?php

namespace App\Filament\Widgets\Supervisor;

use App\Enums\EstadoCaso;
use App\Enums\Prioridad;
use App\Enums\TipoUsuario;
use App\Filament\Widgets\ResumenDeInicio;
use App\Models\Caso;
use App\Models\Cuadrilla;

class ResumenSupervisor extends ResumenDeInicio
{
    protected static TipoUsuario $perfil = TipoUsuario::Supervisor;

    protected string $view = 'supervisor.inicio';

    /**
     * El recorrido de un caso abierto, en el orden en que avanza.
     */
    private const RECORRIDO = [
        EstadoCaso::Recibida, EstadoCaso::EnRevision, EstadoCaso::Asignada,
        EstadoCaso::Programada, EstadoCaso::EnAtencion, EstadoCaso::PendienteValidacion,
    ];

    protected function getViewData(): array
    {
        $usuario = $this->usuario();
        $datos = [...$this->momento(), 'usuario' => $usuario, 'unidad' => $usuario->unidad];

        if (! $usuario->unidad_id) {
            return $datos;
        }

        $abiertos = fn () => Caso::where('unidad_id', $usuario->unidad_id)->whereNotIn('estado', EstadoCaso::finales());

        $porEstado = $abiertos()->selectRaw('estado, count(*) as total')->groupBy('estado')->pluck('total', 'estado');
        $porPrioridad = $abiertos()->selectRaw('prioridad, count(*) as total')->groupBy('prioridad')->pluck('total', 'prioridad');

        return [
            ...$datos,
            'totalAbiertos' => $porEstado->sum(),
            'recorrido' => collect(self::RECORRIDO)->map(fn (EstadoCaso $estado): array => [
                'clave' => $estado->value,
                'nombre' => $estado->getLabel(),
                'total' => (int) ($porEstado[$estado->value] ?? 0),
            ]),
            'prioridades' => collect(Prioridad::cases())->map(fn (Prioridad $p): array => [
                'clave' => $p->value,
                'nombre' => $p->getLabel(),
                'total' => (int) ($porPrioridad[$p->value] ?? 0),
            ]),
            'sinPrioridad' => $abiertos()->whereNull('prioridad')->count(),
            'vencidos' => $abiertos()->where('vencido', true)->count(),
            'porVencer' => $abiertos()->where('vencido', false)->whereBetween('fecha_objetivo', [now(), now()->addDays(2)])->count(),
            'urgentes' => $abiertos()->with('categoria:id,nombre')->whereNotNull('fecha_objetivo')
                ->orderByDesc('vencido')->orderBy('fecha_objetivo')->limit(6)
                ->get(['id', 'categoria_id', 'estado', 'prioridad', 'vencido', 'fecha_objetivo']),
            'cuadrillas' => Cuadrilla::where('unidad_id', $usuario->unidad_id)->withCount('tecnicos')->orderBy('nombre')->get(),
        ];
    }
}
