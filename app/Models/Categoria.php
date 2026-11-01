<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categorias';

    public $timestamps = false;

    protected $fillable = ['unidad_id', 'nombre', 'descripcion', 'activa', 'radio_cercania_m'];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'radio_cercania_m' => 'integer',
        ];
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(UnidadResponsable::class, 'unidad_id');
    }

    public function metasAtencion(): HasMany
    {
        return $this->hasMany(MetaAtencion::class, 'categoria_id');
    }
}
