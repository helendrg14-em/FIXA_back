<?php

namespace App\Filament\Resources\Categorias;

use App\Enums\CodigoCategoria;
use App\Filament\Resources\Categorias\Pages\CreateCategoria;
use App\Filament\Resources\Categorias\Pages\EditCategoria;
use App\Filament\Resources\Categorias\Pages\ListCategorias;
use App\Filament\Resources\Categorias\RelationManagers\MetasAtencionRelationManager;
use App\Models\Categoria;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

/**
 * R03: las categorias se configuran y se desactivan, nunca se borran.
 */
class CategoriaResource extends Resource
{
    protected static ?string $model = Categoria::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Gestionar categorías';

    protected static ?string $modelLabel = 'categoría';

    protected static ?string $pluralModelLabel = 'categorías';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos de la categoría')->columns(2)->schema([
                // No se edita después de crear: Factory Method depende de este código.
                Select::make('codigo')->label('Tipo de categoría')->options(CodigoCategoria::class)->required()->native(false)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(['unique' => 'Ya existe una categoría de este tipo.'])
                    ->disabled(fn (?Categoria $record): bool => $record !== null),
                TextInput::make('nombre')->required()->maxLength(60)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(['unique' => 'Ya existe una categoría con este nombre.']),
                Select::make('unidad_id')->label('Unidad responsable')->relationship('unidad', 'nombre')->required()->native(false),
                Textarea::make('descripcion')->label('Descripción')->required()->rows(3)->columnSpanFull(),
                TextInput::make('radio_cercania_m')->label('Radio para detectar casos cercanos')
                    ->numeric()->integer()->required()->minValue(10)->maxValue(5000)->suffix('metros')
                    ->helperText('Se usa para avisar al vecino si ya hay un caso abierto cerca.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nombre')
            ->columns([
                TextColumn::make('nombre')->searchable(),
                TextColumn::make('unidad.nombre')->label('Unidad responsable'),
                TextColumn::make('radio_cercania_m')->label('Radio')->suffix(' m'),
                IconColumn::make('activa')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                Action::make('desactivar')->label('Desactivar')->icon(Heroicon::OutlinedNoSymbol)->color('danger')
                    ->visible(fn (Categoria $record): bool => $record->activa)
                    ->authorize(fn (Categoria $record): bool => Gate::allows('update', $record))
                    ->requiresConfirmation()
                    ->modalHeading('Desactivar la categoría')
                    ->modalDescription('Los vecinos ya no podrán elegirla al reportar. Los casos existentes se conservan.')
                    ->modalSubmitActionLabel('Desactivar')
                    ->action(function (Categoria $record): void {
                        $record->update(['activa' => false]);
                        Notification::make()->title('Categoría desactivada')->success()->send();
                    }),
                Action::make('reactivar')->label('Reactivar')->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (Categoria $record): bool => ! $record->activa)
                    ->authorize(fn (Categoria $record): bool => Gate::allows('update', $record))
                    ->requiresConfirmation()
                    ->modalHeading('Reactivar la categoría')
                    ->modalSubmitActionLabel('Reactivar')
                    ->action(function (Categoria $record): void {
                        $record->update(['activa' => true]);
                        Notification::make()->title('Categoría reactivada')->success()->send();
                    }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [MetasAtencionRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategorias::route('/'),
            'create' => CreateCategoria::route('/create'),
            'edit' => EditCategoria::route('/{record}/edit'),
        ];
    }
}
