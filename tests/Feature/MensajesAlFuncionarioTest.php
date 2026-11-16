<?php

namespace Tests\Feature;

use App\Api\ClienteFixaApi;
use App\Api\ErrorApi;
use App\Enums\TipoUsuario;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\EsquemaDePrueba;
use Tests\TestCase;

/**
 * El funcionario no sabe qué es una API: los avisos del panel hablan del «sistema» y el detalle
 * técnico queda en el log, para quienes lo mantienen.
 */
class MensajesAlFuncionarioTest extends TestCase
{
    use EsquemaDePrueba;

    private const TECNICISMOS = '/\bAPI\b|Render|JWT|FIXA_API|servidor|token/i';

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        $this->actingAs($this->usuario(TipoUsuario::Administrador));
    }

    public function test_sin_clave_configurada_el_aviso_no_menciona_la_api(): void
    {
        config(['services.fixa_api.jwt_clave' => null]);

        $this->assertMensajeSimple(fn () => (new ClienteFixaApi)->pasarARevision(1));
    }

    public function test_si_no_hay_conexion_el_aviso_no_menciona_la_api(): void
    {
        config(['services.fixa_api.jwt_clave' => str_repeat('x', 40)]);
        Http::fake(fn () => throw new ConnectionException('cURL error 7'));

        $this->assertMensajeSimple(fn () => (new ClienteFixaApi)->pasarARevision(1));
    }

    public function test_un_error_interno_o_de_sesion_no_menciona_la_api(): void
    {
        config(['services.fixa_api.jwt_clave' => str_repeat('x', 40)]);

        foreach ([500, 401] as $estado) {
            Http::fake(['*' => Http::response(['title' => 'Unauthorized'], $estado)]);
            $this->assertMensajeSimple(fn () => (new ClienteFixaApi)->pasarARevision(1));
        }
    }

    private function assertMensajeSimple(callable $accion): void
    {
        try {
            $accion();
            $this->fail('Se esperaba un error.');
        } catch (ErrorApi $error) {
            $this->assertDoesNotMatchRegularExpression(self::TECNICISMOS, $error->getMessage());
        }
    }
}
