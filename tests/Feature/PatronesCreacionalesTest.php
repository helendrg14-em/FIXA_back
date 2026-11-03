<?php

namespace Tests\Feature;

use App\Auth\ServicioAutenticacion;
use App\Categorias\CreadorAlumbrado;
use App\Categorias\CreadorBache;
use App\Categorias\CreadorCategoria;
use App\Categorias\CreadorResiduos;
use App\Enums\CanalOrigen;
use App\Enums\CodigoCategoria;
use App\Enums\EstadoCaso;
use App\Enums\TipoUsuario;
use App\Models\Caso;
use App\Models\Categoria;
use App\Models\Distrito;
use App\Models\Reporte;
use App\Models\UnidadResponsable;
use App\Reportes\ReporteBuilder;
use Error;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\EsquemaDePrueba;
use Tests\TestCase;

class PatronesCreacionalesTest extends TestCase
{
    use EsquemaDePrueba;

    private int $distritoId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        $this->distritoId = Distrito::query()->insertGetId(['numero' => 1, 'nombre' => 'Distrito de prueba']);
    }

    public function test_singleton_devuelve_siempre_la_misma_instancia(): void
    {
        $this->assertSame(ServicioAutenticacion::getInstance(), ServicioAutenticacion::getInstance());
    }

    public function test_singleton_no_se_puede_crear_con_new(): void
    {
        $this->expectException(Error::class);
        new ServicioAutenticacion;
    }

    public function test_singleton_no_guarda_la_sesion_de_otro_usuario(): void
    {
        $servicio = ServicioAutenticacion::getInstance();

        $admin = $this->usuario();
        $this->actingAs($admin);
        $this->assertTrue($servicio->usuario()->is($admin));
        $this->assertTrue($servicio->esAdministrador());

        $tecnico = $this->usuario(TipoUsuario::Tecnico);
        $this->actingAs($tecnico);
        $this->assertTrue($servicio->usuario()->is($tecnico));
        $this->assertFalse($servicio->esAdministrador());
    }

    public function test_singleton_normaliza_el_correo(): void
    {
        $credenciales = ServicioAutenticacion::getInstance()->credenciales('  ANA@Fixa.Test ', 'x');

        $this->assertSame('ana@fixa.test', $credenciales['correo']);
    }

    public function test_factory_method_elige_el_creador_de_cada_categoria(): void
    {
        $this->assertInstanceOf(CreadorBache::class, CreadorCategoria::para(CodigoCategoria::Bache));
        $this->assertInstanceOf(CreadorAlumbrado::class, CreadorCategoria::para(CodigoCategoria::Alumbrado));
        $this->assertInstanceOf(CreadorResiduos::class, CreadorCategoria::para(CodigoCategoria::Residuos));
    }

    public function test_cada_categoria_tiene_sus_tipos_y_la_opcion_otro(): void
    {
        foreach (CodigoCategoria::cases() as $codigo) {
            $this->assertArrayHasKey(CreadorCategoria::TIPO_OTRO, CreadorCategoria::para($codigo)->tipos());
        }

        $this->assertArrayHasKey('luminaria_apagada', CreadorCategoria::para(CodigoCategoria::Alumbrado)->tipos());
        $this->assertArrayNotHasKey('luminaria_apagada', CreadorCategoria::para(CodigoCategoria::Bache)->tipos());
    }

    public function test_alumbrado_pide_luminarias_salvo_que_elija_otro(): void
    {
        $this->assertFalla('numero_luminarias', $this->builder(CodigoCategoria::Alumbrado, 'luminaria_apagada'));

        $conNumero = $this->builder(CodigoCategoria::Alumbrado, 'luminaria_apagada')->conLuminarias(3)->registrar();
        $this->assertSame(3, $conNumero->reporte->numero_luminarias);

        $otro = $this->builder(CodigoCategoria::Alumbrado, 'otro')->registrar();
        $this->assertNull($otro->reporte->numero_luminarias);
    }

    public function test_un_bache_no_acepta_luminarias(): void
    {
        $this->assertFalla('numero_luminarias', $this->builder()->conLuminarias(2));
    }

    public function test_builder_guarda_caso_reporte_foto_e_historial(): void
    {
        $registrado = $this->builder()->registrar();
        $caso = Caso::firstOrFail();

        $this->assertSame(EstadoCaso::Recibida, $caso->estado);
        $this->assertSame($caso->categoria->unidad_id, $caso->unidad_id);
        $this->assertFalse($caso->requiere_verificacion_campo);
        $this->assertSame(1, $caso->eventos()->count());
        $this->assertSame(1, $registrado->reporte->fotos()->count());
        $this->assertStringContainsString('POINT(-63.18', Reporte::firstOrFail()->getRawOriginal('ubicacion'));
    }

    public function test_el_mapa_es_obligatorio_en_todos_los_canales(): void
    {
        $this->assertFalla('latitud', $this->builderSinUbicacion(CanalOrigen::AppMovil)->conFoto('https://f.test/a.jpg', 'a'));
        $this->assertFalla('latitud', $this->builderSinUbicacion(CanalOrigen::Telefono)
            ->sinFoto('Llamada')->registradoPor($this->usuario(TipoUsuario::Operador)->id));
    }

    public function test_la_foto_es_obligatoria_en_la_app(): void
    {
        $this->assertFalla('fotos', $this->builderSinFoto());
        $this->assertFalla('motivo_sin_foto', $this->builderSinFoto()->conFoto('https://f.test/a.jpg', 'a')->sinFoto('No pude'));
    }

    public function test_registro_asistido_sin_foto_queda_para_verificar_en_campo(): void
    {
        $operador = $this->usuario(TipoUsuario::Operador);

        $this->builderSinFoto(CanalOrigen::Telefono)->registradoPor($operador->id)->sinFoto('Reporta por teléfono')->registrar();

        $this->assertTrue(Caso::firstOrFail()->requiere_verificacion_campo);
        $this->assertSame($operador->id, Reporte::firstOrFail()->operador_id);
    }

    public function test_registro_asistido_exige_motivo_si_no_hay_foto(): void
    {
        $operador = $this->usuario(TipoUsuario::Operador);

        $this->assertFalla('fotos', $this->builderSinFoto(CanalOrigen::Presencial)->registradoPor($operador->id));
    }

    public function test_el_codigo_es_secuencial_y_de_la_gestion_actual(): void
    {
        $anio = now(config('fixa.zona_horaria'))->year;

        $this->assertSame("FX-$anio-000001", $this->builder()->registrar()->reporte->codigo);
        $this->assertSame("FX-$anio-000002", $this->builder()->registrar()->reporte->codigo);
    }

    public function test_un_registro_fallido_no_deja_saltos_en_el_codigo(): void
    {
        $this->builder(idempotencia: 'repetida')->registrar();

        // Falla después de pedir el número: la transacción debe devolverlo.
        DB::statement('CREATE TRIGGER falla BEFORE INSERT ON fotos_reporte BEGIN SELECT RAISE(ABORT, \'falla\'); END');
        try {
            $this->builder()->registrar();
            $this->fail('Debía fallar');
        } catch (\Illuminate\Database\QueryException) {
            DB::statement('DROP TRIGGER falla');
        }

        $this->assertSame(1, Caso::count());
        $this->assertStringEndsWith('-000002', $this->builder()->registrar()->reporte->codigo);
    }

    public function test_la_clave_es_aleatoria_y_solo_se_guarda_cifrada(): void
    {
        $registrado = $this->builder()->registrar();

        $this->assertMatchesRegularExpression('/^[A-HJKMNP-Z2-9]{6}$/', $registrado->clave);
        $this->assertNotSame($registrado->clave, $registrado->reporte->clave_hash);
        $this->assertTrue(Hash::check($registrado->clave, $registrado->reporte->clave_hash));
    }

    public function test_reenviar_la_solicitud_no_crea_otro_caso(): void
    {
        $primero = $this->builder(idempotencia: 'misma-solicitud')->registrar();
        $segundo = $this->builder(idempotencia: 'misma-solicitud')->registrar();

        $this->assertSame(1, Caso::count());
        $this->assertTrue($primero->reporte->is($segundo->reporte));
        $this->assertNull($segundo->clave);
    }

    private function categoria(CodigoCategoria $codigo): Categoria
    {
        $unidad = UnidadResponsable::firstOrCreate(['sigla' => $codigo->value], ['nombre' => $codigo->value]);

        return Categoria::firstOrCreate(['codigo' => $codigo], [
            'unidad_id' => $unidad->id, 'nombre' => $codigo->value, 'descripcion' => 'D', 'radio_cercania_m' => 100,
        ]);
    }

    private function builderSinUbicacion(CanalOrigen $canal, ?string $idempotencia = null): ReporteBuilder
    {
        return (new ReporteBuilder($this->categoria(CodigoCategoria::Bache), $canal))
            ->enDistrito($this->distritoId)
            ->conTipo('bache')
            ->conDescripcion('Hueco grande en la esquina')
            ->conClaveIdempotencia($idempotencia ?? uniqid('solicitud', true));
    }

    private function builderSinFoto(CanalOrigen $canal = CanalOrigen::AppMovil): ReporteBuilder
    {
        return $this->builderSinUbicacion($canal)->enUbicacion(-17.78, -63.18);
    }

    private function builder(CodigoCategoria $codigo = CodigoCategoria::Bache, string $tipo = 'bache', ?string $idempotencia = null): ReporteBuilder
    {
        return (new ReporteBuilder($this->categoria($codigo), CanalOrigen::AppMovil))
            ->enDistrito($this->distritoId)
            ->enUbicacion(-17.78, -63.18)
            ->conTipo($tipo)
            ->conDescripcion('Descripción de prueba')
            ->conFoto('https://f.test/a.jpg', 'a')
            ->conClaveIdempotencia($idempotencia ?? uniqid('solicitud', true));
    }

    private function assertFalla(string $campo, ReporteBuilder $builder): void
    {
        try {
            $builder->registrar();
            $this->fail("Debía rechazar el campo $campo");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($campo, $e->errors());
        }
    }
}
