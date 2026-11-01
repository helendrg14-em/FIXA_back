<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaAtencion extends Model
{
    protected $table = 'metas_atencion';

    public $timestamps = false;

    protected $fillable = ['categoria_id', 'prioridad', 'plazo_horas', 'vigente_desde', 'vigente_hasta'];

    protected function casts(): array
    {
        return [
            'plazo_horas' => 'integer',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
