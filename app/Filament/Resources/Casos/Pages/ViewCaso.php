<?php

namespace App\Filament\Resources\Casos\Pages;

use App\Filament\Resources\Casos\AccionesCaso;
use App\Filament\Resources\Casos\CasoResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewCaso extends ViewRecord
{
    protected static string $resource = CasoResource::class;

    /** Acciones que se muestran como botón; las que cambian el rumbo del caso van en «Más». */
    private const PRINCIPALES = ['pasarARevision', 'prioridad', 'programar', 'aprobar', 'rechazarEvidencia'];

    public function getTitle(): string|Htmlable
    {
        return "Caso #{$this->getRecord()->getKey()}";
    }

    protected function getHeaderActions(): array
    {
        [$principales, $otras] = collect(AccionesCaso::todas())
            // Después de cada acción se vuelve a leer el caso, que la API ya cambió en la base.
            ->each(fn (Action $accion) => $accion->after(fn () => $this->getRecord()->refresh()))
            ->partition(fn (Action $accion): bool => in_array($accion->getName(), self::PRINCIPALES, true));

        return [
            ...$principales->values()->all(),
            ActionGroup::make($otras->values()->all())->label('Más')->button()->color('gray'),
        ];
    }
}
