<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Los 15 distritos municipales de Santa Cruz de la Sierra (12 urbanos y 3 rurales) con
 * su numero y nombre. Los LIMITES SON APROXIMADOS, para la demo: cada distrito es un
 * sector de anillo alrededor de la plaza 24 de Septiembre, ubicado en la zona que le
 * corresponde segun la descripcion de las subalcaldias:
 *
 *  - Centro hasta ~2do anillo: D11 (Central).
 *  - Del 2do al 4to anillo: D1 oeste, D2 noreste, D3 este y D4 sur.
 *  - Del 4to anillo hacia afuera: D5, D6, D7, D8, D9, D12 y D10, girando desde el norte.
 *  - Rurales mas lejos: D15 al norte, D13 al este y D14 al sureste.
 *
 * Los sectores comparten exactamente sus bordes, asi que no hay superposiciones. Al
 * oeste del 4to anillo no hay distrito rural porque el rio Pirai es el limite municipal.
 * Reemplazar por los poligonos oficiales del GAMSC cuando el equipo los tenga.
 * Se puede ejecutar varias veces: actualiza los distritos que ya existen.
 */
class DistritosSeeder extends Seeder
{
    // Plaza 24 de Septiembre (longitud, latitud).
    private const CENTRO = [-63.1821, -17.7833];

    // Radios en grados (1 grado ~ 111 km): ~2do anillo, ~4to anillo, borde urbano, borde rural.
    private const RADIO_CENTRAL = 0.018;

    private const RADIO_INTERMEDIO = 0.042;

    private const RADIO_URBANO = 0.10;

    private const RADIO_RURAL = 0.18;

    // Separacion entre puntos de los arcos. Todos los rumbos de abajo son multiplos de
    // este paso, para que dos distritos vecinos generen exactamente los mismos vertices.
    private const PASO_GRADOS = 2.5;

    public function run(): void
    {
        foreach ($this->distritos() as $numero => [$nombre, $anillo]) {
            $geojson = json_encode(['type' => 'Polygon', 'coordinates' => [$anillo]]);
            $limite = DB::raw("ST_SetSRID(ST_GeomFromGeoJSON('{$geojson}'), 4326)");

            DB::table('distritos')->updateOrInsert(
                ['numero' => $numero],
                ['nombre' => $nombre, 'limite' => $limite],
            );
        }
    }

    /**
     * Numero => [nombre, anillo de coordenadas]. Los rumbos se miden en grados desde el
     * norte, en sentido horario (90 = este, 180 = sur, 270 = oeste).
     *
     * @return array<int, array{string, list<array{float, float}>}>
     */
    public function distritos(): array
    {
        $central = self::RADIO_CENTRAL;
        $intermedio = self::RADIO_INTERMEDIO;
        $urbano = self::RADIO_URBANO;
        $rural = self::RADIO_RURAL;

        $sectores = [
            // Entre el 2do y el 4to anillo.
            1 => ['Piraí', $central, $intermedio, 225, 360],
            2 => ['Noreste', $central, $intermedio, 0, 90],
            3 => ['Estación Argentina', $central, $intermedio, 90, 157.5],
            4 => ['Del Pari', $central, $intermedio, 157.5, 225],
            // Del 4to anillo hacia afuera.
            5 => ['Norte', $intermedio, $urbano, 270, 390],
            6 => ['Carretera a Cotoca', $intermedio, $urbano, 30, 75],
            7 => ['Villa Primero de Mayo', $intermedio, $urbano, 75, 120],
            8 => ['Plan 3000', $intermedio, $urbano, 120, 160],
            9 => ['Palmasola', $intermedio, $urbano, 160, 195],
            10 => ['El Bajío', $intermedio, $urbano, 225, 270],
            12 => ['Nuevo Palmar', $intermedio, $urbano, 195, 225],
            // Rurales.
            13 => ['Palmar del Oratorio', $urbano, $rural, 60, 130],
            14 => ['Paurito', $urbano, $rural, 130, 200],
            15 => ['Montero Hoyo', $urbano, $rural, 330, 420],
        ];

        $distritos = [11 => ['Central', $this->cerrar($this->arco($central, 0, 360))]];

        foreach ($sectores as $numero => [$nombre, $radioInterior, $radioExterior, $desde, $hasta]) {
            $anillo = array_merge(
                $this->arco($radioExterior, $desde, $hasta),
                array_reverse($this->arco($radioInterior, $desde, $hasta)),
            );

            $distritos[$numero] = [$nombre, $this->cerrar($anillo)];
        }

        ksort($distritos);

        return $distritos;
    }

    /**
     * Puntos de un arco de circunferencia entre dos rumbos, ambos incluidos.
     *
     * @return list<array{float, float}>
     */
    private function arco(float $radio, float $desde, float $hasta): array
    {
        [$lng, $lat] = self::CENTRO;
        $puntos = [];

        for ($rumbo = $desde; $rumbo <= $hasta + 1e-9; $rumbo += self::PASO_GRADOS) {
            $radianes = deg2rad(fmod($rumbo, 360));
            $puntos[] = [
                round($lng + $radio * sin($radianes), 6),
                round($lat + $radio * cos($radianes), 6),
            ];
        }

        return $puntos;
    }

    /**
     * Quita un punto final repetido y vuelve a cerrar el anillo con el primero, como exige GeoJSON.
     *
     * @param  list<array{float, float}>  $puntos
     * @return list<array{float, float}>
     */
    private function cerrar(array $puntos): array
    {
        if (end($puntos) == $puntos[0]) {
            array_pop($puntos);
        }

        $puntos[] = $puntos[0];

        return $puntos;
    }
}
