<?php

namespace App\Filament\Resources\Casos\Pages;

use App\Auth\ServicioAutenticacion;
use App\Filament\Pages\ConsultarSeguimiento;
use App\Filament\Pages\GestionarReportes;
use App\Filament\Resources\Casos\AccionesCaso;
use App\Filament\Resources\Casos\CasoResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

use function Filament\Support\original_request;

class ViewCaso extends ViewRecord
{
    protected static string $resource = CasoResource::class;

    /** Acciones que se muestran como botón; las que cambian el rumbo del caso van en «Más». */
    private const PRINCIPALES = ['pasarARevision', 'prioridad', 'programar', 'aprobar', 'rechazarEvidencia'];

    public function getTitle(): string|Htmlable
    {
        return "Caso #{$this->getRecord()->getKey()}";
    }

    public function getPageClasses(): array
    {
        return [...parent::getPageClasses(), 'fx-casos-detalle'];
    }

    public function getSubheading(): ?string
    {
        $caso = $this->getRecord();

        return implode(' · ', array_filter([$caso->categoria?->nombre, $caso->unidad?->nombre]));
    }

    public function getBreadcrumbs(): array
    {
        if (! ServicioAutenticacion::getInstance()->esAdministrador()) {
            return parent::getBreadcrumbs();
        }

        $seccion = $this->vieneDeReportes() ? GestionarReportes::class : ConsultarSeguimiento::class;

        return [$seccion::getUrl() => $seccion::getNavigationLabel(), 'Detalle'];
    }

    protected function getHeaderActions(): array
    {
        $esAdmin = ServicioAutenticacion::getInstance()->esAdministrador();
        $desdeReportes = $esAdmin && $this->vieneDeReportes();
        [$etiquetaVolver, $urlVolver] = match (true) {
            $desdeReportes => ['Volver a gestión de reportes', GestionarReportes::getUrl()],
            $esAdmin => ['Volver al seguimiento', ConsultarSeguimiento::getUrl()],
            default => ['Volver a los casos', CasoResource::getUrl('index', ['tab' => 'todos'])],
        };

        [$principales, $otras] = collect(AccionesCaso::todas())
            // Después de cada acción se vuelve a leer el caso, que la API ya cambió en la base.
            ->each(fn (Action $accion) => $accion->after(fn () => $this->getRecord()->refresh()))
            ->partition(fn (Action $accion): bool => in_array($accion->getName(), self::PRINCIPALES, true));

        return [
            Action::make('volverACasos')->label($etiquetaVolver)
                ->icon(Heroicon::OutlinedArrowLeft)->outlined()
                ->url($urlVolver),
            ...$principales->values()->all(),
            ActionGroup::make($otras->values()->all())->label('Más')->button()->color('gray'),
        ];
    }

    private function vieneDeReportes(): bool
    {
        return original_request()->routeIs(CasoResource::getRouteBaseName().'.view-reportes');
    }
}
