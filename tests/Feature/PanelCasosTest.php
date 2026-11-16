<?php

namespace Tests\Feature;

use App\Api\TokenPanel;
use App\Enums\EstadoCaso;
use App\Enums\TipoUsuario;
use App\Filament\Pages\NuevoReporte;
use App\Filament\Resources\Casos\Pages\ListCasos;
use App\Filament\Resources\Casos\Pages\ViewCaso;
use App\Filament\Resources\Revisiones\Pages\ListRevisiones;
use App\Models\Caso;
use App\Models\Categoria;
use App\Models\Reporte;
use App\Models\SolicitudRevision;
use App\Models\UnidadResponsable;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\EsquemaDePrueba;
use Tests\TestCase;

/**
 * Bandeja de casos, nuevo reporte y revisiones del panel. Las acciones van a la API de C#,
 * que aquí se simula con Http::fake: se comprueba qué se le envía y con qué token.
 */
class PanelCasosTest extends TestCase
{
    use EsquemaDePrueba;

    private const API = 'https://api.fixa.test';

    private const CLAVE = 'clave-de-pruebas-de-al-menos-32-caracteres';

    private UnidadResponsable $vialidad;

    private UnidadResponsable $alumbrado;

    private Caso $casoVialidad;

    private Caso $casoAlumbrado;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        config(['services.fixa_api.url' => self::API, 'services.fixa_api.jwt_clave' => self::CLAVE]);

