<?php

namespace App\Reportes;

use Illuminate\Support\Facades\DB;

final class CodigoSeguimiento
{
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
