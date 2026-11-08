<?php

namespace App\Categorias;

use App\Enums\CodigoCategoria;
use Illuminate\Validation\Rule;

/**
 * Factory Method: el formulario común (ubicación, tipo y descripción) vive aquí, y cada
 * subclase decide con crearDefinicion() qué categoría se usa. Una categoría nueva solo
 * agrega su creador y su definición; este código no cambia.
 */
abstract class CreadorCategoria
{
    public const TIPO_OTRO = 'otro';

    abstract protected function crearDefinicion(): DefinicionCategoria;

    public static function para(CodigoCategoria $codigo): self
    {
        return match ($codigo) {
            CodigoCategoria::Bache => new CreadorBache,
            CodigoCategoria::Alumbrado => new CreadorAlumbrado,
            CodigoCategoria::Residuos => new CreadorResiduos,
        };
    }

    /**
     * @return array<string, string>
     */
    public function tipos(): array
    {
        return $this->crearDefinicion()->tipos();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function reglas(): array
    {
        $definicion = $this->crearDefinicion();

        return [
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
            'tipo_incidencia' => ['required', Rule::in(array_keys($definicion->tipos()))],
            'descripcion' => ['required', 'string', 'max:1000'],
            // Un dato de otra categoría se rechaza, salvo que la definición lo pida.
            'numero_luminarias' => ['prohibited'],
            'codigo_aviso_cobranza' => ['prohibited'],
            ...$definicion->reglasPropias(),
        ];
    }
}
