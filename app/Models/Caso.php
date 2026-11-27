<?php

namespace App\Models;

use App\Enums\EstadoCaso;
use App\Enums\Prioridad;
use App\Enums\TipoUsuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Caso extends Model
{
    protected $table = 'casos';

    public $timestamps = false;

    protected $fillable = ['categoria_id', 'unidad_id', 'estado', 'requiere_verificacion_campo'];

    protected function casts(): array
    {
        return [
            'estado' => EstadoCaso::class,
            'prioridad' => Prioridad::class,
            'requiere_verificacion_campo' => 'boolean',
            'vencido' => 'boolean',
            'fecha_creacion' => 'datetime',
            'fecha_objetivo' => 'datetime',
            'fecha_cierre' => 'datetime',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function reportes(): HasMany
    {
        return $this->hasMany(Reporte::class, 'caso_id');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(EventoHistorial::class, 'caso_id');
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(UnidadResponsable::class, 'unidad_id');
    }

    public function intervenciones(): HasMany
    {
        return $this->hasMany(Intervencion::class, 'caso_id');
    }

    public function programaciones(): HasMany
    {
        return $this->hasMany(Programacion::class, 'caso_id');
    }

    public function programacionVigente(): HasOne
    {
        return $this->hasOne(Programacion::class, 'caso_id')
            ->ofMany(['id' => 'max'], fn (Builder $query): Builder => $query->where('vigente', true));
    }

    /** Intervención del técnico que espera la aprobación del supervisor o del administrador. */
    public function intervencionPendiente(): ?Intervencion
    {
        return $this->intervenciones()->where('estado_validacion', 'pendiente')->latest('fecha')->first();
    }

    /**
     * Casos que puede ver el usuario: el administrador, todos; el supervisor, los de su unidad;
     * cualquier otro perfil, ninguno. Es la misma regla que aplica la API al cambiarlos.
     */
    public function scopeVisiblesPara(Builder $consulta, ?User $usuario): Builder
    {
        return match (true) {
            $usuario?->esAdministrador() => $consulta,
            $usuario?->rol === TipoUsuario::Supervisor && $usuario->unidad_id !== null => $consulta->where('unidad_id', $usuario->unidad_id),
            default => $consulta->whereRaw('1 = 0'),
        };
    }
}
