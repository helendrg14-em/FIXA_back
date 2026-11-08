<?php

namespace App\Filament\Pages\Auth;

use App\Auth\ServicioAutenticacion;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use SensitiveParameter;

/**
 *  login de funcionarios con correo y contraseña. Sin "Recordarme", porque la
 * sesion debe expirar a los 15 minutos de inactividad. Filament ya limita a 5 intentos
 * por minuto y rechaza las cuentas donde canAccessPanel() devuelve false.
 */
class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable
    {
        return 'Acceso de funcionarios';
    }

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
        return ServicioAutenticacion::getInstance()->credenciales($data['email'], $data['password']);
    }
}
