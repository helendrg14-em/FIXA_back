<?php

namespace App\Categorias;

class CreadorResiduos extends CreadorCategoria
{
    protected function crearDefinicion(): DefinicionCategoria
    {
        return new DefinicionResiduos;
    }
}
