<?php

namespace App\Filament\Widgets\Admin;

use App\Enums\EstadoCaso;
use App\Enums\TipoUsuario;
use App\Filament\Resources\Categorias\CategoriaResource;
use App\Filament\Resources\Feriados\FeriadoResource;
use App\Filament\Resources\Usuarios\UsuarioResource;
use App\Filament\Widgets\ResumenDeInicio;
use App\Models\Caso;
use App\Models\Categoria;
use App\Models\Feriado;
use App\Models\User;

class ResumenAdmin extends ResumenDeInicio
{
    protected static TipoUsuario $perfil = TipoUsuario::Administrador;

    protected string $view = 'admin.inicio';

    protected function getViewData(): array
    {
        $activos = User::where('activo', true)->get(['id', 'tipo', 'nombres', 'apellidos', 'debe_registrar_password', 'fecha_creacion']);
        $abiertos = Caso::whereNotIn('estado', EstadoCaso::finales());

        return [
            ...$this->momento(),
            'usuario' => $this->usuario(),
            'equipo' => collect([TipoUsuario::Administrador, TipoUsuario::Supervisor, TipoUsuario::Tecnico])
                ->map(fn (TipoUsuario $tipo): array => ['perfil' => $tipo->getLabel(), 'total' => $activos->where('tipo', $tipo)->count()]),
            'totalActivos' => $activos->count(),
            'inactivos' => User::where('activo', false)->count(),
            'sinContrasena' => $activos->where('debe_registrar_password', true)->sortByDesc('fecha_creacion')->take(5)->values(),
            'totalSinContrasena' => $activos->where('debe_registrar_password', true)->count(),
            'categoriasActivas' => Categoria::where('activa', true)->count(),
            'categorias' => Categoria::count(),
            'feriados' => Feriado::whereDate('fecha', '>=', today())->orderBy('fecha')->limit(4)->get(),
            'casosAbiertos' => (clone $abiertos)->count(),
            'casosVencidos' => (clone $abiertos)->where('vencido', true)->count(),
            'urlUsuarios' => UsuarioResource::getUrl('index'),
            'urlNuevoUsuario' => UsuarioResource::getUrl('create'),
            'urlCategorias' => CategoriaResource::getUrl('index'),
            'urlFeriados' => FeriadoResource::getUrl('index'),
        ];
    }
}
