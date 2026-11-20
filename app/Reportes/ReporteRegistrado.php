<?php

namespace App\Reportes;

use App\Models\Reporte;
use SensitiveParameter;

final readonly class ReporteRegistrado
{
    public function __construct(
        public Reporte $reporte,
        #[SensitiveParameter] public ?string $clave,
    ) {}
}
