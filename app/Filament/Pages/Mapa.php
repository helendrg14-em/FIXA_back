<?php

namespace App\Filament\Pages;

use App\Auth\ServicioAutenticacion;
use App\Enums\EstadoCaso;
use App\Enums\TipoUsuario;
use App\Filament\Resources\Casos\CasoResource;
use App\Models\Caso;
use App\Models\Categoria;
use App\Models\Reporte;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Mapa extends Page
{
    private const MAXIMO = 2000;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Mapa';

    protected static ?string $title = 'Mapa de reportes';

    protected static ?int $navigationSort = 2;

    protected string $view = 'panel.mapa-ciudad';

    /** abiertos, cerrados o todos. */
    public string $estado = 'abiertos';

    public ?string $categoria = null;

    public static function canAccess(): bool
    {
        $usuario = ServicioAutenticacion::getInstance()->usuario();

        return $usuario?->esAdministrador()
            || ($usuario?->tipo === TipoUsuario::Supervisor && $usuario->unidad_id !== null);
    }

    /** Al cambiar un filtro, el mapa se vuelve a pintar sin recargar la página. */
    public function updated(): void
    {
        $this->dispatch('mapa-puntos', puntos: $this->puntos());
    }

    protected function getViewData(): array
    {
        return [
            'puntos' => $this->puntos(),
            'categorias' => Categoria::query()->orderBy('id')->get(['id', 'codigo', 'nombre']),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function puntos(): array
    {
        $delPrimerReporte = fn (string $expresion) => Reporte::query()->selectRaw($expresion)
            ->whereColumn('reportes.caso_id', 'casos.id')->orderBy('reportes.id')->limit(1);

        return Caso::query()
            ->visiblesPara(ServicioAutenticacion::getInstance()->usuario())
            ->with('categoria:id,codigo,nombre')
            ->when($this->estado === 'abiertos', fn ($q) => $q->whereNotIn('estado', EstadoCaso::finales()))
            ->when($this->estado === 'cerrados', fn ($q) => $q->whereIn('estado', EstadoCaso::finales()))
            ->when(filled($this->categoria), fn ($q) => $q->where('categoria_id', $this->categoria))
            ->select(['id', 'categoria_id', 'estado', 'prioridad', 'vencido'])
            ->addSelect(['lat' => $delPrimerReporte('ST_Y(ubicacion)'), 'lng' => $delPrimerReporte('ST_X(ubicacion)')])
            ->orderByDesc('id')
            ->limit(self::MAXIMO)
            ->get()
            ->filter(fn (Caso $caso): bool => $caso->lat !== null && $caso->lng !== null)
            ->map(fn (Caso $caso): array => [
                'id' => $caso->id,
                'lat' => (float) $caso->lat,
                'lng' => (float) $caso->lng,
                'categoria' => $caso->categoria?->nombre,
                'codigo' => $caso->categoria?->codigo?->value,
                'estado' => $caso->estado->getLabel(),
                'cerrado' => in_array($caso->estado->value, EstadoCaso::finales(), true),
                'prioridad' => $caso->prioridad?->getLabel(),
                'vencido' => (bool) $caso->vencido,
                'url' => CasoResource::getUrl('view', ['record' => $caso->id]),
            ])
            ->values()
            ->all();
    }
}
