<?php

namespace Database\Seeders;

use App\Models\Cuadrilla;
use App\Models\UnidadResponsable;
use Illuminate\Database\Seeder;

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
