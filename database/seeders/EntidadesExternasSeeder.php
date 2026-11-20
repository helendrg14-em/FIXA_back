<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EntidadesExternasSeeder extends Seeder
{
    public function run(): void
    {
        $entidades = [
            'CRE' => 'cre.com.bo',
            'ABC' => 'abc.gob.bo',
            'Gobernación de Santa Cruz' => 'santacruz.gob.bo',
        ];

        foreach ($entidades as $nombre => $contacto) {
            if (DB::table('entidades_externas')->where('nombre', $nombre)->doesntExist()) {
                DB::table('entidades_externas')->insert(['nombre' => $nombre, 'contacto' => $contacto]);
            }
        }
    }
}
