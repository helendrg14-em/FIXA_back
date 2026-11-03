<?php

namespace App\Filament\Resources\Feriados\Pages;

use App\Filament\Resources\Feriados\FeriadoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFeriados extends ManageRecords
{
    protected static string $resource = FeriadoResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Agregar feriado')->createAnother(false)];
    }
}
