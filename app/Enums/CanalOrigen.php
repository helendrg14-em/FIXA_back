<?php

namespace App\Enums;

enum CanalOrigen: string
{
    case AppMovil = 'app_movil';
    case Web = 'web';
    case Telefono = 'telefono';
    case Presencial = 'presencial';

    /**
     * RN-02: lo registra un operador, así que la foto puede omitirse indicando el motivo.
     */
    public function esAsistido(): bool
    {
        return $this === self::Telefono || $this === self::Presencial;
    }
}
