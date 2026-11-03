<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Escritorio del panel. Cada perfil ve su propio resumen (app/Filament/Widgets/{Admin,Supervisor,Tecnico}),
 * que ya trae el saludo; por eso la página no muestra un título propio.
 */
class Inicio extends Dashboard
{
    protected static ?string $title = 'Inicio';

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
