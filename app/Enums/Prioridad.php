<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Prioridad: string implements HasLabel
{
    case Critica = 'critica';
    case Alta = 'alta';
    case Media = 'media';
    case Baja = 'baja';

    public function getLabel(): string
    {
        return match ($this) {
            self::Critica => 'Crítica',
            self::Alta => 'Alta',
            self::Media => 'Media',
            self::Baja => 'Baja',
        };
    }
}
