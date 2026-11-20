<?php

namespace App\Filament\Pages\Auth;

use App\Auth\ServicioAutenticacion;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use SensitiveParameter;

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

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->belowContent(Schema::end([
                new HtmlString(Blade::render(
                    '<x-filament::link :href="$url">¿Olvidaste tu contraseña?</x-filament::link>',
                    ['url' => route('cuenta.recuperar')],
                )),
            ]));
    }

    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return ServicioAutenticacion::getInstance()->credenciales($data['email'], $data['password']);
    }
}
