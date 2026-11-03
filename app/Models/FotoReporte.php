<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FotoReporte extends Model
{
    protected $table = 'fotos_reporte';

    public $timestamps = false;

    protected $fillable = ['reporte_id', 'url', 'public_id'];
}
