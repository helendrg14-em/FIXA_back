<?php

namespace Tests\Feature;

use App\Enums\EstadoCaso;
use App\Enums\TipoUsuario;
use App\Filament\Pages\GestionarIntervencion;
use App\Filament\Resources\Casos\Pages\ListCasos;
use App\Models\Caso;
use App\Models\Categoria;
use App\Models\Cuadrilla;
use App\Models\Programacion;
use App\Models\Reporte;
use App\Models\UnidadResponsable;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\EsquemaDePrueba;
use Tests\TestCase;

/**
 * Pantalla «Gestionar intervención» del técnico (R10) y los nombres del diagrama de casos de uso
 * en el panel. La API de C# se simula con Http::fake.
 */
class GestionarIntervencionTest extends TestCase
{
    use EsquemaDePrueba;

    private const API = 'https://api.fixa.test';

    private User $tecnico;

    private Caso $programado;

    private Caso $enAtencion;

    private Caso $deOtraCuadrilla;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        config(['services.fixa_api.url' => self::API, 'services.fixa_api.jwt_clave' => 'clave-de-pruebas-de-al-menos-32-caracteres']);

        $vialidad = UnidadResponsable::create(['nombre' => 'Vialidad', 'sigla' => 'VIA']);
        $bache = Categoria::forceCreate(['codigo' => 'bache', 'unidad_id' => $vialidad->id, 'nombre' => 'Baches', 'descripcion' => 'B', 'radio_cercania_m' => 30]);
        $propia = Cuadrilla::forceCreate(['unidad_id' => $vialidad->id, 'nombre' => 'Cuadrilla A', 'turno' => 'Mañana']);
        $otra = Cuadrilla::forceCreate(['unidad_id' => $vialidad->id, 'nombre' => 'Cuadrilla B', 'turno' => 'Tarde']);
        $this->tecnico = $this->usuario(TipoUsuario::Tecnico, ['cuadrilla_id' => $propia->id]);

