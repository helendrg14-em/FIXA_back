<?php

namespace App\Filament\Widgets\Tecnico;

use App\Enums\EstadoCaso;
use App\Enums\TipoUsuario;
use App\Filament\Widgets\ResumenDeInicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ResumenTecnico extends ResumenDeInicio
{
    protected static TipoUsuario $perfil = TipoUsuario::Tecnico;

    protected string $view = 'tecnico.inicio';

    protected function getViewData(): array
    {
        $usuario = $this->usuario();
        $datos = [...$this->momento(), 'usuario' => $usuario, 'cuadrilla' => $usuario->cuadrilla?->loadMissing('unidad')];

        if (! $usuario->cuadrilla_id) {
            return $datos;
        }

        // Trabajo vigente de la cuadrilla: casos programados o que ya se están atendiendo.
        $agenda = DB::table('programaciones')
            ->join('casos', 'casos.id', '=', 'programaciones.caso_id')
            ->join('categorias', 'categorias.id', '=', 'casos.categoria_id')
            ->where('programaciones.cuadrilla_id', $usuario->cuadrilla_id)
            ->where('programaciones.vigente', true)
            ->whereIn('casos.estado', [EstadoCaso::Programada->value, EstadoCaso::EnAtencion->value])
            ->orderBy('programaciones.fecha_prevista')
            ->get(['casos.id', 'casos.estado', 'casos.prioridad', 'casos.vencido', 'categorias.nombre as categoria', 'programaciones.fecha_prevista'])
            ->map(fn (object $fila): object => (object) [
                ...(array) $fila,
                'estado' => EstadoCaso::from($fila->estado),
                'fecha_prevista' => Carbon::parse($fila->fecha_prevista)->startOfDay(),
            ]);

        return [
            ...$datos,
            'agenda' => $agenda->take(8),
            'totalAgenda' => $agenda->count(),
            'paraHoy' => $agenda->filter(fn (object $f): bool => $f->fecha_prevista->lte(today()))->count(),
            'estaSemana' => $agenda->filter(fn (object $f): bool => $f->fecha_prevista->lte(today()->addDays(7)))->count(),
            'enAtencion' => $agenda->filter(fn (object $f): bool => $f->estado === EstadoCaso::EnAtencion)->count(),
            'intervenciones' => DB::table('intervenciones')->where('tecnico_id', $usuario->id)->where('fecha', '>=', now()->subDays(30))->count(),
        ];
    }
}
