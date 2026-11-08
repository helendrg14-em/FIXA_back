<?php

namespace Tests\Feature;

use App\Enums\TipoUsuario;
use App\Filament\Resources\Usuarios\Pages\ListUsuarios;
use App\Models\User;
use App\Notifications\RecuperarPassword;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\EsquemaDePrueba;
use Tests\TestCase;

class RecuperarPasswordTest extends TestCase
{
    use EsquemaDePrueba;

    private User $tecnico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        Notification::fake();
        $this->tecnico = $this->usuario(TipoUsuario::Tecnico);
    }

    private function pedir(string $correo): void
    {
        $this->post(route('cuenta.recuperar.enviar'), ['correo' => $correo])
            ->assertRedirect(route('cuenta.recuperar'))
            ->assertSessionHas('enviado');
    }

    private function enlaceEnviado(): string
    {
        $url = null;
        Notification::assertSentTo($this->tecnico, RecuperarPassword::class, function (RecuperarPassword $n) use (&$url) {
            $url = $n->toMail($this->tecnico)->actionUrl;

            return true;
        });

        return $url;
    }

    public function test_el_login_ofrece_recuperar_la_contrasena(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee(route('cuenta.recuperar'));
        $this->get(route('cuenta.recuperar'))->assertOk()->assertSee('Recupera tu contraseña');
    }

    public function test_el_enlace_cambia_la_contrasena_una_sola_vez(): void
    {
        $this->pedir(strtoupper($this->tecnico->correo));
        parse_str((string) parse_url($this->enlaceEnviado(), PHP_URL_QUERY), $q);

        $this->get(route('cuenta.contrasena', ['usuario' => $this->tecnico->id, 'token' => $q['token']]))
            ->assertOk()->assertSee('Cambia tu contraseña');

        $datos = ['token' => $q['token'], 'password' => 'otra-clave-2026', 'password_confirmation' => 'otra-clave-2026'];
        $this->post(route('cuenta.contrasena.guardar', ['usuario' => $this->tecnico->id]), $datos)->assertOk();

        $this->assertTrue(Hash::check('otra-clave-2026', $this->tecnico->fresh()->password_hash));
        $this->post(route('cuenta.contrasena.guardar', ['usuario' => $this->tecnico->id]), $datos)->assertForbidden();
    }

    public function test_la_contrasena_actual_sigue_sirviendo_hasta_cambiarla(): void
    {
        $this->pedir($this->tecnico->correo);

        $this->assertTrue(Hash::check('clave-de-prueba-123', $this->tecnico->fresh()->password_hash));
    }

    public function test_el_enlace_vence_en_60_minutos(): void
    {
        $this->pedir($this->tecnico->correo);
        parse_str((string) parse_url($this->enlaceEnviado(), PHP_URL_QUERY), $q);

        $this->travel(61)->minutes();

        $this->get(route('cuenta.contrasena', ['usuario' => $this->tecnico->id, 'token' => $q['token']]))->assertForbidden();
    }

    public function test_el_enlace_usa_app_url_y_no_el_host_de_la_peticion(): void
    {
        config(['app.url' => 'https://fixa.example']);

        $this->post('http://sitio-falso.test/cuenta/recuperar', ['correo' => $this->tecnico->correo]);

        $this->assertStringStartsWith('https://fixa.example/cuenta/', $this->enlaceEnviado());
    }

    public function test_la_respuesta_no_revela_si_la_cuenta_existe(): void
    {
        $inactiva = $this->usuario(TipoUsuario::Supervisor, ['activo' => false]);
        $operador = $this->usuario(TipoUsuario::Operador);

        $this->pedir('nadie@fixa.test');
        $this->pedir($inactiva->correo);
        $this->pedir($operador->correo);

        Notification::assertNothingSent();
    }

    public function test_limita_los_correos_por_cuenta(): void
    {
        foreach (range(1, 5) as $_) {
            $this->pedir($this->tecnico->correo);
        }

        Notification::assertSentToTimes($this->tecnico, RecuperarPassword::class, 3);
    }

    public function test_el_administrador_restablece_sin_ver_la_contrasena(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->usuario());

        Livewire::test(ListUsuarios::class)->callTableAction('restablecer', $this->tecnico);

        Notification::assertSentTo($this->tecnico, RecuperarPassword::class);
        $this->assertTrue(Hash::check('clave-de-prueba-123', $this->tecnico->fresh()->password_hash));
    }

    public function test_restablecer_no_aparece_si_la_cuenta_aun_no_tiene_contrasena(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->usuario());
        $pendiente = $this->usuario(TipoUsuario::Tecnico, ['debe_registrar_password' => true]);

        Livewire::test(ListUsuarios::class)
            ->assertTableActionHidden('restablecer', $pendiente)
            ->assertTableActionVisible('reenviar', $pendiente);
    }
}