        $this->programado = $this->casoProgramado($bache, $propia, EstadoCaso::Programada, 'Av. Busch y 3er anillo');
        $this->enAtencion = $this->casoProgramado($bache, $propia, EstadoCaso::EnAtencion, 'Calle Sucre 120');
        $this->deOtraCuadrilla = $this->casoProgramado($bache, $otra, EstadoCaso::Programada, 'Calle Junín 45');
    }

    private function casoProgramado(Categoria $categoria, Cuadrilla $cuadrilla, EstadoCaso $estado, string $direccion): Caso
    {
        $caso = Caso::forceCreate(['categoria_id' => $categoria->id, 'unidad_id' => $categoria->unidad_id, 'estado' => $estado]);
        Programacion::forceCreate(['caso_id' => $caso->id, 'cuadrilla_id' => $cuadrilla->id, 'fecha_prevista' => today(), 'vigente' => true]);
        Reporte::forceCreate([
            'caso_id' => $caso->id, 'distrito_id' => 1, 'codigo' => 'FX-2026-00000'.$caso->id, 'clave_hash' => 'x',
            'descripcion' => 'Bache profundo', 'tipo_incidencia' => 'bache', 'canal_origen' => 'app_movil',
            'ubicacion' => 'POINT(-63.18 -17.78)', 'direccion' => $direccion, 'clave_idempotencia' => 'clave-'.$caso->id,
        ]);

        return $caso;
    }

    public function test_solo_el_tecnico_entra_a_gestionar_intervencion(): void
    {
        $this->actingAs($this->tecnico);
        $this->assertTrue(GestionarIntervencion::canAccess());

        $this->actingAs($this->usuario(TipoUsuario::Supervisor));
        $this->assertFalse(GestionarIntervencion::canAccess());

        $this->actingAs($this->usuario());
        $this->assertFalse(GestionarIntervencion::canAccess());
    }

    public function test_el_tecnico_ve_solo_las_ordenes_de_su_cuadrilla(): void
    {
        $this->actingAs($this->tecnico);

        Livewire::test(GestionarIntervencion::class)
            ->assertCanSeeTableRecords([$this->programado, $this->enAtencion])
            ->assertCanNotSeeTableRecords([$this->deOtraCuadrilla])
            ->assertSee('Av. Busch y 3er anillo')
            ->assertTableActionVisible('iniciar', $this->programado)
            ->assertTableActionHidden('iniciar', $this->enAtencion)
            ->assertTableActionVisible('registrar', $this->enAtencion)
            ->assertTableActionHidden('registrar', $this->programado);
    }

    public function test_iniciar_la_atencion_va_por_la_api(): void
    {
        Http::fake([self::API.'/*' => Http::response(null, 204)]);
        $this->actingAs($this->tecnico);

        Livewire::test(GestionarIntervencion::class)
            ->callTableAction('iniciar', $this->programado)
            ->assertNotified('Atención iniciada. Cuando termines, registra la intervención.');

        Http::assertSent(fn (Request $r): bool => $r->method() === 'POST'
            && $r->url() === self::API."/api/v1/panel/ordenes/{$this->programado->id}/inicio");
    }

    public function test_registrar_la_intervencion_envia_el_trabajo_y_la_foto_a_la_api(): void
    {
        Http::fake([self::API.'/*' => Http::response(null, 204)]);
        $this->actingAs($this->tecnico);

        Livewire::test(GestionarIntervencion::class)
            ->callTableAction('registrar', $this->enAtencion, data: [
                'trabajo' => 'Se rellenó el bache con asfalto en frío.',
                'tipo' => 'fotografia',
                'fotos' => [UploadedFile::fake()->image('despues.jpg')],
            ])
            ->assertHasNoTableActionErrors()
            ->assertNotified('Intervención enviada. El supervisor la revisará para cerrar el caso.');

        Http::assertSent(fn (Request $r): bool => $r->url() === self::API."/api/v1/panel/ordenes/{$this->enAtencion->id}/intervenciones"
            && $r->isMultipart()
            && collect($r->data())->contains(fn ($campo) => $campo['name'] === 'tipoEvidencia' && $campo['contents'] === 'fotografia')
            && collect($r->data())->contains(fn ($campo) => $campo['name'] === 'fotos'));
    }

    public function test_el_acta_tecnica_exige_explicar_por_que_y_sin_foto_no_se_envia(): void
    {
        Http::fake();
        $this->actingAs($this->tecnico);

        Livewire::test(GestionarIntervencion::class)
            ->callTableAction('registrar', $this->enAtencion, data: ['trabajo' => 'Reparación del cableado subterráneo.', 'tipo' => 'acta_tecnica'])
            ->assertHasTableActionErrors(['descripcion' => 'required', 'fotos' => 'required']);

        Http::assertNothingSent();
    }

    public function test_el_panel_usa_los_nombres_del_diagrama_de_casos_de_uso(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Iniciar sesión');

        $this->actingAs($this->usuario());
        $this->get('/admin')->assertOk()
            ->assertSee('Gestionar usuarios')
            ->assertSee('Gestionar categorías')
            ->assertSee('Gestionar casos')
            ->assertSee('Ver mapa')
            ->assertSee('Gestionar reportes')
            ->assertSee('Cerrar sesión');
    }

    public function test_el_tecnico_ve_gestionar_intervencion_en_el_menu(): void
    {
        $this->actingAs($this->tecnico);

        $this->get('/admin')->assertOk()->assertSee('Gestionar intervención');
    }

    public function test_consultar_seguimiento_busca_el_caso_por_el_codigo_del_vecino(): void
    {
        $this->actingAs($this->usuario());

        Livewire::test(ListCasos::class)
            ->searchTable('fx-2026-00000'.$this->enAtencion->id)
            ->assertCanSeeTableRecords([$this->enAtencion])
            ->assertCanNotSeeTableRecords([$this->programado])
            ->assertSee('Consultar seguimiento');
    }
}
