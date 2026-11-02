<?php

namespace App\Categorias;

class CreadorAlumbrado extends CreadorCategoria
{
    protected function crearDefinicion(): DefinicionCategoria
    {
        return new DefinicionAlumbrado;
    }
}
