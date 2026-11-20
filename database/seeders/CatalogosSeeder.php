<?php

namespace Database\Seeders;

use App\Enums\Prioridad;
use App\Models\Categoria;
use App\Models\Feriado;
use App\Models\UnidadResponsable;
use Illuminate\Database\Seeder;

class CatalogosSeeder extends Seeder
{
    public function run(): void
    {
        $unidades = [
            'SMOP' => 'Secretaría Municipal de Obras Públicas',
            'UOD-AP' => 'UOD Alumbrado Público',
            'EMACRUZ' => 'EMACRUZ',
        ];

        foreach ($unidades as $sigla => $nombre) {
            UnidadResponsable::firstOrCreate(['sigla' => $sigla], ['nombre' => $nombre]);
        }

        $categorias = [
            ['bache', 'Baches y deterioro vial', 'SMOP', 'Baches, hundimientos y calles deterioradas.'],
            ['alumbrado', 'Fallas de alumbrado público', 'UOD-AP', 'Luminarias apagadas o que fallan en la vía pública.'],
            ['residuos', 'Microbasurales y residuos', 'EMACRUZ', 'Acumulación de basura en calles y espacios públicos.'],
        ];

        foreach ($categorias as [$codigo, $nombre, $sigla, $descripcion]) {
            Categoria::firstOrCreate(['codigo' => $codigo], [
                'nombre' => $nombre,
                'unidad_id' => UnidadResponsable::where('sigla', $sigla)->value('id'),
                'descripcion' => $descripcion,
                'activa' => true,
                // Menos que una cuadra, para no unir baches de esquinas distintas. Es ajustable en el panel.
                'radio_cercania_m' => 50,
            ]);
        }

        $this->crearPlazosIniciales();
        $this->crearFeriados();
    }

    private function crearPlazosIniciales(): void
    {
        $plazos = [
            [Prioridad::Critica, 'plazo_horas', 24],
            [Prioridad::Alta, 'plazo_horas', 48],
            [Prioridad::Media, 'plazo_dias_habiles', 5],
            [Prioridad::Baja, 'plazo_dias_habiles', 5],
        ];

        foreach (Categoria::all() as $categoria) {
            foreach ($plazos as [$prioridad, $medida, $valor]) {
                $categoria->metasAtencion()->firstOrCreate(
                    ['prioridad' => $prioridad->value, 'vigente_hasta' => null],
                    [$medida => $valor, 'vigente_desde' => today()],
                );
            }
        }
    }

    private function crearFeriados(): void
    {
        $fuente = 'Calendario 2026 según DS 5521 (Ministerio de Turismo Sostenible y Culturas)';

        foreach (['2026-11-02' => 'Día de Todos los Difuntos', '2026-12-25' => 'Navidad'] as $fecha => $nombre) {
            Feriado::firstOrCreate(['fecha' => $fecha], ['nombre' => $nombre, 'fuente' => $fuente]);
        }
    }
}
