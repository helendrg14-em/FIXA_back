<?php

namespace App\Models;

use App\Enums\CanalOrigen;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reporte extends Model
{
    protected $table = 'reportes';

    public $timestamps = false;

    protected $fillable = [
        'caso_id', 'distrito_id', 'operador_id', 'codigo', 'clave_hash', 'descripcion', 'tipo_incidencia',
        'numero_luminarias', 'canal_origen', 'ubicacion', 'direccion', 'referencia', 'codigo_aviso_cobranza',
        'motivo_sin_foto', 'correo_contacto', 'token_dispositivo', 'clave_idempotencia',
    ];

    protected $hidden = ['clave_hash', 'correo_contacto', 'token_dispositivo', 'ubicacion'];

    protected function casts(): array
    {
        return [
            'canal_origen' => CanalOrigen::class,
            'numero_luminarias' => 'integer',
            'es_apoyo' => 'boolean',
            'conformidad' => 'boolean',
            'fecha_conformidad' => 'datetime',
            'fecha_registro' => 'datetime',
        ];
    }

    public function caso(): BelongsTo
    {
        return $this->belongsTo(Caso::class, 'caso_id');
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(FotoReporte::class, 'reporte_id');
    }
}
