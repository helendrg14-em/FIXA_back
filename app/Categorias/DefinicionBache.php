<?php

namespace App\Categorias;

class DefinicionBache implements DefinicionCategoria
{
    public function tipos(): array
    {
        return [
            'bache' => 'Bache',
            'hundimiento' => 'Hundimiento',
            'calle_deteriorada' => 'Calle deteriorada',
            CreadorCategoria::TIPO_OTRO => 'Otro',
        ];
    }

    public function reglasPropias(): array
    {
        return [];
    }
}
