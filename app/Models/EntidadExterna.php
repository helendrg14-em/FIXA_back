<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Entidades a las que se derivan los casos que no son del municipio (CRE, ABC, Gobernación). */
class EntidadExterna extends Model
{
    protected $table = 'entidades_externas';

    public $timestamps = false;

    protected $fillable = ['nombre', 'contacto'];
}
