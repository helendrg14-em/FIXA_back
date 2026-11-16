<?php

namespace App\Filament\Resources\Revisiones;

use App\Api\ClienteFixaApi;
use App\Auth\ServicioAutenticacion;
use App\Filament\Resources\Casos\AccionesCaso;
use App\Filament\Resources\Casos\CasoResource;
use App\Filament\Resources\Revisiones\Pages\ListRevisiones;
use App\Models\SolicitudRevision;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Solicitudes de revisión del cierre (RF-18): el vecino no quedó conforme. Aceptar reabre el
 * caso (vuelve a Asignada); no aceptar exige explicarle por qué. Se responde por la API.
 */
class RevisionResource extends Resource
{
    protected static ?string $model = SolicitudRevision::class;

    protected static ?string $slug = 'revisiones';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftEllipsis;

    protected static ?string $navigationLabel = 'Revisiones';

    protected static ?string $modelLabel = 'solicitud de revisión';

    protected static ?string $pluralModelLabel = 'solicitudes de revisión';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return CasoResource::canAccess();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $usuario = ServicioAutenticacion::getInstance()->usuario();

        return parent::getEloquentQuery()
            ->whereHas('reporte.caso', fn (Builder $caso) => $caso->visiblesPara($usuario))
            ->with(['reporte.caso.categoria']);
    }

    public static function getNavigationBadge(): ?string
    {
        $pendientes = static::getEloquentQuery()->where('estado', 'pendiente')->count();

        return $pendientes > 0 ? (string) $pendientes : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('fecha_solicitud')
            ->columns([
                TextColumn::make('reporte.caso_id')->label('Caso')->prefix('#')
                    ->url(fn (SolicitudRevision $record): string => CasoResource::getUrl('view', ['record' => $record->reporte->caso_id])),
                TextColumn::make('reporte.codigo')->label('Código del vecino'),
                TextColumn::make('reporte.caso.categoria.nombre')->label('Categoría'),
                TextColumn::make('motivo')->label('Lo que dice el vecino')->wrap()->limit(160),
                TextColumn::make('fecha_solicitud')->label('Pedida')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('estado')->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendiente' => 'Pendiente', 'aprobada' => 'Caso reabierto', 'rechazada' => 'No aceptada', default => $state
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'warning', 'aprobada' => 'success', default => 'gray'
                    }),
                TextColumn::make('respuesta')->placeholder('—')->wrap()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')->default('pendiente')
                    ->options(['pendiente' => 'Pendientes', 'aprobada' => 'Caso reabierto', 'rechazada' => 'No aceptadas']),
            ])
            ->recordActions([
                Action::make('reabrir')->label('Reabrir el caso')->icon(Heroicon::OutlinedArrowPath)->color('warning')
                    ->visible(fn (SolicitudRevision $record): bool => $record->estado === 'pendiente')
                    ->requiresConfirmation()
                    ->modalDescription('El caso vuelve a «Asignada» para programar otra vez el trabajo. El historial se conserva.')
                    ->schema([Textarea::make('respuesta')->label('Mensaje para el vecino (opcional)')->maxLength(1000)])
                    ->action(fn (SolicitudRevision $record, array $data) => AccionesCaso::ejecutar(
                        fn (ClienteFixaApi $api) => $api->resolverRevision($record->id, true, $data['respuesta'] ?? null),
                        'Caso reabierto.')),
                Action::make('noAceptar')->label('No aceptar')->icon(Heroicon::OutlinedXMark)->color('gray')
                    ->visible(fn (SolicitudRevision $record): bool => $record->estado === 'pendiente')
                    ->modalDescription('El caso sigue cerrado. Explica al vecino por qué.')
                    ->schema([Textarea::make('respuesta')->label('Respuesta para el vecino')->required()->maxLength(1000)])
                    ->action(fn (SolicitudRevision $record, array $data) => AccionesCaso::ejecutar(
                        fn (ClienteFixaApi $api) => $api->resolverRevision($record->id, false, trim($data['respuesta'])),
                        'Respuesta enviada.')),
            ])
            ->emptyStateHeading('No hay solicitudes de revisión');
    }

    public static function getPages(): array
    {
        return ['index' => ListRevisiones::route('/')];
    }
}
