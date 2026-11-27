<?php

namespace Tests\Feature;

use App\Enums\CanalOrigen;
use App\Enums\EstadoCaso;
use App\Enums\TipoUsuario;
use App\Filament\Pages\ConsultarSeguimiento;
use App\Filament\Pages\ControlIntervenciones;
use App\Filament\Pages\GestionarReportes;
use App\Filament\Pages\NuevoReporte;
use App\Filament\Resources\Casos\CasoResource;
use App\Filament\Resources\Casos\Pages\ViewCaso;
use App\Models\Caso;
use App\Models\Categoria;
use App\Models\Cuadrilla;
use App\Models\Reporte;
use App\Models\UnidadResponsable;
use Dom\HTMLDocument;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationManager;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\EsquemaDePrueba;
use Tests\TestCase;

class OperacionesAdminTest extends TestCase
{
    use EsquemaDePrueba;

    private const API = 'https://api.fixa.test';

    private UnidadResponsable $unidad;

    private Categoria $categoria;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->unidad = UnidadResponsable::create(['nombre' => 'Vialidad', 'sigla' => 'VIA']);
        $this->categoria = Categoria::forceCreate([
            'unidad_id' => $this->unidad->id, 'codigo' => 'bache', 'nombre' => 'Baches',
            'descripcion' => 'Deterioro vial', 'radio_cercania_m' => 50,
        ]);
        config(['services.fixa_api.url' => self::API, 'services.fixa_api.jwt_clave' => str_repeat('a', 40)]);
    }

    public function test_las_nuevas_pantallas_son_del_administrador_y_seguimiento_tambien_del_supervisor(): void
    {
        $this->actingAs($this->usuario());
        $inicio = $this->get(Filament::getUrl())->assertOk();
        $paginas = [GestionarReportes::class, ConsultarSeguimiento::class, ControlIntervenciones::class];

        foreach ($paginas as $pagina) {
            $inicio->assertSee($pagina::getUrl());
            $this->get($pagina::getUrl())->assertOk();
        }

        foreach ([TipoUsuario::Supervisor, TipoUsuario::Tecnico] as $perfil) {
            $this->flushSession();
            $this->app->forgetInstance(NavigationManager::class);
            $this->actingAs($this->usuario($perfil));
            $inicio = $this->get(Filament::getUrl())->assertOk();
            foreach ($paginas as $pagina) {
                // Consultar seguimiento también es del supervisor (diagrama de casos de uso).
                if ($pagina === ConsultarSeguimiento::class && $perfil === TipoUsuario::Supervisor) {
                    $this->get($pagina::getUrl())->assertOk();

                    continue;
                }
                $inicio->assertDontSee($pagina::getUrl());
                $this->get($pagina::getUrl())->assertForbidden();
            }
        }
    }

    public function test_reportes_permite_filtrar_y_abrir_el_reporte_en_su_caso(): void
    {
        $this->actingAs($this->usuario());
        $caso = $this->caso(EstadoCaso::Recibida);
        $telefono = $this->reporte($caso, 'FX-2026-000101', CanalOrigen::Telefono);
        $app = $this->reporte($caso, 'FX-2026-000102', CanalOrigen::AppMovil);
        $url = CasoResource::getUrl('view-reportes', ['record' => $caso]);

        Livewire::test(GestionarReportes::class)
            ->assertSee(NuevoReporte::getUrl())
            ->assertCanSeeTableRecords([$telefono, $app])
            ->assertSee($url.'#reporte-'.$telefono->id)
            ->filterTable('canal_origen', CanalOrigen::Telefono->value)
            ->assertCanSeeTableRecords([$telefono])
            ->assertCanNotSeeTableRecords([$app]);

        $respuesta = $this->get($url)->assertOk()
            ->assertSee('id="reporte-'.$telefono->id.'"', escape: false)
            ->assertDontSee($telefono->clave_hash);

        $documento = HTMLDocument::createFromString($respuesta->getContent(), LIBXML_NOERROR);
        $opcionesActivas = $documento->querySelectorAll('.fi-sidebar-item-btn[aria-current="page"]');
        $this->assertCount(1, $opcionesActivas);
        $this->assertSame(GestionarReportes::getUrl(), $opcionesActivas->item(0)->getAttribute('href'));

        $volver = $documento->querySelector('.fi-header-actions-ctn a[href="'.GestionarReportes::getUrl().'"]');
        $this->assertNotNull($volver);
        $this->assertSame('Volver a gestión de reportes', trim($volver->textContent));
    }

    public function test_seguimiento_encuentra_casos_finalizados_por_codigo_y_numero(): void
    {
        Http::fake([self::API.'/*' => Http::response(null, 204)]);
        $this->actingAs($this->usuario());
        $cerrado = $this->caso(EstadoCaso::Cerrada);
        $abierto = $this->caso(EstadoCaso::Recibida);
        $this->reporte($cerrado, 'FX-2026-000202', CanalOrigen::Presencial);
        $cerrado->eventos()->create(['tipo' => 'cambio_estado', 'detalle' => 'Reparación verificada']);

        $url = CasoResource::getUrl('view', ['record' => $cerrado]);
        Livewire::test(ConsultarSeguimiento::class)
            ->assertCanSeeTableRecords([$cerrado, $abierto])
            ->searchTable('fx-2026-000202')
            ->assertCanSeeTableRecords([$cerrado])
            ->assertCanNotSeeTableRecords([$abierto])
            ->assertSee('Abrir seguimiento')
            ->assertSee('href="'.e($url).'"', escape: false)
            ->assertDontSee($url.'#historial-caso')
            ->searchTable((string) $cerrado->id)
            ->assertCanSeeTableRecords([$cerrado]);

        $this->get($url)->assertOk()->assertSee('Reparación verificada')
            ->assertSee('id="historial-caso"', escape: false);

        $urlAbierto = CasoResource::getUrl('view', ['record' => $abierto]);
        $this->get($urlAbierto)->assertOk()->assertSee('Pasar a revisión')
            ->assertSee('Volver al seguimiento');

        Livewire::test(ViewCaso::class, ['record' => $abierto->getRouteKey()])
            ->callAction('pasarARevision')
            ->assertNotified('El caso pasó a revisión.');

        Http::assertSent(fn (Request $request): bool => $request->url() === self::API."/api/v1/panel/casos/{$abierto->id}/estado"
            && $request->method() === 'PATCH' && $request['estado'] === 'en_revision');
    }

    public function test_control_programa_una_cuadrilla_y_muestra_su_programacion_vigente(): void
    {
        Http::fake([self::API.'/*' => Http::response(null, 204)]);
        $this->actingAs($this->usuario());
        $asignado = $this->caso(EstadoCaso::Asignada);
        $programado = $this->caso(EstadoCaso::Programada);
        $recibido = $this->caso(EstadoCaso::Recibida);
        $cuadrilla = Cuadrilla::create(['unidad_id' => $this->unidad->id, 'nombre' => 'Cuadrilla Norte', 'turno' => 'Mañana']);
        $fecha = today()->addDays(2);
        DB::table('programaciones')->insert([
            'caso_id' => $programado->id, 'cuadrilla_id' => $cuadrilla->id, 'fecha_prevista' => $fecha->toDateString(),
        ]);

        Livewire::test(ControlIntervenciones::class)
            ->assertCanSeeTableRecords([$asignado, $programado])
            ->assertCanNotSeeTableRecords([$recibido])
            ->assertSee('Cuadrilla Norte')
            ->assertSee($fecha->format('d/m/Y'))
            ->callTableAction('programar', $asignado, data: ['cuadrilla_id' => $cuadrilla->id, 'fecha' => $fecha->toDateString()])
            ->assertHasNoTableActionErrors();

        Http::assertSent(fn (Request $request): bool => $request->url() === self::API."/api/v1/panel/casos/{$asignado->id}/programaciones"
            && $request['cuadrillaId'] === $cuadrilla->id && $request['fecha'] === $fecha->toDateString());
    }

    public function test_el_admin_ve_evidencia_y_puede_aprobar_o_devolver_al_tecnico(): void
    {
        Http::fake([self::API.'/*' => Http::response(null, 204)]);
        $this->actingAs($this->usuario());
        $caso = $this->caso(EstadoCaso::PendienteValidacion);
        $intervencion = DB::table('intervenciones')->insertGetId([
            'caso_id' => $caso->id, 'tecnico_id' => $this->usuario(TipoUsuario::Tecnico)->id,
            'trabajo_realizado' => 'Reparación del tramo', 'estado_validacion' => 'pendiente',
        ]);
        DB::table('evidencias')->insert([
            'intervencion_id' => $intervencion, 'tipo' => 'acta_tecnica', 'descripcion' => 'Reparación subterránea',
            'url' => 'https://fotos.fixa.test/acta.jpg',
        ]);

        Livewire::test(ControlIntervenciones::class)->assertCanSeeTableRecords([$caso])->assertSee('Revisar evidencia');
        Livewire::test(ViewCaso::class, ['record' => $caso->getRouteKey()])
            ->assertSee('Acta técnica')
            ->assertSee('Reparación subterránea')
            ->assertSee('https://fotos.fixa.test/acta.jpg')
            ->callAction('rechazarEvidencia', data: ['observacion' => 'Completar registro'])
            ->assertNotified('El trabajo volvió a la cuadrilla.')
            ->callAction('aprobar')
            ->assertNotified('Caso cerrado.');

        $url = self::API."/api/v1/panel/casos/intervenciones/{$intervencion}/validacion";
        Http::assertSent(fn (Request $request): bool => $request->url() === $url
            && $request['aprobar'] === false && $request['observacion'] === 'Completar registro');
        Http::assertSent(fn (Request $request): bool => $request->url() === $url && $request['aprobar'] === true);
    }

    private function caso(EstadoCaso $estado): Caso
    {
        return Caso::create(['unidad_id' => $this->unidad->id, 'categoria_id' => $this->categoria->id, 'estado' => $estado]);
    }

    private function reporte(Caso $caso, string $codigo, CanalOrigen $canal): Reporte
    {
        return $caso->reportes()->create([
            'distrito_id' => 1, 'codigo' => $codigo, 'clave_hash' => 'clave-solo-para-la-prueba', 'descripcion' => 'Bache de prueba',
            'tipo_incidencia' => 'bache', 'canal_origen' => $canal, 'ubicacion' => 'POINT(-63.18 -17.78)', 'clave_idempotencia' => $codigo,
        ]);
    }
}
