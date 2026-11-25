<?php

namespace Tests\Feature;

use App\Enums\Prioridad;
use App\Filament\Resources\Categorias\CategoriaResource;
use App\Filament\Resources\Categorias\Pages\EditCategoria;
use App\Filament\Resources\Categorias\RelationManagers\MetasAtencionRelationManager;
use App\Filament\Resources\Feriados\FeriadoResource;
use App\Enums\TipoUsuario;
use App\Models\Categoria;
use App\Models\Feriado;
use App\Models\MetaAtencion;
use Carbon\CarbonImmutable;
use Database\Seeders\CatalogosSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\Concerns\EsquemaDePrueba;
use Tests\TestCase;

class PlazosTest extends TestCase
{
    use EsquemaDePrueba;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_las_horas_corren_tambien_en_fin_de_semana(): void
    {
        $meta = new MetaAtencion(['plazo_horas' => 24]);

        $objetivo = $meta->fechaObjetivo(CarbonImmutable::parse('2026-11-07 10:00', 'America/La_Paz'));

        $this->assertSame('2026-11-08 10:00', $objetivo->format('Y-m-d H:i'));
    }

    public function test_los_dias_habiles_saltan_fin_de_semana(): void
    {
        $meta = new MetaAtencion(['plazo_dias_habiles' => 5]);

        // Lunes: martes a viernes son 4 días; el quinto cae el lunes siguiente.
        $objetivo = $meta->fechaObjetivo(CarbonImmutable::parse('2026-11-09 10:00', 'America/La_Paz'));

        $this->assertSame('2026-11-16', $objetivo->toDateString());
    }

    public function test_los_dias_habiles_saltan_feriados(): void
    {
        Feriado::create(['fecha' => '2026-11-11', 'nombre' => 'Feriado de prueba', 'fuente' => 'Prueba']);
        $meta = new MetaAtencion(['plazo_dias_habiles' => 5]);

        $objetivo = $meta->fechaObjetivo(CarbonImmutable::parse('2026-11-09 10:00', 'America/La_Paz'));

        $this->assertSame('2026-11-17', $objetivo->toDateString());
    }

    public function test_el_seeder_carga_los_plazos_acordados_sin_duplicar(): void
    {
        $this->seed(CatalogosSeeder::class);
        $this->seed(CatalogosSeeder::class);

        $this->assertSame(12, MetaAtencion::count());

        foreach (Categoria::all() as $categoria) {
            $plazos = $categoria->metasAtencion->mapWithKeys(fn (MetaAtencion $m) => [$m->prioridad => $m->plazoLegible()]);

            $this->assertSame('24 horas', $plazos[Prioridad::Critica->value]);
            $this->assertSame('48 horas', $plazos[Prioridad::Alta->value]);
            $this->assertSame('5 días hábiles', $plazos[Prioridad::Media->value]);
            $this->assertSame('5 días hábiles', $plazos[Prioridad::Baja->value]);
        }
    }

    public function test_un_plazo_nuevo_en_dias_cierra_el_vigente(): void
    {
        $this->seed(CatalogosSeeder::class);
        $this->actingAs($this->usuario());
        $categoria = Categoria::firstOrFail();
        $inicio = today(config('fixa.zona_horaria'))->addDays(2);

        Livewire::test(MetasAtencionRelationManager::class, ['ownerRecord' => $categoria, 'pageClass' => EditCategoria::class])
            ->callTableAction('create', data: [
                'prioridad' => Prioridad::Baja->value,
                'plazo' => 10,
                'medida' => 'plazo_dias_habiles',
                'vigente_desde' => $inicio->toDateString(),
            ])
            ->assertHasNoTableActionErrors()
            ->assertSee('Programado');

        $plazos = $categoria->metasAtencion()->where('prioridad', Prioridad::Baja->value)->orderBy('vigente_desde')->get();
        $this->assertCount(2, $plazos);
        [$anterior, $programado] = $plazos->all();
        $this->assertSame($inicio->copy()->subDay()->toDateString(), $anterior->vigente_hasta->toDateString());
        $this->assertSame(10, $programado->plazo_dias_habiles);
        $this->assertNull($programado->plazo_horas);
        $this->assertNull($programado->vigente_hasta);

        Livewire::test(MetasAtencionRelationManager::class, ['ownerRecord' => $categoria, 'pageClass' => EditCategoria::class])
            ->callTableAction('create', data: [
                'prioridad' => Prioridad::Baja->value,
                'plazo' => 12,
                'medida' => 'plazo_dias_habiles',
                'vigente_desde' => $inicio->toDateString(),
            ])
            ->assertHasTableActionErrors(['vigente_desde']);
        $this->assertSame(2, $categoria->metasAtencion()->where('prioridad', Prioridad::Baja->value)->count());
    }

    public function test_solo_el_administrador_ve_feriados(): void
    {
        $this->actingAs($this->usuario(TipoUsuario::Supervisor));
        $this->get(FeriadoResource::getUrl('index'))->assertForbidden();

        $this->flushSession();
        $this->actingAs($this->usuario());
        $this->get(FeriadoResource::getUrl('index'))->assertOk();
        $this->get(CategoriaResource::getUrl('index'))->assertOk();
    }
}
