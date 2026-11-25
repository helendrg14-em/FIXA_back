<?php

namespace App\Filament\Resources\Casos\Pages;

use App\Auth\ServicioAutenticacion;
use App\Enums\EstadoCaso;
use App\Filament\Resources\Casos\CasoResource;
use App\Models\Caso;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pestañas por etapa en lugar de un filtro de estado: el supervisor ve de un vistazo qué le
 * toca hacer y cuántos casos hay en cada etapa, sin abrir el panel de filtros.
 */
class ListCasos extends ListRecords
{
    protected static string $resource = CasoResource::class;

    protected static ?string $title = 'Gestionar casos';

    /** @var array<string, array{0: string, 1: list<EstadoCaso>}> */
    private const ETAPAS = [
        'por_atender' => ['Por atender', [EstadoCaso::Recibida, EstadoCaso::EnRevision]],
        'en_curso' => ['En curso', [EstadoCaso::Asignada, EstadoCaso::Programada, EstadoCaso::EnAtencion]],
        'por_validar' => ['Por validar', [EstadoCaso::PendienteValidacion]],
        'cerrados' => ['Cerrados', [EstadoCaso::Cerrada]],
        'otros' => ['Otros cierres', [EstadoCaso::Rechazada, EstadoCaso::Duplicada, EstadoCaso::Derivada]],
    ];

    public function getPageClasses(): array
    {
        return [...parent::getPageClasses(), 'fx-casos-bandeja'];
    }

    public function getSubheading(): ?string
    {
        return ServicioAutenticacion::getInstance()->esAdministrador()
            ? 'Estado, prioridad y plazos de los casos de todas las unidades.'
            : 'Revisa y gestiona los casos de tu unidad.';
    }

    public function getTabs(): array
    {
        // Consulta propia y no getEloquentQuery(): esa trae «casos.*» y el conteo de reportes,
        // y PostgreSQL no acepta esas columnas junto a GROUP BY estado.
        $conteos = Caso::query()->visiblesPara(ServicioAutenticacion::getInstance()->usuario())->toBase()
            ->select('estado')->selectRaw('count(*) as total')->groupBy('estado')->pluck('total', 'estado');

        $abiertos = array_values(array_diff(array_column(EstadoCaso::cases(), 'value'), EstadoCaso::finales()));
        $tabs = [
            'abiertos' => Tab::make('Abiertos')->badge((string) $conteos->only($abiertos)->sum())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('estado', $abiertos)),
            'todos' => Tab::make('Todos')->badge((string) $conteos->sum()),
        ];

        foreach (self::ETAPAS as $clave => [$nombre, $estados]) {
            $valores = array_column($estados, 'value');

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