        $this->vialidad = UnidadResponsable::create(['nombre' => 'Vialidad', 'sigla' => 'VIA']);
        $this->alumbrado = UnidadResponsable::create(['nombre' => 'Alumbrado', 'sigla' => 'ALU']);
        $bache = Categoria::forceCreate(['codigo' => 'bache', 'unidad_id' => $this->vialidad->id, 'nombre' => 'Baches', 'descripcion' => 'Baches', 'radio_cercania_m' => 30]);
        $luz = Categoria::forceCreate(['codigo' => 'alumbrado', 'unidad_id' => $this->alumbrado->id, 'nombre' => 'Alumbrado', 'descripcion' => 'Luz', 'radio_cercania_m' => 30]);
        $this->casoVialidad = Caso::forceCreate(['categoria_id' => $bache->id, 'unidad_id' => $this->vialidad->id, 'estado' => EstadoCaso::Recibida]);
        $this->casoAlumbrado = Caso::forceCreate(['categoria_id' => $luz->id, 'unidad_id' => $this->alumbrado->id, 'estado' => EstadoCaso::Recibida]);
    }

    private function supervisorVialidad(): User
    {
        return $this->usuario(TipoUsuario::Supervisor, ['unidad_id' => $this->vialidad->id]);
    }

    public function test_el_token_lo_acepta_la_api_porque_va_firmado_con_su_clave(): void
    {
        $admin = $this->usuario(datos: ['nombres' => 'Ana', 'apellidos' => 'Rios']);

        [$cabecera, $datos, $firma] = explode('.', TokenPanel::para($admin, ahora: 1_000));
        $decodificar = fn (string $parte): array => json_decode(base64_decode(strtr($parte, '-_', '+/')), true);

        $this->assertSame(['alg' => 'HS256', 'typ' => 'JWT'], $decodificar($cabecera));
        $this->assertSame((string) $admin->id, $decodificar($datos)['sub']);
        $this->assertSame('administrador', $decodificar($datos)['role']);
        $this->assertSame(1_000 + 15 * 60, $decodificar($datos)['exp']);
        $esperada = rtrim(strtr(base64_encode(hash_hmac('sha256', "$cabecera.$datos", self::CLAVE, true)), '+/', '-_'), '=');
        $this->assertSame($esperada, $firma);
    }

    public function test_el_administrador_ve_los_casos_de_todas_las_unidades(): void
    {
        $this->actingAs($this->usuario());

        Livewire::test(ListCasos::class)->assertCanSeeTableRecords([$this->casoVialidad, $this->casoAlumbrado]);
    }

    public function test_el_supervisor_solo_ve_los_casos_de_su_unidad(): void
    {
        $this->actingAs($this->supervisorVialidad());

        Livewire::test(ListCasos::class)
            ->assertCanSeeTableRecords([$this->casoVialidad])
            ->assertCanNotSeeTableRecords([$this->casoAlumbrado]);
        $this->get(route('filament.admin.resources.casos.view', $this->casoAlumbrado))->assertNotFound();
    }

    public function test_el_tecnico_no_entra_a_la_bandeja_ni_al_nuevo_reporte(): void
    {
        $this->actingAs($this->usuario(TipoUsuario::Tecnico));

        $this->get(route('filament.admin.resources.casos.index'))->assertForbidden();
        $this->get(route('filament.admin.pages.nuevo-reporte'))->assertForbidden();
    }

    public function test_el_supervisor_no_registra_reportes_desde_el_panel(): void
    {
        $this->actingAs($this->supervisorVialidad());

        $this->get(route('filament.admin.pages.nuevo-reporte'))->assertForbidden();
    }

    public function test_pasar_a_revision_se_hace_por_la_api_con_el_token_del_funcionario(): void
    {
        Http::fake([self::API.'/*' => Http::response(null, 204)]);
        $admin = $this->usuario();
        $this->actingAs($admin);

        Livewire::test(ViewCaso::class, ['record' => $this->casoAlumbrado->getRouteKey()])
            ->callAction('pasarARevision')
            ->assertNotified('El caso pasó a revisión.');

        Http::assertSent(fn (Request $r): bool => $r->method() === 'PATCH'
            && $r->url() === self::API."/api/v1/panel/casos/{$this->casoAlumbrado->id}/estado"
            && $r['estado'] === 'en_revision'
            && str_starts_with($r->header('Authorization')[0] ?? '', 'Bearer '));
    }

    public function test_si_la_api_rechaza_la_accion_se_muestra_su_mensaje(): void
    {
        Http::fake([self::API.'/*' => Http::response(['title' => 'La fecha prevista no puede ser anterior a hoy.'], 422)]);
        $this->casoVialidad->forceFill(['estado' => EstadoCaso::EnRevision])->save();
        $this->actingAs($this->supervisorVialidad());

        Livewire::test(ViewCaso::class, ['record' => $this->casoVialidad->getRouteKey()])
            ->callAction('rechazar', data: ['motivo' => 'No es un bache.'])
            ->assertNotified('No se pudo completar');
    }

    public function test_el_administrador_registra_un_reporte_por_telefono_y_ve_el_codigo_y_la_clave(): void
    {
        Http::fake([
            self::API.'/api/v1/publico/categorias/bache/tipos' => Http::response([['valor' => 'bache', 'texto' => 'Bache']]),
            self::API.'/api/v1/panel/reportes' => Http::response(['codigo' => 'FX-2026-000123', 'clave' => 'K7Q2PX'], 201),
        ]);
        $this->actingAs($this->usuario());

        Livewire::test(NuevoReporte::class)
            ->fillForm([
                'canal' => 'telefono',
                'categoria' => 'bache',
                'tipo' => 'bache',
                'descripcion' => 'Bache grande frente al colegio.',
                'latitud' => -17.78,
                'longitud' => -63.18,
                'motivo_sin_foto' => 'El vecino llamó por teléfono.',
            ])
            ->call('registrar')
            ->assertHasNoFormErrors()
            ->assertSet('creado', ['codigo' => 'FX-2026-000123', 'clave' => 'K7Q2PX'])
            ->assertSee('K7Q2PX');

        Http::assertSent(fn (Request $r): bool => $r->url() === self::API.'/api/v1/panel/reportes'
            && $r->isMultipart()
            && collect($r->data())->contains(fn ($campo) => $campo['name'] === 'canal' && $campo['contents'] === 'telefono')
            && collect($r->data())->contains(fn ($campo) => $campo['name'] === 'motivoSinFoto'));
    }

    public function test_sin_foto_el_reporte_exige_el_motivo(): void
    {
        Http::fake([self::API.'/*' => Http::response([['valor' => 'bache', 'texto' => 'Bache']])]);
        $this->actingAs($this->usuario());

        Livewire::test(NuevoReporte::class)
            ->fillForm(['canal' => 'telefono', 'categoria' => 'bache', 'tipo' => 'bache', 'descripcion' => 'Bache.', 'latitud' => -17.78, 'longitud' => -63.18])
            ->call('registrar')
            ->assertHasFormErrors(['motivo_sin_foto' => 'required']);

        Http::assertNotSent(fn (Request $r): bool => str_ends_with($r->url(), '/panel/reportes'));
    }

    public function test_el_supervisor_solo_ve_las_revisiones_de_su_unidad_y_las_responde_por_la_api(): void
    {
        Http::fake([self::API.'/*' => Http::response(null, 204)]);
        $propia = $this->revisionDe($this->casoVialidad, 'FX-2026-000001');
        $ajena = $this->revisionDe($this->casoAlumbrado, 'FX-2026-000002');
        $this->actingAs($this->supervisorVialidad());

        Livewire::test(ListRevisiones::class)
            ->assertCanSeeTableRecords([$propia])
            ->assertCanNotSeeTableRecords([$ajena])
            ->callTableAction('noAceptar', $propia, data: ['respuesta' => 'Fuimos y el bache está tapado.'])
            ->assertNotified('Respuesta enviada.');

        Http::assertSent(fn (Request $r): bool => $r->url() === self::API."/api/v1/panel/casos/revisiones/{$propia->id}"
            && $r['aceptar'] === false);
    }

    private function revisionDe(Caso $caso, string $codigo): SolicitudRevision
    {
        $reporte = Reporte::forceCreate([
            'caso_id' => $caso->id, 'distrito_id' => 1, 'codigo' => $codigo, 'clave_hash' => 'x',
            'descripcion' => 'Prueba', 'tipo_incidencia' => 'bache', 'canal_origen' => 'app_movil',
            'ubicacion' => 'POINT(-63.18 -17.78)', 'clave_idempotencia' => uniqid(),
        ]);

        return SolicitudRevision::forceCreate(['reporte_id' => $reporte->id, 'motivo' => 'El bache se volvió a abrir.']);
    }
}
