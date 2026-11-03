<?php

namespace App\Categorias;

class DefinicionResiduos implements DefinicionCategoria
{
    public function tipos(): array
    {
        return [
            'basura_en_calle' => 'Basura en la calle',
            'basura_en_espacio_publico' => 'Basura en un espacio público',
            CreadorCategoria::TIPO_OTRO => 'Otro',
        ];
    }

    public function reglasPropias(): array
    {
        return [];
    }
}
