<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** El vecino no quedó conforme con el cierre y pide revisarlo. Se responde por la API. */
class SolicitudRevision extends Model
{
    protected $table = 'solicitudes_revision';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['fecha_solicitud' => 'datetime', 'fecha_resolucion' => 'datetime'];
    }

    public function reporte(): BelongsTo
    {
        return $this->belongsTo(Reporte::class, 'reporte_id');
    }
}
