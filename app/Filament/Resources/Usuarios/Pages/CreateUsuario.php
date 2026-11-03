<?php

namespace App\Filament\Resources\Usuarios\Pages;

use App\Filament\Resources\Usuarios\UsuarioResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateUsuario extends CreateRecord
{
    protected static string $resource = UsuarioResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return 'Crear cuenta';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = UsuarioResource::limpiarPorPerfil($data, $data['tipo']);

        return [
            ...$data,
            'activo' => true,
            'debe_registrar_password' => true,
            // Nadie conoce esta contraseña: el funcionario crea la suya con el enlace.
            'password_hash' => Str::random(64),
        ];
    }

    protected function afterCreate(): void
    {
        UsuarioResource::enviarEnlace($this->getRecord());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
