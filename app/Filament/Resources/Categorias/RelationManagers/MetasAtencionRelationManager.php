<?php

namespace App\Filament\Resources\Categorias\RelationManagers;

use App\Enums\Prioridad;
use App\Models\Categoria;
use App\Models\MetaAtencion;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * R03 / RN-06: cada cambio registra una versión del plazo para conservar el historial.
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
            DatePicker::make('vigente_desde')->label('Desde')->required()->default(fn () => self::hoy())
                ->minDate(fn () => self::hoy())->native(false)
                ->helperText('El plazo anterior se aplica hasta el día previo a este cambio.'),
            DatePicker::make('vigente_hasta')->label('Hasta')->native(false)->afterOrEqual('vigente_desde')
                ->helperText('Deja en blanco para aplicar el plazo hasta que empiece el siguiente.'),
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
                    ->state(fn (MetaAtencion $record): string => match (true) {
                        $record->vigente_desde->toDateString() > self::hoy()->toDateString() => 'Programado',
                        $record->vigente_hasta !== null && $record->vigente_hasta->toDateString() < self::hoy()->toDateString() => 'Histórico',
                        default => 'Vigente',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Vigente' => 'success', 'Programado' => 'info', default => 'gray',
                    }),
            ])
            ->headerActions([
                CreateAction::make()->label('Definir plazo')->modalHeading('Definir plazo de atención')->createAnother(false)
                    ->using(fn (array $data): MetaAtencion => $this->guardarPlazo($data)),
            ]);
    }

    private function guardarPlazo(array $data): MetaAtencion
    {
        $inicio = CarbonImmutable::parse($data['vigente_desde'], config('fixa.zona_horaria'));
        $desde = $inicio->toDateString();
        $hasta = $data['vigente_hasta'] ?? null;
        $prioridad = $data['prioridad'] instanceof Prioridad ? $data['prioridad']->value : $data['prioridad'];
        $formulario = $this->getMountedActionSchema()->getStatePath();

        return DB::transaction(function () use ($data, $desde, $hasta, $inicio, $prioridad, $formulario): MetaAtencion {
            $categoria = Categoria::query()->lockForUpdate()->findOrFail($this->getOwnerRecord()->getKey());
            $plazos = $categoria->metasAtencion()->where('prioridad', $prioridad);
            $siguiente = (clone $plazos)->where('vigente_desde', '>=', $desde)->orderBy('vigente_desde')->first();

            if ($siguiente?->vigente_desde->toDateString() === $desde) {
                throw ValidationException::withMessages([
                    "{$formulario}.vigente_desde" => 'Ya hay un plazo para esa prioridad que comienza ese día. Elige otra fecha.',
                ]);
            }

            if ($siguiente !== null) {
                if ($hasta !== null && $hasta >= $siguiente->vigente_desde->toDateString()) {
                    throw ValidationException::withMessages([
                        "{$formulario}.vigente_hasta" => 'El plazo debe terminar antes de que comience el siguiente plazo programado.',
                    ]);
                }

                $hasta ??= $siguiente->vigente_desde->copy()->subDay()->toDateString();
            }

            $plazos->where('vigente_desde', '<', $desde)
                ->where(fn ($query) => $query->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $desde))
                ->update(['vigente_hasta' => $inicio->subDay()->toDateString()]);

            return $categoria->metasAtencion()->create([
                'prioridad' => $prioridad,
                $data['medida'] => $data['plazo'],
                'vigente_desde' => $desde,
                'vigente_hasta' => $hasta,
            ]);
        });
    }

    private static function hoy(): CarbonInterface
    {
        return today(config('fixa.zona_horaria'));
    }
}
