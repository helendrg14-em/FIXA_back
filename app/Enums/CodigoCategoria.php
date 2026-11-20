<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

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
