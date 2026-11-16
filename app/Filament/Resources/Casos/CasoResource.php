<?php

namespace App\Filament\Resources\Casos;

use App\Auth\ServicioAutenticacion;
use App\Enums\EstadoCaso;
use App\Enums\Prioridad;
use App\Enums\TipoUsuario;
use App\Filament\Resources\Casos\Pages\ListCasos;
use App\Filament\Resources\Casos\Pages\ViewCaso;
use App\Models\Caso;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Bandeja de casos (RF-05 a RF-12). El supervisor ve los de su unidad y el administrador todos.
 * La lista y el detalle se leen de la base; los cambios se hacen con AccionesCaso, por la API.
 */
class CasoResource extends Resource
{
    protected static ?string $model = Caso::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?string $navigationLabel = 'Casos';

    protected static ?string $modelLabel = 'caso';

    protected static ?string $pluralModelLabel = 'casos';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $usuario = ServicioAutenticacion::getInstance()->usuario();

        return $usuario !== null && in_array($usuario->tipo, [TipoUsuario::Administrador, TipoUsuario::Supervisor], true);
    }

    public static function canView(Model $record): bool
    {
        return static::getEloquentQuery()->whereKey($record->getKey())->exists();
    }

    public static function canCreate(): bool
    {
        return false; // los reportes nuevos se cargan con «Nuevo reporte»
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->visiblesPara(ServicioAutenticacion::getInstance()->usuario())
            ->with(['categoria', 'unidad'])
            ->withCount('reportes');
    }

    public static function getNavigationBadge(): ?string
    {
        $abiertos = static::getEloquentQuery()->whereIn('estado', [EstadoCaso::Recibida->value, EstadoCaso::PendienteValidacion->value])->count();

        return $abiertos > 0 ? (string) $abiertos : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Recibidos y pendientes de validar';
    }

    public static function table(Table $table): Table
    {
        $esAdmin = ServicioAutenticacion::getInstance()->esAdministrador();

        return $table
            // Primero los vencidos y luego los que vencen antes, como la bandeja de la API.
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('vencido')->orderByRaw('fecha_objetivo is null, fecha_objetivo')->orderByDesc('id'))
            ->columns([
                // En celular quedan caso, categoría y estado; lo demás aparece desde tablet.
                TextColumn::make('id')->label('Caso')->prefix('#')->sortable()->weight('bold')
                    ->icon(fn (Caso $record): ?Heroicon => $record->vencido ? Heroicon::ExclamationTriangle : null)
                    ->iconColor('danger')->tooltip(fn (Caso $record): ?string => $record->vencido ? 'Vencido' : null),
                TextColumn::make('categoria.nombre')->label('Categoría')->wrap()
                    ->description(fn (Caso $record): ?string => $esAdmin ? $record->unidad?->sigla : null),
                TextColumn::make('estado')->badge()->color(fn (EstadoCaso $state): string => self::colorEstado($state)),
                TextColumn::make('prioridad')->badge()->placeholder('Sin asignar')->visibleFrom('md')
                    ->color(fn (?Prioridad $state): string => match ($state) {
                        Prioridad::Critica => 'danger', Prioridad::Alta => 'warning', default => 'gray',
                    }),
                TextColumn::make('reportes_count')->label('Vecinos')->alignCenter()->visibleFrom('md'),
                TextColumn::make('fecha_creacion')->label('Recibido')->since()->sortable()
                    ->tooltip(fn (Caso $record): ?string => $record->fecha_creacion?->format('d/m/Y H:i'))->visibleFrom('md'),
                TextColumn::make('fecha_objetivo')->label('Vence')->date('d/m/Y')->placeholder('—')->sortable()->visibleFrom('md')
                    ->color(fn (Caso $record): ?string => $record->vencido ? 'danger' : null),
            ])
            // El estado va en las pestañas (ListCasos); aquí quedan los filtros finos, en una fila
            // sobre la tabla que se pliega, en vez de un desplegable más alto que la pantalla.
            ->filters([
                SelectFilter::make('categoria_id')->label('Categoría')->relationship('categoria', 'nombre')->native(false),
                SelectFilter::make('unidad_id')->label('Unidad')->relationship('unidad', 'sigla')->native(false)->visible($esAdmin),
                SelectFilter::make('prioridad')->options(Prioridad::class)->native(false),
                Filter::make('vencido')->label('Solo vencidos')->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('vencido', true)),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(['default' => 1, 'sm' => 2, 'lg' => $esAdmin ? 4 : 3])
            ->deferFilters(false)
            ->recordUrl(fn (Caso $record): string => self::getUrl('view', ['record' => $record]))
            ->recordActions([ViewAction::make()->label('Abrir')])
            ->emptyStateIcon(Heroicon::OutlinedInboxStack)
            ->emptyStateHeading('No hay casos aquí')
            ->emptyStateDescription('Los reportes de la app aparecen apenas se envían. Prueba otra pestaña o quita los filtros.');
    }

    public static function infolist(Schema $schema): Schema
    {
        // Una franja de datos arriba y, debajo, lo que dijeron los vecinos junto al historial.
        // Los datos vacíos no se muestran: un caso nuevo no tiene que llenar la pantalla de «—».
        $vacio = fn (mixed $state): bool => blank($state);

        return $schema->columns(1)->components([
            Section::make()->compact()->extraAttributes(['class' => 'fx-caso-resumen'])
                ->columns(['default' => 2, 'md' => 3, 'xl' => 6])
                ->schema([
                    TextEntry::make('estado')->badge()->color(fn (EstadoCaso $state): string => self::colorEstado($state)),
                    TextEntry::make('prioridad')->badge()->placeholder('Sin asignar')
                        ->color(fn (?Prioridad $state): string => match ($state) {
                            Prioridad::Critica => 'danger', Prioridad::Alta => 'warning', default => 'gray',
                        }),
                    TextEntry::make('categoria.nombre')->label('Categoría'),
                    TextEntry::make('unidad.nombre')->label('Unidad'),
                    TextEntry::make('fecha_creacion')->label('Recibido')->dateTime('d/m/Y H:i'),
                    TextEntry::make('fecha_objetivo')->label('Vence')->date('d/m/Y')
                        ->placeholder('Al fijar la prioridad')
                        ->color(fn (Caso $record): ?string => $record->vencido ? 'danger' : null)
                        ->suffix(fn (Caso $record): string => $record->vencido ? ' · vencido' : ''),
                    TextEntry::make('programacion')->label('Programado')
                        ->state(fn (Caso $record): ?string => ($p = $record->programaciones()->where('vigente', true)->with('cuadrilla')->first())
                            ? "{$p->cuadrilla?->nombre} · {$p->fecha_prevista->format('d/m/Y')}" : null)
                        ->hidden($vacio),
                    TextEntry::make('fecha_cierre')->label('Cerrado')->dateTime('d/m/Y H:i')->hidden($vacio),
                    TextEntry::make('requiere_verificacion_campo')->label('Sin foto')->hiddenLabel()
                        ->state('Verificar en campo: llegó sin foto')->badge()->color('warning')->icon(Heroicon::OutlinedCamera)
                        ->hidden(fn (Caso $record): bool => ! $record->requiere_verificacion_campo),
                    TextEntry::make('justificacion_prioridad')->label('Por qué esa prioridad')->columnSpanFull()->hidden($vacio),
                ]),
            Grid::make(['default' => 1, 'lg' => 3])->schema([
                Grid::make(1)->columnSpan(['default' => 1, 'lg' => 2])->schema([
                    Section::make('Trabajo de la cuadrilla')->compact()->icon(Heroicon::OutlinedWrenchScrewdriver)
                        ->hidden(fn (Caso $record): bool => ! $record->intervenciones()->exists())
                        ->schema([ViewEntry::make('intervenciones')->hiddenLabel()->view('panel.caso.intervenciones')]),
                    Section::make(fn (Caso $record): string => $record->reportes_count > 1
                        ? "Lo que reportaron {$record->reportes_count} vecinos" : 'Lo que reportó el vecino')
                        ->compact()->icon(Heroicon::OutlinedChatBubbleBottomCenterText)
                        ->schema([ViewEntry::make('reportes')->hiddenLabel()->view('panel.caso.reportes')]),
                ]),
                Section::make('Historial')->compact()->icon(Heroicon::OutlinedClock)->columnSpan(1)
                    ->schema([ViewEntry::make('eventos')->hiddenLabel()->view('panel.caso.historial')]),
            ]),
        ]);
    }

    public static function colorEstado(EstadoCaso $estado): string
    {
        return match ($estado) {
            EstadoCaso::Recibida, EstadoCaso::EnRevision => 'warning',
            EstadoCaso::PendienteValidacion => 'info',
            EstadoCaso::Cerrada => 'success',
            EstadoCaso::Rechazada, EstadoCaso::Duplicada, EstadoCaso::Derivada => 'gray',
            default => 'primary',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCasos::route('/'),
            'view' => ViewCaso::route('/{record}'),
        ];
    }
}
