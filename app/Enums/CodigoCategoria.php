<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Identificador estable de cada categoría (RN-01). El nombre visible se puede editar;
 * este código no cambia, y Factory Method lo usa para elegir el creador de la categoría.
 */
enum CodigoCategoria: string implements HasLabel
{
    case Bache = 'bache';
    case Alumbrado = 'alumbrado';
    case Residuos = 'residuos';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bache => 'Baches y deterioro vial',
            self::Alumbrado => 'Alumbrado público',
            self::Residuos => 'Microbasurales y residuos',
        };
    }
}
