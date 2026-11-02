<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feriado extends Model
{
    protected $table = 'feriados';

    public $timestamps = false;

    protected $dateFormat = 'Y-m-d';

    protected $fillable = ['fecha', 'nombre', 'fuente'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }
}
