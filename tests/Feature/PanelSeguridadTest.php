<?php

namespace Tests\Feature;

use App\Enums\TipoUsuario;
use App\Filament\Resources\Categorias\CategoriaResource;
use App\Filament\Resources\Usuarios\Pages\CreateUsuario;
use App\Filament\Resources\Usuarios\Pages\EditUsuario;
use App\Filament\Resources\Usuarios\Pages\ListUsuarios;
use App\Filament\Resources\Usuarios\UsuarioResource;
use App\Models\Categoria;
use App\Models\UnidadResponsable;
use App\Models\User;
use App\Notifications\RegistrarPassword;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\EsquemaDePrueba;
use Tests\TestCase;

class PanelSeguridadTest extends TestCase
{
    use EsquemaDePrueba;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_cuenta_inactiva_no_entra_al_panel(): void
    {
        $panel = app(Panel::class);

        $this->assertTrue($this->usuario()->canAccessPanel($panel));
        $this->assertFalse($this->usuario(datos: ['activo' => false])->canAccessPanel($panel));
    }

    public function test_el_operador_aun_no_entra_al_panel(): void
    {
        $this->assertFalse($this->usuario(TipoUsuario::Operador)->canAccessPanel(app(Panel::class)));
    }

    public function test_solo_el_administrador_ve_usuarios_y_categorias(): void
    {
        foreach ([TipoUsuario::Supervisor, TipoUsuario::Tecnico] as $tipo) {
            $this->flushSession();
            $this->actingAs($this->usuario($tipo));
            $this->get(UsuarioResource::getUrl('index'))->assertForbidden();
            $this->get(CategoriaResource::getUrl('index'))->assertForbidden();
        }

        $this->flushSession();
        $this->actingAs($this->usuario());
        $this->get(UsuarioResource::getUrl('index'))->assertOk();
        $this->get(CategoriaResource::getUrl('index'))->assertOk();
    }

    public function test_crear_cuenta_envia_enlace_y_no_deja_contrasena_conocida(): void
    {
        Notification::fake();
        $this->actingAs($this->usuario());
        $unidad = UnidadResponsable::create(['nombre' => 'Unidad', 'sigla' => 'U']);

        Livewire::test(CreateUsuario::class)
            ->fillForm([
                'nombres' => 'Ana', 'apellidos' => 'Rojas', 'correo' => 'ANA@fixa.test',
                'rol' => 'supervisor', 'unidad_id' => $unidad->id, 'cargo' => 'Jefa',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $nueva = User::where('correo', 'ana@fixa.test')->firstOrFail();
        $this->assertTrue($nueva->debe_registrar_password);
        $this->assertNotNull($nueva->token_recuperacion_hash);
        Notification::assertSentTo($nueva, RegistrarPassword::class);
    }

    public function test_el_panel_no_crea_administradores(): void
    {
        $this->actingAs($this->usuario());

        Livewire::test(CreateUsuario::class)
            ->fillForm(['nombres' => 'A', 'apellidos' => 'B', 'correo' => 'nuevo@fixa.test', 'rol' => 'administrador'])
            ->call('create')
            ->assertHasFormErrors(['rol']);

        $this->assertFalse(User::where('correo', 'nuevo@fixa.test')->exists());
    }

    public function test_el_panel_no_convierte_una_cuenta_en_administrador(): void
    {
        $this->actingAs($this->usuario());
        $tecnico = $this->usuario(TipoUsuario::Tecnico, ['especialidad' => 'Vías']);

        Livewire::test(EditUsuario::class, ['record' => $tecnico->getKey()])
            ->fillForm(['rol' => 'administrador'])
            ->call('save')
            ->assertHasFormErrors(['rol']);

        $this->assertSame(TipoUsuario::Tecnico, $tecnico->fresh()->rol);
    }

    public function test_a_otro_administrador_no_se_le_cambia_el_perfil(): void
    {
        $this->actingAs($this->usuario());
        $otro = $this->usuario();

        Livewire::test(EditUsuario::class, ['record' => $otro->getKey()])
            ->assertFormFieldIsDisabled('rol')
            ->assertFormSet(['rol' => TipoUsuario::Administrador->value]);
    }

    public function test_crear_cuenta_no_ofrece_crear_otra(): void
    {
        $this->actingAs($this->usuario());

        $this->assertFalse(Livewire::test(CreateUsuario::class)->instance()->canCreateAnother());
    }

    public function test_el_supervisor_exige_unidad(): void
    {
        $this->actingAs($this->usuario());

        Livewire::test(CreateUsuario::class)
            ->fillForm(['nombres' => 'A', 'apellidos' => 'B', 'correo' => 'a@fixa.test', 'rol' => 'supervisor'])
            ->call('create')
            ->assertHasFormErrors(['unidad_id' => 'required']);
    }

    public function test_desactivar_conserva_la_cuenta(): void
    {
        $this->actingAs($this->usuario());
        $otra = $this->usuario(TipoUsuario::Tecnico);

        Livewire::test(ListUsuarios::class)->callTableAction('desactivar', $otra);

        $this->assertFalse($otra->fresh()->activo);
    }

    public function test_el_administrador_no_puede_desactivarse_a_si_mismo(): void
    {
        $admin = $this->usuario();
        $this->actingAs($admin);

        $this->assertFalse($admin->can('desactivar', $admin));
        Livewire::test(ListUsuarios::class)->assertTableActionHidden('desactivar', $admin);
    }

    public function test_nadie_puede_borrar_cuentas_ni_categorias(): void
    {
        $admin = $this->usuario();
        $unidad = UnidadResponsable::create(['nombre' => 'Unidad', 'sigla' => 'U']);
        $categoria = Categoria::create(['codigo' => 'bache', 'unidad_id' => $unidad->id, 'nombre' => 'C', 'descripcion' => 'D', 'radio_cercania_m' => 100]);

        $this->assertFalse($admin->can('delete', $this->usuario(TipoUsuario::Tecnico)));
        $this->assertFalse($admin->can('delete', $categoria));
    }

    public function test_no_se_puede_crear_una_cuarta_categoria(): void
    {
        $admin = $this->usuario();
        $unidad = UnidadResponsable::create(['nombre' => 'Unidad', 'sigla' => 'U']);
        foreach (['bache' => 'A', 'alumbrado' => 'B'] as $codigo => $n) {
            Categoria::create(['codigo' => $codigo, 'unidad_id' => $unidad->id, 'nombre' => $n, 'descripcion' => 'D', 'radio_cercania_m' => 100]);
        }
        $this->assertTrue($admin->can('create', Categoria::class));

        Categoria::create(['codigo' => 'residuos', 'unidad_id' => $unidad->id, 'nombre' => 'C', 'descripcion' => 'D', 'radio_cercania_m' => 100]);
        $this->assertFalse($admin->can('create', Categoria::class));
    }
}
