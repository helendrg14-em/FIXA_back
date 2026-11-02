<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventoHistorial extends Model
{
    protected $table = 'eventos_historial';

    public $timestamps = false;

    protected $fillable = ['caso_id', 'usuario_id', 'tipo', 'estado_anterior', 'estado_nuevo', 'detalle', 'motivo'];
}
