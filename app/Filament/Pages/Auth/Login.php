<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Schema;
use SensitiveParameter;

/**
 * R01: login de funcionarios con correo y contraseña. Sin "Recordarme", porque la
 * sesion debe expirar a los 15 minutos de inactividad. Filament ya limita a 5 intentos
 * por minuto y rechaza las cuentas donde canAccessPanel() devuelve false.
 */
class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
            ]);
    }

    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'correo' => mb_strtolower(trim($data['email'])),
            'password' => $data['password'],
        ];
    }
}
