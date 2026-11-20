<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard;
use Illuminate\Contracts\Support\Htmlable;

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
