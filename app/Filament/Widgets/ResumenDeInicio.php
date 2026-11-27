<?php

namespace App\Filament\Widgets;

use App\Enums\TipoUsuario;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

/**
 * Base de los resúmenes de inicio: cada perfil tiene el suyo y solo lo ve ese perfil.
 */
abstract class ResumenDeInicio extends Widget
{
    protected static TipoUsuario $perfil;

    protected static ?bool $isLazyLoaded = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Filament::auth()->user()?->rol === static::$perfil;
    }

    protected function usuario(): User
    {
        return Filament::auth()->user();
    }

    /**
     * @return array{saludo: string, fecha: string}
     */
    protected function momento(): array
    {
        $ahora = Carbon::now(config('fixa.zona_horaria'))->locale('es');

        return [
            'saludo' => match (true) {
                $ahora->hour < 12 => 'Buenos días',
                $ahora->hour < 19 => 'Buenas tardes',
                default => 'Buenas noches',
            },
            'fecha' => ucfirst($ahora->translatedFormat('l j \d\e F')),
        ];
    }
}
