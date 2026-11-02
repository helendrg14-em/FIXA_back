<?php

namespace Tests\Feature;

use App\Enums\TipoUsuario;
use App\Models\User;
use App\Notifications\RegistrarPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\EsquemaDePrueba;
use Tests\TestCase;

class RegistrarPasswordTest extends TestCase
{
    use EsquemaDePrueba;

    private User $usuario;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        Notification::fake();
        $this->usuario = $this->usuario(TipoUsuario::Tecnico, ['debe_registrar_password' => true]);
        $this->usuario->enviarEnlaceRegistro();
        Notification::assertSentTo($this->usuario, RegistrarPassword::class, function (RegistrarPassword $n) {
            $url = $n->toMail($this->usuario)->actionUrl;
            parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
            $this->token = $q['token'];

            return true;
        });
    }

    private function ruta(): string
    {
        return route('cuenta.contrasena.guardar', ['usuario' => $this->usuario->id]);
    }

    public function test_el_token_no_se_guarda_en_texto_plano(): void
    {
        $this->assertNotSame($this->token, $this->usuario->fresh()->token_recuperacion_hash);
    }

    public function test_enlace_valido_registra_la_contrasena_una_sola_vez(): void
    {
        $this->get(route('cuenta.contrasena', ['usuario' => $this->usuario->id, 'token' => $this->token]))->assertOk();

        $datos = ['token' => $this->token, 'password' => 'clave-nueva-2026', 'password_confirmation' => 'clave-nueva-2026'];
        $this->post($this->ruta(), $datos)->assertOk();

        $u = $this->usuario->fresh();
        $this->assertTrue(Hash::check('clave-nueva-2026', $u->password_hash));
        $this->assertFalse($u->debe_registrar_password);

        $this->post($this->ruta(), $datos)->assertForbidden();
    }

    public function test_token_incorrecto_es_rechazado(): void
    {
        $this->get(route('cuenta.contrasena', ['usuario' => $this->usuario->id, 'token' => 'falso']))->assertForbidden();
    }

    public function test_enlace_vencido_es_rechazado(): void
    {
        $this->usuario->forceFill(['token_expira' => now()->subMinute()])->save();

        $this->get(route('cuenta.contrasena', ['usuario' => $this->usuario->id, 'token' => $this->token]))->assertForbidden();
    }

    public function test_cuenta_desactivada_no_puede_usar_el_enlace(): void
    {
        $this->usuario->forceFill(['activo' => false])->save();

        $this->get(route('cuenta.contrasena', ['usuario' => $this->usuario->id, 'token' => $this->token]))->assertForbidden();
    }

    public function test_exige_contrasena_de_12_caracteres_con_letras_y_numeros(): void
    {
        $this->post($this->ruta(), ['token' => $this->token, 'password' => 'corta1', 'password_confirmation' => 'corta1'])
            ->assertSessionHasErrors('password');
    }
}
