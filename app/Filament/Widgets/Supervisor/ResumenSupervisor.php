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

        // Todos los conteos salen de una sola consulta agrupada por estado y prioridad.
        $conteos = $abiertos()->toBase()
            ->selectRaw(
                'estado, prioridad, count(*) as total,'
                .' sum(case when vencido then 1 else 0 end) as vencidos,'
                .' sum(case when not vencido and fecha_objetivo between ? and ? then 1 else 0 end) as por_vencer',
                [now(), now()->addDays(2)],
            )
            ->groupBy('estado', 'prioridad')
            ->get();

        $porEstado = $conteos->groupBy('estado')->map(fn ($filas): int => (int) $filas->sum('total'));
        $porPrioridad = $conteos->whereNotNull('prioridad')->groupBy('prioridad')->map(fn ($filas): int => (int) $filas->sum('total'));

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
            'sinPrioridad' => (int) $conteos->whereNull('prioridad')->sum('total'),
            'vencidos' => (int) $conteos->sum('vencidos'),
            'porVencer' => (int) $conteos->sum('por_vencer'),
            'urgentes' => $abiertos()->with('categoria:id,nombre')->whereNotNull('fecha_objetivo')
                ->orderByDesc('vencido')->orderBy('fecha_objetivo')->limit(6)
                ->get(['id', 'categoria_id', 'estado', 'prioridad', 'vencido', 'fecha_objetivo']),
            'cuadrillas' => Cuadrilla::where('unidad_id', $usuario->unidad_id)->withCount('tecnicos')->orderBy('nombre')->get(),
        ];
    }
}
