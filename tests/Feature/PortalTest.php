<?php

namespace Tests\Feature;

use Tests\TestCase;

class PortalTest extends TestCase
{
    public function test_muestra_las_categorias_y_las_entidades_externas(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Baches y deterioro vial')
            ->assertSee('Fallas de alumbrado público')
            ->assertSee('Microbasurales y residuos')
            ->assertSee('CRE')
            ->assertSee('No es un canal oficial');
    }

    public function test_el_boton_de_descarga_no_funciona_sin_enlace(): void
    {
        config(['fixa.app_url' => null]);

        $this->get('/')->assertSee('estará disponible pronto')
            ->assertSee('Descargar la app para Android')
            ->assertSee('aria-disabled="true"', false);
    }

    public function test_muestra_el_boton_cuando_hay_enlace_de_descarga(): void
    {
        config(['fixa.app_url' => 'https://ejemplo.test/fixa.apk']);

        $this->get('/')->assertSee('Descargar la app para Android')->assertSee('https://ejemplo.test/fixa.apk');
    }
}
