<?php

namespace App\Api;

use RuntimeException;

/** Error de la API traducido a un mensaje que se puede mostrar al funcionario. */
final class ErrorApi extends RuntimeException
{
    public function __construct(string $mensaje, public readonly ?int $estadoHttp = null)
    {
        parent::__construct($mensaje);
    }
}
