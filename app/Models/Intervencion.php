<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Trabajo que registra el técnico con su evidencia. Solo se lee: lo escribe la API. */
class Intervencion extends Model
{
    protected $table = 'intervenciones';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['fecha' => 'datetime'];
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    public function evidencias(): HasMany
    {
        return $this->hasMany(Evidencia::class, 'intervencion_id');
    }
}
