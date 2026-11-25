<?php

namespace App\Filament\Pages;

use App\Auth\ServicioAutenticacion;
use App\Enums\EstadoCaso;
use App\Filament\Resources\Casos\AccionesCaso;
use App\Filament\Resources\Casos\CasoResource;
use App\Models\Caso;
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
use Illuminate\Database\Eloquent\Builder;

class ControlIntervenciones extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $title = 'Gestionar intervención';

    protected static ?string $slug = 'control-intervenciones';

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return ServicioAutenticacion::getInstance()->esAdministrador();
    }

    public function getSubheading(): ?string
    {
        return 'Programa cuadrillas y abre el trabajo de cada caso para auditar la evidencia, aprobar el cierre o devolverlo al técnico.';
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Caso::query()
                ->with(['categoria', 'unidad', 'programacionVigente.cuadrilla'])
                ->where(fn (Builder $query): Builder => $query
                    ->whereIn('estado', [
                        EstadoCaso::Asignada->value,
                        EstadoCaso::Programada->value,
                        EstadoCaso::EnAtencion->value,
                        EstadoCaso::PendienteValidacion->value,
                    ])
                    ->orHas('intervenciones')))
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderByRaw('case estado when ? then 0 when ? then 1 when ? then 2 when ? then 3 else 4 end', [
                    EstadoCaso::PendienteValidacion->value,
                    EstadoCaso::Asignada->value,
                    EstadoCaso::Programada->value,
                    EstadoCaso::EnAtencion->value,
                ])
                ->orderByDesc('id'))
            ->columns([
                TextColumn::make('id')->label('Caso')->prefix('#')->weight('bold')->sortable(),
                TextColumn::make('categoria.nombre')->label('Categoría')->wrap()
                    ->description(fn (Caso $record): ?string => $record->unidad?->sigla),
                TextColumn::make('estado')->badge()
                    ->color(fn (EstadoCaso $state): string => CasoResource::colorEstado($state)),
                TextColumn::make('programacionVigente.cuadrilla.nombre')->label('Cuadrilla')->placeholder('Sin programar')->wrap(),
                TextColumn::make('programacionVigente.fecha_prevista')->label('Fecha prevista')->date('d/m/Y')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('unidad_id')->label('Unidad')->relationship('unidad', 'sigla')->native(false),
                SelectFilter::make('estado')->options(EstadoCaso::class)->native(false),
            ])
            ->deferFilters(false)
            ->recordActions([
                AccionesCaso::programar(),
                Action::make('verTrabajo')
                    ->label(fn (Caso $record): string => $record->estado === EstadoCaso::PendienteValidacion
                        ? 'Revisar evidencia' : 'Ver caso')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Caso $record): string => CasoResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('No se encontraron intervenciones')
            ->emptyStateDescription('Aquí aparecen los casos que se pueden programar y los que ya tienen trabajo registrado.');
    }
}
