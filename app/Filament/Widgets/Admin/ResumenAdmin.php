<?php

namespace App\Filament\Widgets\Admin;

use App\Enums\EstadoCaso;
use App\Enums\TipoUsuario;
use App\Filament\Pages\ConsultarSeguimiento;
use App\Filament\Pages\ControlIntervenciones;
use App\Filament\Pages\GestionarReportes;
use App\Filament\Resources\Casos\CasoResource;
use App\Filament\Resources\Categorias\CategoriaResource;
use App\Filament\Resources\Usuarios\UsuarioResource;
use App\Filament\Widgets\ResumenDeInicio;
use App\Models\Caso;
use App\Models\Categoria;
use App\Models\User;

class ResumenAdmin extends ResumenDeInicio
{
    protected static TipoUsuario $perfil = TipoUsuario::Administrador;

    protected string $view = 'admin.inicio';

    protected function getViewData(): array
    {
        // Una consulta por tabla: con la base en otro servidor, cada ida y vuelta cuenta.
        $usuarios = User::get(['id', 'rol', 'nombres', 'apellidos', 'activo', 'debe_registrar_password', 'fecha_creacion']);
        $activos = $usuarios->where('activo', true);
        $categorias = Categoria::toBase()
            ->selectRaw('count(*) as total, sum(case when activa then 1 else 0 end) as activas')
            ->first();
        $casos = Caso::whereNotIn('estado', EstadoCaso::finales())->toBase()
            ->selectRaw('count(*) as abiertos, sum(case when vencido then 1 else 0 end) as vencidos')
            ->first();

        return [
            ...$this->momento(),
            'usuario' => $this->usuario(),
            'equipo' => collect([TipoUsuario::Administrador, TipoUsuario::Supervisor, TipoUsuario::Tecnico])
                ->map(fn (TipoUsuario $tipo): array => ['perfil' => $tipo->getLabel(), 'total' => $activos->where('rol', $tipo)->count()]),
            'totalActivos' => $activos->count(),
            'inactivos' => $usuarios->where('activo', false)->count(),
            'sinContrasena' => $activos->where('debe_registrar_password', true)->sortByDesc('fecha_creacion')->take(5)->values(),
            'totalSinContrasena' => $activos->where('debe_registrar_password', true)->count(),
            'categoriasActivas' => (int) $categorias->activas,
            'categorias' => (int) $categorias->total,
            'casosAbiertos' => (int) $casos->abiertos,
            'casosVencidos' => (int) $casos->vencidos,
            'urlUsuarios' => UsuarioResource::getUrl('index'),
            'urlNuevoUsuario' => UsuarioResource::getUrl('create'),
            'urlCategorias' => CategoriaResource::getUrl('index'),
            'urlCasos' => CasoResource::getUrl('index'),
            'operacion' => [
                ['titulo' => 'Gestionar reportes', 'url' => GestionarReportes::getUrl(), 'icono' => GestionarReportes::getNavigationIcon()],
                ['titulo' => 'Consultar seguimiento', 'url' => ConsultarSeguimiento::getUrl(), 'icono' => ConsultarSeguimiento::getNavigationIcon()],
                ['titulo' => 'Gestionar intervención', 'url' => ControlIntervenciones::getUrl(), 'icono' => ControlIntervenciones::getNavigationIcon()],
            ],
        ];
    }
}
