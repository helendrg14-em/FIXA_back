<?php

namespace App\Filament\Resources\Categorias\RelationManagers;

use App\Enums\Prioridad;
use App\Models\MetaAtencion;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

/**
 * R03 / RN-06: plazos de atención por prioridad. Un plazo nuevo cierra el vigente de esa
 * prioridad en lugar de editarlo, para conservar el historial. La medida elegida es la
 * columna que se llena: plazo_horas o plazo_dias_habiles.
 */
class MetasAtencionRelationManager extends RelationManager
{
    protected static string $relationship = 'metasAtencion';

    protected static ?string $title = 'Plazos de atención';

    protected static ?string $modelLabel = 'plazo';

    protected static ?string $pluralModelLabel = 'plazos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('prioridad')->options(Prioridad::class)->required()->native(false),
            TextInput::make('plazo')->label('Plazo')->numeric()->integer()->required()->minValue(1)
                // Ley 2341: 6 meses es el máximo legal para resolver un trámite.
                ->maxValue(fn (Get $get): int => $get('medida') === 'plazo_horas' ? 4380 : 130),
            Select::make('medida')->label('Se cuenta en')->required()->native(false)->live()
                ->options(['plazo_horas' => 'Horas corridas', 'plazo_dias_habiles' => 'Días hábiles'])
                ->helperText('Los días hábiles no cuentan sábados, domingos ni feriados.'),
            DatePicker::make('vigente_desde')->label('Desde')->required()->native(false)
                ->helperText('Fecha de inicio del plazo.'),
            DatePicker::make('vigente_hasta')->label('Hasta')->native(false)
                ->helperText('Dejar en blanco si el plazo sigue vigente.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('vigente_desde', 'desc')
            ->emptyStateHeading('Sin plazos configurados')
            ->emptyStateDescription('Cada unidad define sus plazos según su capacidad (RN-06).')
            ->columns([
                TextColumn::make('prioridad')
                    ->formatStateUsing(fn (string $state): string => Prioridad::from($state)->getLabel()),
                TextColumn::make('plazo')->label('Plazo')
                    ->state(fn (MetaAtencion $record): string => $record->plazoLegible()),
                TextColumn::make('vigente_desde')->label('Desde')->date('d/m/Y'),
                TextColumn::make('vigente_hasta')->label('Hasta')->date('d/m/Y')->placeholder('—'),
                TextColumn::make('estado')->label('Estado')
                    ->state(fn (MetaAtencion $record): string => $record->vigente_hasta === null ? 'Vigente' : 'Histórico')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Vigente' ? 'success' : 'gray'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->modalHeading('Editar plazo de atención')
                    ->mutateRecordDataUsing(function (array $data, MetaAtencion $record): array {
                        // Hidrata el campo virtual "medida" a partir de qué columna tiene valor.
                        $data['medida']  = $record->plazo_horas !== null ? 'plazo_horas' : 'plazo_dias_habiles';
                        $data['plazo']   = $record->plazo_horas ?? $record->plazo_dias_habiles;
                        return $data;
                    })
                    ->using(function (MetaAtencion $record, array $data): MetaAtencion {
                        $medida  = $data['medida'];
                        $opuesta = $medida === 'plazo_horas' ? 'plazo_dias_habiles' : 'plazo_horas';

                        $record->update([
                            'prioridad'    => $data['prioridad'],
                            $medida        => $data['plazo'],
                            $opuesta       => null,
                            'vigente_desde' => $data['vigente_desde'],
                            'vigente_hasta' => $data['vigente_hasta'] ?? null,
                        ]);

                        return $record;
                    }),
            ])
            ->headerActions([
                CreateAction::make()->label('Definir plazo')->modalHeading('Definir plazo de atención')->createAnother(false)
                    ->using(function (array $data, RelationManager $livewire): MetaAtencion {
                        $categoria = $livewire->getOwnerRecord();
                        $prioridad = $data['prioridad'] instanceof Prioridad ? $data['prioridad']->value : $data['prioridad'];

                        return DB::transaction(function () use ($categoria, $prioridad, $data): MetaAtencion {
                            $categoria->metasAtencion()->where('prioridad', $prioridad)->whereNull('vigente_hasta')
                                ->update(['vigente_hasta' => today()]);

                            return $categoria->metasAtencion()->create([
                                'prioridad'    => $prioridad,
                                $data['medida'] => $data['plazo'],
                                'vigente_desde' => $data['vigente_desde'] ?? today(),
                                'vigente_hasta' => $data['vigente_hasta'] ?? null,
                            ]);
                        });
                    }),
            ]);
    }
}
