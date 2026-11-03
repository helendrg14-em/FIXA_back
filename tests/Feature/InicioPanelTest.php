<?php

namespace Tests\Feature;

use App\Enums\EstadoCaso;
use App\Enums\TipoUsuario;
use App\Models\Caso;
use App\Models\Categoria;
use App\Models\Cuadrilla;
use App\Models\UnidadResponsable;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\EsquemaDePrueba;
use Tests\TestCase;

class InicioPanelTest extends TestCase
{
    use EsquemaDePrueba;

    private UnidadResponsable $unidad;

    private Cuadrilla $cuadrilla;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->unidad = UnidadResponsable::create(['nombre' => 'Obras Públicas', 'sigla' => 'SMOP']);
        $this->cuadrilla = Cuadrilla::create(['unidad_id' => $this->unidad->id, 'nombre' => 'Cuadrilla Norte', 'turno' => 'Mañana']);
        $categoria = Categoria::forceCreate([
            'codigo' => 'bache', 'unidad_id' => $this->unidad->id, 'nombre' => 'Baches',
            'descripcion' => 'Baches', 'radio_cercania_m' => 30,
        ]);
        $caso = Caso::forceCreate([
            'categoria_id' => $categoria->id, 'unidad_id' => $this->unidad->id,
            'estado' => EstadoCaso::Programada, 'prioridad' => 'alta', 'vencido' => true, 'fecha_objetivo' => now()->subDay(),
        ]);
        DB::table('programaciones')->insert(['caso_id' => $caso->id, 'cuadrilla_id' => $this->cuadrilla->id, 'fecha_prevista' => today()]);
    }

    public function test_el_administrador_ve_su_resumen(): void
    {
        $this->usuario(TipoUsuario::Tecnico, ['nombres' => 'Luis', 'apellidos' => 'Vaca', 'debe_registrar_password' => true]);
        $this->actingAs($this->usuario(datos: ['nombres' => 'Ana']));

        $this->get(Filament::getUrl())->assertOk()
            ->assertSee('Ana')
            ->assertSee('Esperando crear su contraseña')
            ->assertSee('Luis Vaca')
            ->assertDontSee('Recorrido de los casos');
    }

    public function test_el_supervisor_ve_los_casos_de_su_unidad(): void
    {
        $this->actingAs($this->usuario(TipoUsuario::Supervisor, ['unidad_id' => $this->unidad->id]));

        $this->get(Filament::getUrl())->assertOk()
            ->assertSee('Obras Públicas')
            ->assertSee('Recorrido de los casos')
            ->assertSee('Fuera de plazo')
            ->assertSee('Cuadrilla Norte')
            ->assertDontSee('Esperando crear su contraseña');
    }

    public function test_el_supervisor_sin_unidad_ve_un_aviso(): void
    {
        $this->actingAs($this->usuario(TipoUsuario::Supervisor));

        $this->get(Filament::getUrl())->assertOk()->assertSee('Tu cuenta aún no tiene unidad');
    }

    public function test_el_tecnico_ve_la_agenda_de_su_cuadrilla(): void
    {
        $this->actingAs($this->usuario(TipoUsuario::Tecnico, ['cuadrilla_id' => $this->cuadrilla->id]));

        $this->get(Filament::getUrl())->assertOk()
            ->assertSee('Agenda de la cuadrilla')
            ->assertSee('Baches')
            ->assertSee('Hoy')
            ->assertDontSee('Recorrido de los casos');
    }
}
