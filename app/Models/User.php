<?php

namespace App\Models;

use App\Enums\TipoUsuario;
use App\Notifications\RecuperarPassword;
use App\Notifications\RegistrarPassword;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = null;

    /**
     * Solo el administrador edita cuentas (ver UserPolicy). No exponer este modelo
     * a rutas publicas ni a la API del ciudadano.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tipo',
        'activo',
        'debe_registrar_password',
        'nombres',
        'apellidos',
        'correo',
        'password_hash',
        'punto_atencion',
        'distrito_id',
        'cargo',
        'unidad_id',
        'especialidad',
        'cuadrilla_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'codigo_2fa_hash',
        'token_recuperacion_hash',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoUsuario::class,
            'password_hash' => 'hashed',
            'activo' => 'boolean',
            'debe_registrar_password' => 'boolean',
            'requiere_2fa' => 'boolean',
            'codigo_2fa_expira' => 'datetime',
            'token_expira' => 'datetime',
            'ultimo_acceso' => 'datetime',
            'fecha_creacion' => 'datetime',
        ];
    }

    /**
     * En PostgreSQL la comparacion es sensible a mayusculas: se guarda siempre en minusculas.
     */
    protected function correo(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => mb_strtolower(trim($value)),
        );
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    /**
     * La tabla no tiene remember_token: la sesion siempre expira por inactividad (R01).
     */
    public function getRememberTokenName(): string
    {
        return '';
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->correo;
    }

    public function routeNotificationForMail(): string
    {
        return $this->correo;
    }

    /**
     * Filament lo revisa en el login y en cada peticion al panel, asi que una cuenta
     * desactivada pierde el acceso aunque tenga una sesion abierta.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->activo && $this->tipo?->puedeEntrarAlPanel();
    }

    public function getFilamentName(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    public function esAdministrador(): bool
    {
        return $this->tipo === TipoUsuario::Administrador;
    }

    /**
     * Cuenta nueva: enlace de un solo uso que vence en 48 horas.
     */
    public function enviarEnlaceRegistro(): void
    {
        $this->notify(new RegistrarPassword($this->nuevoToken(now()->addHours(48))));
    }

    /**
     * Contraseña olvidada: enlace de un solo uso que vence en 60 minutos. La contraseña
     * actual sigue sirviendo hasta que se cree la nueva.
     */
    public function enviarEnlaceRecuperacion(): void
    {
        $this->notify(new RecuperarPassword($this->nuevoToken(now()->addMinutes(60))));
    }

    /**
     * El enlace se arma con APP_URL y no con el host de la petición: así nadie puede
     * pedir una recuperación con una cabecera Host falsa y recibir un enlace a su sitio.
     */
    public function enlaceContrasena(string $token): string
    {
        return rtrim((string) config('app.url'), '/')
            .route('cuenta.contrasena', ['usuario' => $this->id, 'token' => $token], absolute: false);
    }

    /**
     * Solo se guarda el hash del token, y uno nuevo anula el anterior.
     */
    private function nuevoToken(CarbonInterface $vence): string
    {
        $token = Str::random(64);

        $this->forceFill([
            'token_recuperacion_hash' => hash('sha256', $token),
            'token_expira' => $vence,
        ])->save();

        return $token;
    }

    public function validarToken(string $token): bool
    {
        return filled($this->token_recuperacion_hash)
            && $this->token_expira?->isFuture()
            && hash_equals($this->token_recuperacion_hash, hash('sha256', $token));
    }

    public function registrarPassword(string $password): void
    {
        $this->forceFill([
            'password_hash' => $password,
            'debe_registrar_password' => false,
            'token_recuperacion_hash' => null,
            'token_expira' => null,
        ])->save();
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(UnidadResponsable::class, 'unidad_id');
    }

    public function cuadrilla(): BelongsTo
    {
        return $this->belongsTo(Cuadrilla::class, 'cuadrilla_id');
    }

    public function distrito(): BelongsTo
    {
        return $this->belongsTo(Distrito::class, 'distrito_id');
    }
}
