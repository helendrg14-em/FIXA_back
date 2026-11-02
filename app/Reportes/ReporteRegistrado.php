<?php

namespace App\Reportes;

use App\Models\Reporte;
use SensitiveParameter;

/**
 * Resultado de registrar un reporte. La clave solo existe aquí, sin cifrar, para mostrarla
 * una vez al vecino. Es null cuando la solicitud se reenvió y el reporte ya existía.
 */
final readonly class ReporteRegistrado
{
    public function __construct(
        public Reporte $reporte,
        #[SensitiveParameter] public ?string $clave,
    ) {}
}
