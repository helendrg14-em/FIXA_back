<?php

namespace App\Filament\Resources\Usuarios\Pages;

use App\Filament\Resources\Usuarios\UsuarioResource;
use Filament\Resources\Pages\EditRecord;

class EditUsuario extends EditRecord
{
    protected static string $resource = UsuarioResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return UsuarioResource::limpiarPorPerfil($data, $data['tipo'] ?? $this->getRecord()->tipo);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
