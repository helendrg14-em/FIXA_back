<?php

namespace Tests\Feature;

use App\Enums\EstadoCaso;
use App\Enums\TipoUsuario;
use App\Filament\Pages\Mapa;
use App\Models\Caso;
use App\Models\Categoria;
use App\Models\Reporte;
use App\Models\UnidadResponsable;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\EsquemaDePrueba;
use Tests\TestCase;

/** Mapa de reportes del panel: quién lo ve y qué casos muestra. */
class MapaTest extends TestCase
{
    use EsquemaDePrueba;

    private UnidadResponsable $vialidad;

    private Caso $bache;

    private Caso $luz;

    private Caso $cerrado;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquema();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->vialidad = UnidadResponsable::create(['nombre' => 'Vialidad', 'sigla' => 'VIA']);
        $alumbrado = UnidadResponsable::create(['nombre' => 'Alumbrado', 'sigla' => 'ALU']);
        $catBache = Categoria::forceCreate(['codigo' => 'bache', 'unidad_id' => $this->vialidad->id, 'nombre' => 'Baches', 'descripcion' => 'B', 'radio_cercania_m' => 30]);
        $catLuz = Categoria::forceCreate(['codigo' => 'alumbrado', 'unidad_id' => $alumbrado->id, 'nombre' => 'Alumbrado', 'descripcion' => 'L', 'radio_cercania_m' => 30]);

        $this->bache = $this->casoEn($catBache, EstadoCaso::Recibida, -17.78, -63.18);
        $this->luz = $this->casoEn($catLuz, EstadoCaso::Programada, -17.79, -63.19);
        $this->cerrado = $this->casoEn($catBache, EstadoCaso::Cerrada, -17.77, -63.17);
    }

    private function casoEn(Categoria $categoria, EstadoCaso $estado, float $lat, float $lng): Caso
    {
        $caso = Caso::forceCreate(['categoria_id' => $categoria->id, 'unidad_id' => $categoria->unidad_id, 'estado' => $estado]);
        Reporte::forceCreate([
            'caso_id' => $caso->id, 'distrito_id' => 1, 'codigo' => 'FX-'.$caso->id, 'clave_hash' => 'x',
            'descripcion' => 'Prueba', 'tipo_incidencia' => 'otro', 'canal_origen' => 'app_movil',
            'ubicacion' => DB::raw("ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)"), 'clave_idempotencia' => 'clave-'.$caso->id,
        ]);

        return $caso;
    }

    /** @return list<int> */
    private function idsEnElMapa(): array
    {
        return array_column(Livewire::test(Mapa::class)->instance()->puntos(), 'id');
    }

    public function test_el_administrador_ve_los_casos_abiertos_de_toda_la_ciudad_con_su_ubicacion(): void
    {
        $this->actingAs($this->usuario());

        $this->get(Mapa::getUrl())->assertOk()->assertSee('Mapa de reportes');

        $puntos = Livewire::test(Mapa::class)->instance()->puntos();
        $this->assertEqualsCanonicalizing([$this->bache->id, $this->luz->id], array_column($puntos, 'id'));
        $bache = collect($puntos)->firstWhere('id', $this->bache->id);
        $this->assertSame([-17.78, -63.18, 'bache'], [$bache['lat'], $bache['lng'], $bache['codigo']]);
    }

    public function test_los_filtros_cambian_los_casos_del_mapa(): void
    {
        $this->actingAs($this->usuario());

        $this->assertSame([$this->cerrado->id], array_column(Livewire::test(Mapa::class)->set('estado', 'cerrados')->instance()->puntos(), 'id'));
        $this->assertCount(3, Livewire::test(Mapa::class)->set('estado', 'todos')->instance()->puntos());
        Livewire::test(Mapa::class)->set('estado', 'todos')->set('categoria', (string) $this->luz->categoria_id)
            ->assertDispatched('mapa-puntos');
    }

    public function test_el_supervisor_solo_ve_los_casos_de_su_unidad(): void
    {
        $this->actingAs($this->usuario(TipoUsuario::Supervisor, ['unidad_id' => $this->vialidad->id]));

        $this->assertSame([$this->bache->id], $this->idsEnElMapa());
    }

    public function test_el_tecnico_no_entra_al_mapa(): void
    {
        $this->actingAs($this->usuario(TipoUsuario::Tecnico));

        $this->get(Mapa::getUrl())->assertForbidden();
    }
}
