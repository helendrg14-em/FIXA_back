<?php

namespace Database\Seeders;

use App\Models\Cuadrilla;
use App\Models\UnidadResponsable;
use Illuminate\Database\Seeder;

/**
 * Cuadrillas de demostracion: dos por unidad, una por turno. Necesita CatalogosSeeder
 * antes, porque cada cuadrilla pertenece a una unidad. Se puede ejecutar varias veces.
 */
class CuadrillasSeeder extends Seeder
{
    public function run(): void
    {
        foreach (UnidadResponsable::all() as $unidad) {
            foreach (['Mañana', 'Tarde'] as $turno) {
                Cuadrilla::firstOrCreate(
                    ['unidad_id' => $unidad->id, 'nombre' => "Cuadrilla {$unidad->sigla} {$turno}"],
                    ['turno' => $turno],
                );
            }
        }
    }
}
