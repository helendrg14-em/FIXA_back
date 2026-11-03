<?php

namespace App\Categorias;

/**
 * Producto de Factory Method: lo que cambia entre una categoría y otra al reportar.
 */
interface DefinicionCategoria
{
    /**
     * Tipos de incidencia que el vecino puede elegir, como valor => texto visible.
     *
     * @return array<string, string>
     */
    public function tipos(): array;

    /**
     * Reglas de los datos que solo pide esta categoría.
     *
     * @return array<string, array<int, mixed>>
     */
    public function reglasPropias(): array;
}
