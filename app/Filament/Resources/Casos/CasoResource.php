<?php

namespace App\Filament\Resources\Casos;

use App\Auth\ServicioAutenticacion;
use App\Enums\EstadoCaso;
use App\Enums\Prioridad;
use App\Enums\TipoUsuario;
use App\Filament\Resources\Casos\Pages\ListCasos;
use App\Filament\Resources\Casos\Pages\ViewCaso;
use App\Models\Caso;
use App\Models\EventoHistorial;
use App\Models\Intervencion;
use App\Models\Reporte;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
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
                TextColumn::make('id')->label('Caso')->prefix('#')->sortable(),
                TextColumn::make('categoria.nombre')->label('Categoría'),
                TextColumn::make('unidad.sigla')->label('Unidad')->visible($esAdmin),
                TextColumn::make('estado')->badge()->color(fn (EstadoCaso $state): string => self::colorEstado($state)),
                TextColumn::make('prioridad')->badge()->placeholder('Sin asignar')
                    ->color(fn (?Prioridad $state): string => match ($state) {
                        Prioridad::Critica => 'danger', Prioridad::Alta => 'warning', default => 'gray',
                    }),
                TextColumn::make('reportes_count')->label('Vecinos')->alignCenter(),
                TextColumn::make('fecha_creacion')->label('Recibido')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('fecha_objetivo')->label('Fecha objetivo')->dateTime('d/m/Y')->placeholder('—')->sortable(),
                IconColumn::make('vencido')->boolean()->trueColor('danger')->falseIcon(null),
            ])
            ->filters([
                SelectFilter::make('estado')->options(EstadoCaso::class)->multiple()
                    ->default(array_values(array_diff(array_column(EstadoCaso::cases(), 'value'), EstadoCaso::finales()))),
                SelectFilter::make('categoria_id')->label('Categoría')->relationship('categoria', 'nombre'),
                SelectFilter::make('unidad_id')->label('Unidad')->relationship('unidad', 'nombre')->visible($esAdmin),
                SelectFilter::make('prioridad')->options(Prioridad::class),
                TernaryFilter::make('vencido'),
            ])
            ->recordUrl(fn (Caso $record): string => self::getUrl('view', ['record' => $record]))
            ->recordActions([ViewAction::make()->label('Abrir')])
            ->emptyStateHeading('No hay casos con estos filtros')
            ->emptyStateDescription('Los reportes de la app aparecen aquí apenas se envían.');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Resumen')->columnSpan(1)->schema([
                TextEntry::make('estado')->badge()->color(fn (EstadoCaso $state): string => self::colorEstado($state)),
                TextEntry::make('prioridad')->badge()->placeholder('Sin asignar'),
                TextEntry::make('justificacion_prioridad')->label('Justificación')->placeholder('—'),
                TextEntry::make('categoria.nombre')->label('Categoría'),
                TextEntry::make('unidad.nombre')->label('Unidad responsable'),
                TextEntry::make('fecha_creacion')->label('Recibido')->dateTime('d/m/Y H:i'),
                TextEntry::make('fecha_objetivo')->label('Fecha objetivo')->dateTime('d/m/Y')->placeholder('Se calcula al fijar la prioridad'),
                TextEntry::make('fecha_cierre')->label('Cerrado')->dateTime('d/m/Y H:i')->placeholder('—'),
                IconEntry::make('vencido')->boolean()->trueColor('danger'),
                IconEntry::make('requiere_verificacion_campo')->label('Verificar en campo (sin foto)')->boolean()->trueColor('warning'),
                TextEntry::make('programacion')->label('Programado')
                    ->state(fn (Caso $record): ?string => ($p = $record->programaciones()->where('vigente', true)->with('cuadrilla')->first())
                        ? "{$p->cuadrilla?->nombre} · {$p->fecha_prevista->format('d/m/Y')}" : null)
                    ->placeholder('Sin programar'),
            ]),
            Section::make('Reportes de los vecinos')->columnSpan(2)->schema([
                RepeatableEntry::make('reportes')->hiddenLabel()->contained(false)
                    ->state(fn (Caso $record) => $record->reportes()->with(['fotos', 'distrito', 'operador'])->orderBy('fecha_registro')->get())
                    ->columns(2)
                    ->schema([
                        TextEntry::make('codigo')->label('Código')->weight('bold')
                            ->suffix(fn (Reporte $record): string => $record->es_apoyo ? ' · apoyo' : ''),
                        TextEntry::make('fecha_registro')->label('Fecha')->dateTime('d/m/Y H:i'),
                        TextEntry::make('tipo_incidencia')->label('Tipo')->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                        TextEntry::make('canal_origen')->label('Canal')
                            ->suffix(fn (Reporte $record): string => $record->operador ? ' · '.$record->operador->getFilamentName() : ''),
                        TextEntry::make('descripcion')->label('Descripción')->columnSpanFull()->placeholder('Sin descripción (apoyo)'),
                        TextEntry::make('distrito.nombre')->label('Distrito'),
                        TextEntry::make('numero_luminarias')->label('Luminarias')->placeholder('—'),
                        TextEntry::make('direccion')->label('Dirección')->placeholder('—'),
                        TextEntry::make('motivo_sin_foto')->label('Motivo sin foto')->placeholder('—'),
                        ImageEntry::make('fotos.url')->label('Fotos')->columnSpanFull()->imageHeight(160)->placeholder('Sin foto'),
                    ]),
            ]),
            Section::make('Trabajo de la cuadrilla')->columnSpanFull()
                ->visible(fn (Caso $record): bool => $record->intervenciones()->exists())
                ->schema([
                    RepeatableEntry::make('intervenciones')->hiddenLabel()->columns(3)
                        ->state(fn (Caso $record) => $record->intervenciones()->with(['evidencias', 'tecnico'])->orderByDesc('fecha')->get())
                        ->schema([
                            TextEntry::make('fecha')->dateTime('d/m/Y H:i'),
                            TextEntry::make('tecnico_nombre')->label('Técnico')->state(fn (Intervencion $record): ?string => $record->tecnico?->getFilamentName()),
                            TextEntry::make('estado_validacion')->label('Validación')->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'aprobada' => 'success', 'rechazada' => 'danger', default => 'warning'
                                }),
                            TextEntry::make('trabajo_realizado')->label('Trabajo realizado')->columnSpanFull(),
                            TextEntry::make('observacion_validacion')->label('Observación')->columnSpanFull()->placeholder('—'),
                            ImageEntry::make('evidencias.url')->label('Evidencia (después)')->columnSpanFull()->imageHeight(160),
                        ]),
                ]),
            Section::make('Historial')->columnSpanFull()->collapsible()->schema([
                RepeatableEntry::make('eventos')->hiddenLabel()->contained(false)->columns(4)
                    ->state(fn (Caso $record) => $record->eventos()->orderBy('fecha_hora')->orderBy('id')->get())
                    ->schema([
                        TextEntry::make('fecha_hora')->hiddenLabel()->dateTime('d/m/Y H:i'),
                        TextEntry::make('estado_nuevo')->hiddenLabel()->badge()->placeholder('—')
                            ->formatStateUsing(fn (?string $state): string => EstadoCaso::tryFrom((string) $state)?->getLabel() ?? (string) $state),
                        TextEntry::make('detalle')->hiddenLabel()->columnSpan(2)
                            ->suffix(fn (EventoHistorial $record): string => $record->motivo ? " — {$record->motivo}" : ''),
                    ]),
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
