<?php

namespace App\Filament\Resources\Feriados\Pages;

use App\Filament\Resources\Feriados\FeriadoResource;
use App\Filament\Resources\Feriados\Widgets\CalendarioFeriados;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Livewire\Attributes\On;

class ManageFeriados extends ManageRecords
{
    protected static string $resource = FeriadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Agregar feriado')->modalHeading('Agregar feriado')->createAnother(false)
                ->fillForm(fn (array $arguments): array => ['fecha' => $arguments['fecha'] ?? null])
                ->after(fn () => $this->dispatch('feriados-actualizados')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [CalendarioFeriados::class];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    /** El calendario pide agregar un feriado en el día que se tocó. */
    #[On('crear-feriado')]
    public function crearFeriado(string $fecha): void
    {
        $this->mountAction('create', ['fecha' => $fecha]);
    }

    /** El calendario pide editar el feriado que se tocó. */
    #[On('editar-feriado')]
    public function editarFeriado(int $id): void
    {
        $this->mountTableAction('edit', (string) $id);
    }
}
