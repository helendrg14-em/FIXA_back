<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadResponsable extends Model
{
    protected $table = 'unidades_responsables';

    public $timestamps = false;

    protected $fillable = ['nombre', 'sigla'];

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class, 'unidad_id');
    }

    public function cuadrillas(): HasMany
    {
        return $this->hasMany(Cuadrilla::class, 'unidad_id');
    }
}
