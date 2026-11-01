<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cuadrilla extends Model
{
    protected $table = 'cuadrillas';

    public $timestamps = false;

    protected $fillable = ['unidad_id', 'nombre', 'turno'];

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(UnidadResponsable::class, 'unidad_id');
    }

    public function tecnicos(): HasMany
    {
        return $this->hasMany(User::class, 'cuadrilla_id');
    }
}
