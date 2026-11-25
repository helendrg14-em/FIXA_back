<?php

namespace App\Filament\Pages;

use App\Api\ClienteFixaApi;
use App\Api\ErrorApi;
use App\Auth\ServicioAutenticacion;
use App\Enums\CanalOrigen;
use App\Filament\Resources\Casos\CasoResource;
use App\Models\Categoria;
use App\Models\Reporte;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Registro asistido (R07, requerimientos v1.2): el administrador carga el reclamo de un vecino
 * que llamó o fue a ventanilla. Se envía a la API, que aplica las mismas reglas que la app
 * (distrito, unidad, código y clave) y guarda quién lo registró. La clave se muestra una vez
 * para dictársela al vecino.
 */
class NuevoReporte extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlusCircle;

    protected static ?string $navigationLabel = 'Gestionar reportes';

    protected static ?string $title = 'Gestionar reportes';

    protected static ?int $navigationSort = 2;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** Código y clave del último reporte registrado, para mostrárselos al funcionario. */
    public ?array $creado = null;

    public static function canAccess(): bool
    {
        return ServicioAutenticacion::getInstance()->esAdministrador();
    }

    public function mount(): void
    {
        $this->form->fill(['canal' => CanalOrigen::Telefono->value, 'latitud' => -17.7834, 'longitud' => -63.1821]);
    }

    public function form(Schema $schema): Schema
    {
        // Dos columnas: el reclamo (lo que cuenta el vecino) y el lugar. En celular se apilan.
        return $schema->statePath('data')->columns(['default' => 1, 'lg' => 5])->components([
            Section::make('Reclamo')->compact()->icon(Heroicon::OutlinedChatBubbleBottomCenterText)
                ->columnSpan(['default' => 1, 'lg' => 3])->columns(['default' => 1, 'sm' => 2])
                ->schema([
                    Radio::make('canal')->label('¿Cómo llegó?')->required()->inline()->columnSpanFull()
                        ->options([CanalOrigen::Telefono->value => 'Por teléfono', CanalOrigen::Presencial->value => 'En ventanilla']),
                    Select::make('categoria')->label('Categoría')->placeholder('Elige la categoría')->required()->native(false)->live()
                        ->options(fn (): array => Categoria::query()->where('activa', true)->orderBy('id')->pluck('nombre', 'codigo')->all())
                        ->afterStateUpdated(fn (Set $set) => $set('tipo', null)),
                    Select::make('tipo')->label('Tipo')->required()->native(false)->live()
                        ->placeholder(fn (Get $get): string => blank($get('categoria')) ? 'Elige antes la categoría' : 'Elige el tipo')
                        ->options(fn (Get $get): array => $get('categoria') ? $this->tipos($get('categoria')) : [])
                        ->disabled(fn (Get $get): bool => blank($get('categoria'))),
                    TextInput::make('numero_luminarias')->label('Luminarias afectadas')->numeric()->minValue(1)->maxValue(200)
                        ->visible(fn (Get $get): bool => $get('categoria') === 'alumbrado' && $get('tipo') !== 'otro')
                        ->required(fn (Get $get): bool => $get('categoria') === 'alumbrado' && $get('tipo') !== 'otro'),
                    TextInput::make('codigo_aviso_cobranza')->label('Código del aviso de cobranza')->maxLength(30)->hint('Opcional')
                        ->visible(fn (Get $get): bool => $get('categoria') === 'alumbrado'),
                    Textarea::make('descripcion')->label('Descripción')->required()->maxLength(1000)->rows(3)->autosize()->columnSpanFull()
                        ->placeholder('Lo que cuenta el vecino: qué pasa y desde cuándo.'),
                    TextInput::make('correo')->label('Correo del vecino')->email()->maxLength(120)->columnSpanFull()
                        ->hint('Opcional')->placeholder('nombre@correo.com')
                        ->helperText('Para avisarle cuando cambie el estado. Si no tiene correo, deja el campo vacío.'),
                    FileUpload::make('fotos')->label('Fotos')->hint('Opcional, hasta 3')->columnSpanFull()
                        ->multiple()->maxFiles(3)->image()->panelLayout('grid')->imagePreviewHeight('96')
                        ->acceptedFileTypes(['image/jpeg', 'image/webp'])->maxSize(3072)->storeFiles(false)->live()
                        ->helperText('JPG o WebP de hasta 3 MB, por ejemplo la que el vecino mandó por WhatsApp.'),
                    TextInput::make('motivo_sin_foto')->label('¿Por qué no hay foto?')->maxLength(200)->minLength(5)->columnSpanFull()
                        ->placeholder('Ej.: el vecino llamó y no tiene cámara.')
                        ->visible(fn (Get $get): bool => blank($get('fotos')))
                        ->required(fn (Get $get): bool => blank($get('fotos'))),
                ]),
            Section::make('Lugar')->compact()->icon(Heroicon::OutlinedMapPin)
                ->columnSpan(['default' => 1, 'lg' => 2])
                ->schema([
                    ViewField::make('mapa')->hiddenLabel()->view('panel.mapa-punto')->dehydrated(false),
                    Grid::make(2)->schema([
                        TextInput::make('latitud')->required()->numeric()->minValue(-90)->maxValue(90)->live(debounce: 600),
                        TextInput::make('longitud')->required()->numeric()->minValue(-180)->maxValue(180)->live(debounce: 600),
                    ]),
                    TextInput::make('direccion')->label('Dirección')->hint('Opcional')->maxLength(200),
                    TextInput::make('referencia')->label('Referencia')->hint('Opcional')->maxLength(200)->placeholder('Ej.: frente al mercado'),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Reporte registrado')->icon(Heroicon::OutlinedCheckCircle)->iconColor('success')
                ->visible(fn (): bool => $this->creado !== null)
                ->description('Dale al vecino el código y la clave. La clave no se vuelve a mostrar.')
                ->columns(2)
                ->schema([
                    TextEntry::make('codigo')->label('Código')->state(fn (): ?string => $this->creado['codigo'] ?? null)
                        ->size('lg')->weight('bold')->copyable(),
                    TextEntry::make('clave')->label('Clave')->state(fn (): ?string => $this->creado['clave'] ?? null)
                        ->size('lg')->weight('bold')->copyable(),
                ])
                ->footerActions([
                    Action::make('verCaso')->label('Ver el caso')->link()
                        ->url(fn (): ?string => ($caso = Reporte::query()->where('codigo', $this->creado['codigo'] ?? '')->value('caso_id'))
                            ? CasoResource::getUrl('view', ['record' => $caso]) : null),
                ]),
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('registrar')
                ->footer([
                    Actions::make([
                        Action::make('registrar')->label('Registrar reporte')->submit('registrar')->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function registrar(ClienteFixaApi $api): void
    {
        $datos = $this->form->getState();
        $fotos = collect($datos['fotos'] ?? [])
            ->map(fn ($archivo): string => $archivo instanceof TemporaryUploadedFile ? $archivo->getRealPath() : (string) $archivo)
            ->values()->all();

        try {
            $resultado = $api->registrarReporte([
                'categoria' => $datos['categoria'],
                'latitud' => $datos['latitud'],
                'longitud' => $datos['longitud'],
                'tipoIncidencia' => $datos['tipo'],
                'descripcion' => trim($datos['descripcion']),
                'numeroLuminarias' => $datos['numero_luminarias'] ?? null,
                'codigoAvisoCobranza' => $datos['codigo_aviso_cobranza'] ?? null,
                'direccion' => $datos['direccion'] ?? null,
                'referencia' => $datos['referencia'] ?? null,
                'correoContacto' => $datos['correo'] ?? null,
                'canal' => $datos['canal'],
                'motivoSinFoto' => $fotos === [] ? ($datos['motivo_sin_foto'] ?? null) : null,
                // Si se pulsa dos veces o se corta la conexión, la API no lo registra de nuevo.
                'claveIdempotencia' => $this->claveIdempotencia(),
            ], $fotos);
        } catch (ErrorApi $error) {
            Notification::make()->title('No se pudo registrar')->body($error->getMessage())->danger()->persistent()->send();

            return;
        }

        $this->creado = $resultado;
        unset($this->data['clave_idempotencia']);
        $this->form->fill(['canal' => $datos['canal'], 'latitud' => $datos['latitud'], 'longitud' => $datos['longitud']]);
        Notification::make()->title("Reporte {$resultado['codigo']} registrado")->success()->send();
    }

    /** Una por reporte: se mantiene mientras no se registre con éxito. */
    private function claveIdempotencia(): string
    {
        return $this->data['clave_idempotencia'] ??= bin2hex(random_bytes(16));
    }

    /** @return array<string, string> */
    private function tipos(string $categoria): array
    {
        try {
            return Cache::remember("fixa_api.tipos.{$categoria}", now()->addHour(), fn (): array => app(ClienteFixaApi::class)->tipos($categoria));
        } catch (ErrorApi $error) {
            Notification::make()->title('No se pudieron cargar los tipos')->body($error->getMessage())->warning()->send();

            return [];
        }
    }
}
