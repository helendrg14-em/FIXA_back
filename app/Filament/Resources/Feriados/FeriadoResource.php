<?php

namespace App\Filament\Resources\Feriados;

use App\Filament\Resources\Feriados\Pages\ManageFeriados;
use App\Models\Feriado;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FeriadoResource extends Resource
{
    protected static ?string $model = Feriado::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Feriados';

    protected static ?string $modelLabel = 'feriado';

    protected static ?string $pluralModelLabel = 'feriados';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('fecha')->required()->native(false)->displayFormat('d/m/Y')->closeOnDateSelection()
                ->unique(ignoreRecord: true)
                ->validationMessages(['unique' => 'Esta fecha ya está registrada.']),
            TextInput::make('nombre')->required()->maxLength(100),
            TextInput::make('fuente')->required()->maxLength(200)->columnSpanFull()
                ->helperText('Decreto o publicación oficial que declara el feriado.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('fecha')
            ->emptyStateHeading('Sin feriados registrados')
            ->emptyStateDescription('Sin feriados, los plazos en días hábiles solo saltan sábados y domingos.')
            ->columns([
                TextColumn::make('fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('nombre')->searchable(),
                TextColumn::make('fuente')->wrap(),
            ])
            ->recordActions([
                // Después de editar o borrar, el calendario de arriba se vuelve a dibujar.
                EditAction::make()->modalHeading('Editar feriado')->after(fn ($livewire) => $livewire->dispatch('feriados-actualizados')),
                DeleteAction::make()->after(fn ($livewire) => $livewire->dispatch('feriados-actualizados')),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageFeriados::route('/')];
    }
}
