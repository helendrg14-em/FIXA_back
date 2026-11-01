<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoUsuario: string implements HasLabel
{
    case Administrador = 'administrador';
    case Supervisor = 'supervisor';
    case Tecnico = 'tecnico';
    // Version futura (R07): existe en la base de datos pero aun no entra al panel.
    case Operador = 'operador';

    public function getLabel(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Supervisor => 'Supervisor',
            self::Tecnico => 'Técnico',
            self::Operador => 'Operador de atención',
        };
    }

    public function puedeEntrarAlPanel(): bool
    {
        return $this !== self::Operador;
    }
}
