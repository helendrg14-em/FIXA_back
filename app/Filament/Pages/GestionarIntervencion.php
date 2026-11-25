<?php

namespace App\Filament\Pages;

use App\Api\ClienteFixaApi;
use App\Api\ErrorApi;
use App\Auth\ServicioAutenticacion;
use App\Enums\EstadoCaso;
use App\Enums\Prioridad;
use App\Enums\TipoUsuario;
use App\Filament\Resources\Casos\CasoResource;
use App\Models\Caso;
use App\Models\Intervencion;
use App\Models\Programacion;
use App\Models\Reporte;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Intervención del técnico (R10): ve las órdenes de su cuadrilla, inicia la atención y registra
 * el trabajo con la foto del después. No cierra el caso: lo valida el supervisor (RN-04).
 * La lista se lee de la base; iniciar y registrar van por la API, como el resto de cambios.
 */
class GestionarIntervencion extends Page implements HasTable
{
    use InteractsWithTable;

    private const ESTADOS = [EstadoCaso::Programada, EstadoCaso::EnAtencion, EstadoCaso::PendienteValidacion];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Gestionar intervención';

    protected static ?string $title = 'Gestionar intervención';

    protected static ?string $slug = 'intervenciones';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return ServicioAutenticacion::getInstance()->usuario()?->tipo === TipoUsuario::Tecnico;
    }

    public function getSubheading(): ?string
    {
        return $this->cuadrilla() === null
            ? 'Aún no perteneces a una cuadrilla. Pide a tu supervisor que te asigne a una.'
            : 'Casos programados para tu cuadrilla. Inicia la atención en el lugar y registra el trabajo con la foto del después.';
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->ordenes())
            ->defaultSort(fn (Builder $query): Builder => $query->orderBy('fecha_prevista')->orderBy('id'))
            // Una ficha por orden, pensada para el celular: a la izquierda qué y dónde; a la
            // derecha en qué estado está y para cuándo. En pantallas grandes las dos partes van en una fila.
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('id')->weight('bold')
                            ->formatStateUsing(fn (int $state, Caso $record): string => "Caso N.º {$state} · {$record->categoria?->nombre}"),
                        TextColumn::make('direccion')->wrap()->color('gray')
                            ->placeholder('Sin dirección: mira la ubicación en el detalle')
                            ->formatStateUsing(fn (?string $state, Caso $record): ?string => filled($record->referencia) ? "{$state} · {$record->referencia}" : $state),
                        // Solo aparece cuando el supervisor devolvió el trabajo: el detalle dice qué corregir.
                        TextColumn::make('correccion')->color('warning')->weight('semibold')
                            ->state(fn (Caso $record): ?string => $record->estado === EstadoCaso::EnAtencion && $record->rechazos > 0
                                ? 'El supervisor pidió corregir el trabajo' : null),
                    ])->space(1),
                    Stack::make([
                        TextColumn::make('estado')->badge()
                            ->formatStateUsing(fn (EstadoCaso $state): string => $state === EstadoCaso::PendienteValidacion ? 'Esperando validación' : $state->getLabel())
                            ->color(fn (EstadoCaso $state): string => CasoResource::colorEstado($state)),
                        TextColumn::make('fecha_prevista')->size('sm')
                            ->formatStateUsing(fn (Caso $record): string => $this->cuando($record))
                            ->color(fn (Caso $record): ?string => $this->atrasada($record) ? 'danger' : 'gray'),
                        TextColumn::make('prioridad')->badge()->size('sm')
                            ->formatStateUsing(fn (Prioridad $state): string => 'Prioridad '.mb_strtolower($state->getLabel()))
                            ->color(fn (?Prioridad $state): string => match ($state) {
                                Prioridad::Critica => 'danger', Prioridad::Alta => 'warning', default => 'gray',
                            }),
                    ])->space(1)->alignment(Alignment::End)->grow(false),
                ])->from('xl'),
            ])
            ->recordActions([
                $this->detalle(),
                $this->iniciar(),
                $this->registrar(),
            ])
            ->recordAction('detalle')
            ->paginated([10, 25])
            ->emptyStateIcon(Heroicon::OutlinedCalendar)
            ->emptyStateHeading($this->cuadrilla() === null ? 'Sin cuadrilla asignada' : 'No hay trabajo pendiente')
            ->emptyStateDescription($this->cuadrilla() === null
                ? 'Cuando tu supervisor te asigne a una cuadrilla, sus casos aparecerán aquí.'
                : 'Cuando el supervisor programe un caso para tu cuadrilla, aparecerá aquí con su fecha.');
    }

    private function detalle(): Action
    {
        return Action::make('detalle')->label('Ver detalle')->icon(Heroicon::OutlinedEye)->color('gray')
            ->modalHeading(fn (Caso $record): string => "Caso N.º {$record->id} · {$record->categoria?->nombre}")
            ->slideOver()
            ->schema(fn (Caso $record): array => [View::make('tecnico.orden')->viewData(['caso' => $record])])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar');
    }

    private function iniciar(): Action
    {
        return Action::make('iniciar')->label('Iniciar atención')->icon(Heroicon::OutlinedPlay)->button()->extraAttributes(['class' => 'fx-boton-orden'])
            ->visible(fn (Caso $record): bool => $record->estado === EstadoCaso::Programada)
            ->requiresConfirmation()
            ->modalHeading('Iniciar atención')
            ->modalDescription(fn (Caso $record): string => "Caso N.º {$record->id}. Hazlo cuando la cuadrilla llegue al lugar: el vecino verá que su caso está en atención.")
            ->modalSubmitActionLabel('Iniciar atención')
            ->action(fn (Caso $record, ClienteFixaApi $api) => $this->ejecutar(
                fn () => $api->iniciarAtencion($record->id), 'Atención iniciada. Cuando termines, registra la intervención.'));
    }

    private function registrar(): Action
    {
        return Action::make('registrar')->label('Registrar intervención')->icon(Heroicon::OutlinedCamera)->button()->extraAttributes(['class' => 'fx-boton-orden'])
            ->visible(fn (Caso $record): bool => $record->estado === EstadoCaso::EnAtencion)
            ->modalHeading('Registrar intervención')
            ->modalDescription(fn (Caso $record): string => "Caso N.º {$record->id}. El supervisor revisará la evidencia y cerrará el caso.")
            ->modalSubmitActionLabel('Enviar al supervisor')
            ->schema([
                Textarea::make('trabajo')->label('Trabajo realizado')->required()->minLength(10)->maxLength(2000)->rows(4)->autosize()
                    ->placeholder('Qué se hizo, con qué materiales y cómo quedó.'),
                Radio::make('tipo')->label('Evidencia')->required()->live()->default('fotografia')
                    ->options(['fotografia' => 'Foto del después', 'acta_tecnica' => 'Acta técnica'])
                    ->descriptions([
                        'fotografia' => 'Una foto del lugar ya arreglado.',
                        'acta_tecnica' => 'Cuando el trabajo no se puede fotografiar, por ejemplo una reparación subterránea.',
                    ]),
                Textarea::make('descripcion')->label('¿Por qué un acta técnica?')->rows(2)->maxLength(1000)
                    ->visible(fn (Get $get): bool => $get('tipo') === 'acta_tecnica')
                    ->required(fn (Get $get): bool => $get('tipo') === 'acta_tecnica'),
                FileUpload::make('fotos')
                    ->label(fn (Get $get): string => $get('tipo') === 'acta_tecnica' ? 'Foto del acta' : 'Foto del después')
                    ->required()->multiple()->maxFiles(3)->image()->panelLayout('grid')->imagePreviewHeight('120')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(3072)->storeFiles(false)
                    ->helperText('Hasta 3 fotos JPG, PNG o WebP de 3 MB como máximo.'),
            ])
            ->action(function (Caso $record, array $data, ClienteFixaApi $api): void {
                $fotos = collect($data['fotos'] ?? [])
                    ->map(fn ($archivo): string => $archivo instanceof TemporaryUploadedFile ? $archivo->getRealPath() : (string) $archivo)
                    ->values()->all();

                $this->ejecutar(fn () => $api->registrarIntervencion(
                    $record->id, trim($data['trabajo']), $data['tipo'],
                    $data['tipo'] === 'acta_tecnica' ? trim((string) ($data['descripcion'] ?? '')) : null, $fotos,
                ), 'Intervención enviada. El supervisor la revisará para cerrar el caso.');
            });
    }

    /** Casos con programación vigente para la cuadrilla del técnico, con lo que muestra la lista. */
    private function ordenes(): Builder
    {
        $cuadrilla = $this->cuadrilla();
        $vigente = fn (string $columna) => Programacion::query()->select($columna)
            ->whereColumn('programaciones.caso_id', 'casos.id')->where('vigente', true)->orderByDesc('id')->limit(1);
        $delPrimerReporte = fn (string $columna) => Reporte::query()->select($columna)
            ->whereColumn('reportes.caso_id', 'casos.id')->orderBy('es_apoyo')->orderBy('id')->limit(1);

        return Caso::query()
            ->with('categoria:id,nombre')
            ->whereIn('estado', array_map(fn (EstadoCaso $e): string => $e->value, self::ESTADOS))
            ->whereHas('programaciones', fn (Builder $q) => $q->where('vigente', true)->where('cuadrilla_id', $cuadrilla ?? 0))
            ->select('casos.*')
            ->addSelect([
                'fecha_prevista' => $vigente('fecha_prevista'),
                'direccion' => $delPrimerReporte('direccion'),
                'referencia' => $delPrimerReporte('referencia'),
                'rechazos' => Intervencion::query()->selectRaw('count(*)')
                    ->whereColumn('intervenciones.caso_id', 'casos.id')->where('estado_validacion', 'rechazada'),
            ]);
    }

    private function cuadrilla(): ?int
    {
        return ServicioAutenticacion::getInstance()->usuario()?->cuadrilla_id;
    }

    private function cuando(Caso $caso): string
    {
        $fecha = Carbon::parse($caso->fecha_prevista)->startOfDay();

        return match (true) {
            $fecha->isToday() => 'Para hoy',
            $this->atrasada($caso) => 'Atrasada, era para el '.$fecha->format('d/m'),
            $fecha->isTomorrow() => 'Para mañana',
            default => 'Para el '.$fecha->format('d/m/Y'),
        };
    }

    private function atrasada(Caso $caso): bool
    {
        return $caso->estado === EstadoCaso::Programada && $caso->fecha_prevista !== null
            && now()->startOfDay()->gt($caso->fecha_prevista);
    }

    private function ejecutar(callable $llamada, string $exito): void
    {
        try {
            $llamada();
        } catch (ErrorApi $error) {
            Notification::make()->title('No se pudo completar')->body($error->getMessage())->danger()->persistent()->send();

            return;
        }

        Notification::make()->title($exito)->success()->send();
    }
}
