<?php

namespace App\Filament\Pages;

use App\Auth\ServicioAutenticacion;
use App\Filament\Resources\Casos\CasoResource;
use App\Filament\Resources\Casos\Pages\ViewCaso;
use App\Models\Caso;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ConsultarSeguimiento extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $title = 'Consultar seguimiento';

    protected static ?string $slug = 'seguimiento';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return ServicioAutenticacion::getInstance()->esAdministrador();
    }

    public function getSubheading(): ?string
    {
        return 'Busca por número de caso o código FX para abrir su historial y sus acciones.';
    }

    public function getPageClasses(): array
    {
        return [...parent::getPageClasses(), 'fx-casos-bandeja', 'fx-seguimiento-bandeja'];
    }

    public static function getNavigationItemActiveRoutePattern(): string|array
    {
        return [static::getRouteName(), ViewCaso::getRouteName()];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return CasoResource::table($table->query(fn () => CasoResource::getEloquentQuery()))
            ->defaultSort('id', 'desc')
            ->recordUrl(self::urlCaso(...))
            ->recordActions([
                Action::make('abrirSeguimiento')->label('Abrir seguimiento')->icon(Heroicon::OutlinedArrowRight)
                    ->tooltip('Abrir seguimiento')->extraAttributes(['class' => 'fx-caso-consultar'])
                    ->url(self::urlCaso(...)),
            ])
            ->emptyStateHeading('No se encontraron casos')
            ->emptyStateDescription('Revisa el número o el código y los filtros de la consulta.');
    }

    private static function urlCaso(Caso $record): string
    {
        return CasoResource::getUrl('view', ['record' => $record]);
    }
}
