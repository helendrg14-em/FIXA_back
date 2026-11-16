<?php

namespace Tests\Feature;

use App\Enums\EstadoCaso;
use App\Filament\Pages\NuevoReporte;
use App\Filament\Resources\Casos\Pages\ListCasos;
use App\Filament\Resources\Feriados\Pages\ManageFeriados;
use App\Filament\Resources\Feriados\Widgets\CalendarioFeriados;
use App\Models\Caso;
use App\Models\Categoria;
use App\Models\EventoHistorial;
use App\Models\Feriado;
use App\Models\Reporte;
use App\Models\UnidadResponsable;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\EsquemaDePrueba;
use Tests\TestCase;

/** Pestañas de la bandeja, detalle compacto, calendario de feriados y mensajes en español. */
class PanelDisenoTest extends TestCase
{
    use EsquemaDePrueba;

    private Categoria $bache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $unidad = UnidadResponsable::create(['nombre' => 'Vialidad', 'sigla' => 'VIA']);
        $this->bache = Categoria::forceCreate(['codigo' => 'bache', 'unidad_id' => $unidad->id, 'nombre' => 'Baches', 'descripcion' => 'Baches', 'radio_cercania_m' => 30]);
        $this->actingAs($this->usuario());
    }

    private function caso(EstadoCaso $estado): Caso
    {
        return Caso::forceCreate(['categoria_id' => $this->bache->id, 'unidad_id' => $this->bache->unidad_id, 'estado' => $estado]);
    }

    public function test_las_pestanas_separan_los_casos_por_etapa(): void
    {
        $recibido = $this->caso(EstadoCaso::Recibida);
        $porValidar = $this->caso(EstadoCaso::PendienteValidacion);
        $cerrado = $this->caso(EstadoCaso::Cerrada);

        Livewire::test(ListCasos::class)
            ->assertCanSeeTableRecords([$recibido, $porValidar])
            ->assertCanNotSeeTableRecords([$cerrado])
            ->set('activeTab', 'por_validar')
            ->assertCanSeeTableRecords([$porValidar])
            ->assertCanNotSeeTableRecords([$recibido])
            ->set('activeTab', 'cerrados')
            ->assertCanSeeTableRecords([$cerrado]);
    }

    public function test_el_detalle_muestra_el_reporte_y_el_historial_sin_campos_vacios(): void
    {
        $caso = $this->caso(EstadoCaso::Recibida);
        Reporte::forceCreate([
            'caso_id' => $caso->id, 'distrito_id' => 1, 'codigo' => 'FX-2026-000777', 'clave_hash' => 'x',
            'descripcion' => 'Bache profundo en la esquina', 'tipo_incidencia' => 'calle_deteriorada', 'canal_origen' => 'app_movil',
            'ubicacion' => 'POINT(-63.18 -17.78)', 'clave_idempotencia' => uniqid(),
        ]);
        EventoHistorial::forceCreate(['caso_id' => $caso->id, 'tipo' => 'registro', 'estado_nuevo' => 'recibida', 'detalle' => 'Reporte FX-2026-000777 registrado por app_movil.']);

        $this->get(route('filament.admin.resources.casos.view', $caso))->assertOk()
            ->assertSee('FX-2026-000777')
            ->assertSee('Bache profundo en la esquina')
            ->assertSee('Calle deteriorada')
            ->assertSee('registrado por app_movil')
            ->assertDontSee('Motivo sin foto');
    }

    public function test_el_calendario_marca_los_feriados_del_mes(): void
    {
        Feriado::create(['fecha' => '2026-11-02', 'nombre' => 'Todos Santos', 'fuente' => 'Decreto']);

        Livewire::test(CalendarioFeriados::class)
            ->set('mes', '2026-11')
            ->assertSee('Noviembre de 2026')
            ->assertSee('Todos Santos')
            ->assertSee('1 feriado este mes.')
            ->call('siguiente')
            ->assertSet('mes', '2026-12')
            ->assertDontSee('Todos Santos');
    }

    public function test_tocar_un_dia_abre_agregar_feriado_con_esa_fecha(): void
    {
        Livewire::test(ManageFeriados::class)
            ->dispatch('crear-feriado', fecha: '2026-12-08')
            ->assertActionMounted('create')
            ->assertSchemaStateSet(['fecha' => '2026-12-08'], 'mountedActionSchema0');
    }

    public function test_los_mensajes_de_validacion_estan_en_espanol(): void
    {
        $this->assertSame('es', app()->getLocale());
        $this->assertSame('Completa la descripción.', __('validation.required', ['attribute' => 'la descripción']));
    }

    public function test_un_correo_mal_escrito_se_explica_en_espanol(): void
    {
        Http::fake(['*' => Http::response([['valor' => 'bache', 'texto' => 'Bache']])]);

        Livewire::test(NuevoReporte::class)
            ->fillForm(['canal' => 'telefono', 'categoria' => 'bache', 'tipo' => 'bache', 'descripcion' => 'Bache.', 'correo' => 'no tiene', 'motivo_sin_foto' => 'Llamó por teléfono.'])
            ->call('registrar')
            ->assertHasFormErrors(['correo' => 'email'])
            ->assertSee('debe ser un correo válido');
    }
}
