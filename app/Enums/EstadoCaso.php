<?php

namespace App\Enums;

/**
 * Estados de la máquina de estados de RN-03.
 */
enum EstadoCaso: string
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
}
