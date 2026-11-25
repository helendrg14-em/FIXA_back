<?php

namespace App\Filament\Pages;

use App\Auth\ServicioAutenticacion;
use App\Enums\CanalOrigen;
use App\Enums\EstadoCaso;
use App\Filament\Resources\Casos\CasoResource;
use App\Models\Reporte;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GestionarReportes extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $title = 'Gestionar reportes';

    protected static ?string $slug = 'reportes';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return ServicioAutenticacion::getInstance()->esAdministrador();
    }

    public function getSubheading(): ?string
    {
        return 'Registra reportes por teléfono o en ventanilla y consulta cada reporte en su caso.';
    }

    public static function getNavigationItemActiveRoutePattern(): string|array
    {
        return [static::getRouteName(), NuevoReporte::getRouteName(), CasoResource::getRouteBaseName().'.view-reportes'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nuevoReporte')->label('Registrar reporte')->icon(Heroicon::OutlinedPlus)
                ->url(NuevoReporte::getUrl()),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Reporte::query()
                ->select(['id', 'caso_id', 'codigo', 'canal_origen', 'fecha_registro'])
                ->with(['caso:id,categoria_id,estado', 'caso.categoria:id,nombre']))
            ->defaultSort('fecha_registro', 'desc')
            ->searchPlaceholder('Código del reporte')
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->weight('bold'),
                TextColumn::make('caso_id')->label('Caso')->prefix('#'),
                TextColumn::make('caso.categoria.nombre')->label('Categoría')->wrap(),
                TextColumn::make('canal_origen')->label('Canal'),
                TextColumn::make('caso.estado')->label('Estado del caso')->badge()
                    ->color(fn (EstadoCaso $state): string => CasoResource::colorEstado($state)),
                TextColumn::make('fecha_registro')->label('Recibido')->dateTime('d/m/Y H:i')->sortable()->visibleFrom('md'),
            ])
            ->filters([
                SelectFilter::make('canal_origen')->label('Canal')->options(CanalOrigen::class)->native(false),
            ])
            ->deferFilters(false)
            ->recordActions([
                Action::make('verReporte')->label('Ver en el caso')->icon(Heroicon::OutlinedEye)
                    ->url(fn (Reporte $record): string => CasoResource::getUrl('view-reportes', ['record' => $record->caso_id]).'#reporte-'.$record->id),
            ])
            ->emptyStateHeading('No se encontraron reportes');
    }
}
