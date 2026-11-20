<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Estados de la máquina de estados de RN-03.
 */
enum EstadoCaso: string implements HasLabel
{
    case Recibida = 'recibida';
    case EnRevision = 'en_revision';
    case Asignada = 'asignada';
    case Programada = 'programada';
    case EnAtencion = 'en_atencion';
    case PendienteValidacion = 'pendiente_validacion';
    case Cerrada = 'cerrada';
    case Rechazada = 'rechazada';
    case Duplicada = 'duplicada';
    case Derivada = 'derivada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Recibida => 'Recibida',
            self::EnRevision => 'En revisión',
            self::Asignada => 'Asignada',
            self::Programada => 'Programada',
            self::EnAtencion => 'En atención',
            self::PendienteValidacion => 'Pendiente de validación',
            self::Cerrada => 'Cerrada',
            self::Rechazada => 'Rechazada',
            self::Duplicada => 'Duplicada',
            self::Derivada => 'Derivada',
        };
    }

    public static function finales(): array
    {
        return [self::Cerrada->value, self::Rechazada->value, self::Duplicada->value, self::Derivada->value];
    }
}
