<?php

namespace App\Models;

use App\Enums\TipoUsuario;
use App\Notifications\RegistrarPassword;
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
     * Crea un enlace de un solo uso que vence en 48 horas. Solo se guarda el hash del token.
     */
    public function enviarEnlaceRegistro(): void
    {
        $token = Str::random(64);

        $this->forceFill([
            'token_recuperacion_hash' => hash('sha256', $token),
            'token_expira' => now()->addHours(48),
        ])->save();

        $this->notify(new RegistrarPassword($token));
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
