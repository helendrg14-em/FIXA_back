<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distrito extends Model
{
    protected $table = 'distritos';

    public $timestamps = false;

    // "limite" es un POLYGON de PostGIS; se carga con ST_GeomFromGeoJSON, no por asignacion masiva.
    protected $fillable = ['numero', 'nombre'];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
        ];
    }
}
