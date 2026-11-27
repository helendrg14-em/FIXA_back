<?php

namespace App\Filament\Resources\Usuarios;

use App\Enums\TipoUsuario;
use App\Filament\Resources\Usuarios\Pages\CreateUsuario;
use App\Filament\Resources\Usuarios\Pages\EditUsuario;
use App\Filament\Resources\Usuarios\Pages\ListUsuarios;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * R03: cuentas de funcionarios. Se crean sin contraseña conocida (la registra el funcionario
 * con un enlace) y se desactivan, nunca se borran.
 */
class UsuarioResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Gestionar usuarios';

    protected static ?string $modelLabel = 'usuario';

    protected static ?string $pluralModelLabel = 'usuarios';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos del funcionario')->columns(2)->schema([
                TextInput::make('nombres')->required()->maxLength(80),
                TextInput::make('apellidos')->required()->maxLength(80),
                TextInput::make('correo')->label('Correo institucional')->email()->required()->maxLength(120)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(['unique' => 'Ya existe una cuenta con este correo.']),
                Select::make('rol')->label('Perfil')->required()->live()->native(false)
                    // Un administrador se crea solo por dentro (AdminSeeder), nunca desde el panel.
                    ->options(fn (?User $record): array => $record?->esAdministrador() ? self::perfiles() : self::perfilesAsignables())
                    // Tampoco se le cambia el perfil: así no se pierde el acceso de administrador.
                    ->disabled(fn (?User $record): bool => $record?->esAdministrador() ?? false),
            ]),
            Section::make('Datos del supervisor')->columns(2)
                ->visible(fn (Get $get): bool => self::rolEs($get('rol'), TipoUsuario::Supervisor))
                ->schema([
                    Select::make('unidad_id')->label('Unidad responsable')->relationship('unidad', 'nombre')->required()->native(false),
                    TextInput::make('cargo')->required()->maxLength(80),
                ]),
            Section::make('Datos del técnico')->columns(2)
                ->visible(fn (Get $get): bool => self::rolEs($get('rol'), TipoUsuario::Tecnico))
                ->schema([
                    TextInput::make('especialidad')->required()->maxLength(80),
                    Select::make('cuadrilla_id')->label('Cuadrilla')->relationship('cuadrilla', 'nombre')->native(false)
                        ->helperText('Puedes asignarla después.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('apellidos')
            ->columns([
                TextColumn::make('nombre')->label('Nombre')
                    ->state(fn (User $record): string => $record->getFilamentName())
                    ->searchable(['nombres', 'apellidos']),
                TextColumn::make('correo')->searchable(),
                TextColumn::make('rol')->label('Perfil')->badge()->color('primary'),
                TextColumn::make('unidad.sigla')->label('Unidad')->placeholder('Sin unidad'),
                TextColumn::make('estado_contrasena')->label('Contraseña')
                    ->state(fn (User $record): string => $record->debe_registrar_password ? 'Pendiente' : 'Creada'),
                IconColumn::make('activo')->boolean(),
            ])
            ->filters([
                SelectFilter::make('rol')->label('Perfil')->options(self::perfiles()),
                TernaryFilter::make('activo'),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                Action::make('reenviar')->label('Reenviar enlace')->icon(Heroicon::OutlinedEnvelope)
                    ->visible(fn (User $record): bool => $record->activo && $record->debe_registrar_password)
                    ->authorize(fn (User $record): bool => Gate::allows('update', $record))
                    ->requiresConfirmation()
                    ->modalDescription('El enlace anterior dejará de funcionar.')
                    ->action(function (User $record): void {
                        self::enviarEnlace($record);
                    }),
                Action::make('restablecer')->label('Restablecer contraseña')->icon(Heroicon::OutlinedKey)
                    ->visible(fn (User $record): bool => $record->activo && ! $record->debe_registrar_password)
                    ->authorize(fn (User $record): bool => Gate::allows('update', $record))
                    ->requiresConfirmation()
                    ->modalHeading('Restablecer contraseña')
                    // El administrador nunca ve ni escribe la contraseña: solo dispara el enlace.
                    ->modalDescription(fn (User $record): string => "Enviaremos a {$record->correo} un enlace para crear una contraseña nueva. Vence en 60 minutos; mientras tanto, la contraseña actual sigue funcionando.")
                    ->modalSubmitActionLabel('Enviar enlace')
                    ->action(function (User $record): void {
                        try {
                            $record->enviarEnlaceRecuperacion();
                            Notification::make()->title('Enlace enviado')->body("Se envió a {$record->correo}.")->success()->send();
                        } catch (Throwable $e) {
                            report($e);
                            Notification::make()->title('No se pudo enviar el correo')
                                ->body('Revisa la configuración de correo y vuelve a intentarlo.')->warning()->persistent()->send();
                        }
                    }),
                Action::make('desactivar')->label('Desactivar')->icon(Heroicon::OutlinedNoSymbol)->color('danger')
                    ->visible(fn (User $record): bool => $record->activo)
                    ->authorize(fn (User $record): bool => Gate::allows('desactivar', $record))
                    ->requiresConfirmation()
                    ->modalHeading('Desactivar la cuenta')
                    ->modalDescription('La persona perderá el acceso de inmediato. Su historial se conserva.')
                    ->modalSubmitActionLabel('Desactivar')
                    ->action(function (User $record): void {
                        $record->forceFill(['activo' => false])->save();
                        Notification::make()->title('Cuenta desactivada')->success()->send();
                    }),
                Action::make('reactivar')->label('Reactivar')->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (User $record): bool => ! $record->activo)
                    ->authorize(fn (User $record): bool => Gate::allows('update', $record))
                    ->requiresConfirmation()
                    ->modalHeading('Reactivar la cuenta')
                    ->modalSubmitActionLabel('Reactivar')
                    ->action(function (User $record): void {
                        $record->forceFill(['activo' => true])->save();
                        Notification::make()->title('Cuenta reactivada')->success()->send();
                    }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsuarios::route('/'),
            'create' => CreateUsuario::route('/create'),
            'edit' => EditUsuario::route('/{record}/edit'),
        ];
    }

    /**
     * Quita los datos de otros perfiles para que no queden al cambiar el perfil de una cuenta.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function limpiarPorPerfil(array $data, TipoUsuario|string|null $rol): array
    {
        if (! self::rolEs($rol, TipoUsuario::Supervisor)) {
            $data['cargo'] = null;
            $data['unidad_id'] = null;
        }

        if (! self::rolEs($rol, TipoUsuario::Tecnico)) {
            $data['especialidad'] = null;
            $data['cuadrilla_id'] = null;
        }

        return $data;
    }

    public static function enviarEnlace(User $usuario): void
    {
        try {
            $usuario->enviarEnlaceRegistro();
            Notification::make()->title('Enlace enviado')->body("Se envió a {$usuario->correo}.")->success()->send();
        } catch (Throwable $e) {
            report($e);
            Notification::make()->title('No se pudo enviar el correo')
                ->body('La cuenta está guardada. Revisa la configuración de correo y usa «Reenviar enlace».')
                ->warning()->persistent()->send();
        }
    }

    /**
     * @return array<string, string>
     */
    private static function perfiles(): array
    {
        return collect(TipoUsuario::cases())
            // El operador es versión futura (R07): no se ofrece todavía.
            ->filter(fn (TipoUsuario $tipo): bool => $tipo->puedeEntrarAlPanel())
            ->mapWithKeys(fn (TipoUsuario $tipo): array => [$tipo->value => $tipo->getLabel()])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function perfilesAsignables(): array
    {
        return collect(self::perfiles())->except(TipoUsuario::Administrador->value)->all();
    }

    private static function rolEs(TipoUsuario|string|null $rol, TipoUsuario $esperado): bool
    {
        return ($rol instanceof TipoUsuario ? $rol->value : $rol) === $esperado->value;
    }
}
