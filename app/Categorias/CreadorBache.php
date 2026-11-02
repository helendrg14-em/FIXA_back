<?php

namespace App\Categorias;

class CreadorBache extends CreadorCategoria
{
    protected function crearDefinicion(): DefinicionCategoria
    {
        return new DefinicionBache;
    }
}
