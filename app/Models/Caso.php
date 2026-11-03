<?php

namespace App\Models;

use App\Enums\EstadoCaso;
use App\Enums\Prioridad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
