<?php

namespace App\Categorias;

class DefinicionAlumbrado implements DefinicionCategoria
{
    public function tipos(): array
    {
        return [
            'luminaria_apagada' => 'Luminaria apagada',
            'luminaria_intermitente' => 'Luminaria que parpadea',
            CreadorCategoria::TIPO_OTRO => 'Otro',
        ];
    }

    public function reglasPropias(): array
    {
        return [
            'numero_luminarias' => ['required_unless:tipo_incidencia,'.CreadorCategoria::TIPO_OTRO, 'nullable', 'integer', 'min:1', 'max:200'],
            'codigo_aviso_cobranza' => ['nullable', 'string', 'max:30'],
        ];
    }
}
