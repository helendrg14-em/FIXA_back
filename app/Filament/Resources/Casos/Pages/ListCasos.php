<?php

namespace App\Filament\Resources\Casos\Pages;

use App\Auth\ServicioAutenticacion;
use App\Enums\EstadoCaso;
use App\Models\Caso;
use App\Filament\Resources\Casos\CasoResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCasos extends ListRecords
{
    protected static string $resource = CasoResource::class;

    /** @var array<string, array{0: string, 1: list<EstadoCaso>|null}> */
    private const ETAPAS = [
        'abiertos' => ['Abiertos', null],
        'por_atender' => ['Por atender', [EstadoCaso::Recibida, EstadoCaso::EnRevision]],
        'en_curso' => ['En curso', [EstadoCaso::Asignada, EstadoCaso::Programada, EstadoCaso::EnAtencion]],
        'por_validar' => ['Por validar', [EstadoCaso::PendienteValidacion]],
        'cerrados' => ['Cerrados', [EstadoCaso::Cerrada]],
        'otros' => ['Rechazados y derivados', [EstadoCaso::Rechazada, EstadoCaso::Duplicada, EstadoCaso::Derivada]],
    ];

    public function getTabs(): array
    {
        // Consulta propia y no getEloquentQuery(): esa trae «casos.*» y el conteo de reportes,
        // y PostgreSQL no acepta esas columnas junto a GROUP BY estado.
        $conteos = Caso::query()->visiblesPara(ServicioAutenticacion::getInstance()->usuario())->toBase()
            ->select('estado')->selectRaw('count(*) as total')->groupBy('estado')->pluck('total', 'estado');

        $tabs = [];
        foreach (self::ETAPAS as $clave => [$nombre, $estados]) {
            $valores = $estados === null
                ? array_values(array_diff(array_column(EstadoCaso::cases(), 'value'), EstadoCaso::finales()))
                : array_map(fn (EstadoCaso $e): string => $e->value, $estados);

            $tabs[$clave] = Tab::make($nombre)
                ->badge((string) $conteos->only($valores)->sum())
                ->badgeColor($clave === 'por_validar' ? 'warning' : 'gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('estado', $valores));
        }

        return $tabs;
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'abiertos';
    }
}
