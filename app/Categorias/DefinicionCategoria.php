<?php

namespace App\Categorias;

/**
 * Producto de Factory Method: lo que cambia entre una categoría y otra al reportar.
 */
interface DefinicionCategoria
{
    public function tipos(): array;

    public function reglasPropias(): array;
}
