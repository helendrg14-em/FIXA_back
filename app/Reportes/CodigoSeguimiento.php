<?php

namespace App\Reportes;

use Illuminate\Support\Facades\DB;

/**
 * Código visible y guardado del reporte: FX-AAAA-000123, correlativo por gestión. Ser
 * secuencial permite llevar el registro en orden; la consulta pública igual exige la clave.
 */
final class CodigoSeguimiento
{
    /**
     * Debe llamarse dentro de la transacción del reporte: la fila queda bloqueada hasta
     * confirmar, así dos registros no reciben el mismo número y un fallo no deja saltos.
     */
    public static function siguiente(): string
    {
        $anio = now(config('fixa.zona_horaria'))->year;

        $fila = DB::selectOne(
            'INSERT INTO contadores_codigo (anio, ultimo) VALUES (?, 1)
             ON CONFLICT (anio) DO UPDATE SET ultimo = contadores_codigo.ultimo + 1
             RETURNING ultimo',
            [$anio],
        );

        return sprintf('FX-%d-%06d', $anio, $fila->ultimo);
    }
}
