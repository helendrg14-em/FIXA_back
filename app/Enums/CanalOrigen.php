<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CanalOrigen: string implements HasLabel
{
    case AppMovil = 'app_movil';
    case Web = 'web';
    case Telefono = 'telefono';
    case Presencial = 'presencial';

    public function getLabel(): string
    {
        return match ($this) {
            self::AppMovil => 'App móvil',
            self::Web => 'Web',
            self::Telefono => 'Teléfono',
            self::Presencial => 'En ventanilla',
        };
    }

    /**
     * RN-02: lo registra un operador, así que la foto puede omitirse indicando el motivo.
     */
    public function esAsistido(): bool
    {
        return $this === self::Telefono || $this === self::Presencial;
    }
}
