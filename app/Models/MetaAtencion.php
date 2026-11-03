<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaAtencion extends Model
{
    protected $table = 'metas_atencion';

    public $timestamps = false;

    protected $fillable = ['categoria_id', 'prioridad', 'plazo_horas', 'plazo_dias_habiles', 'vigente_desde', 'vigente_hasta'];

    protected function casts(): array
    {
        return [
            'plazo_horas' => 'integer',
            'plazo_dias_habiles' => 'integer',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function plazoLegible(): string
    {
        return $this->plazo_horas !== null
            ? "{$this->plazo_horas} horas"
            : "{$this->plazo_dias_habiles} días hábiles";
    }

    /**
     * RN-06: las horas corren siempre; los días hábiles saltan sábados, domingos y feriados.
     */
    public function fechaObjetivo(CarbonInterface $desde): CarbonImmutable
    {
        $fecha = CarbonImmutable::instance($desde)->setTimezone(config('fixa.zona_horaria'));

        if ($this->plazo_horas !== null) {
            return $fecha->addHours($this->plazo_horas);
        }

        $feriados = Feriado::query()
            ->whereBetween('fecha', [$fecha->toDateString(), $fecha->addDays($this->plazo_dias_habiles * 3 + 30)->toDateString()])
            ->pluck('fecha')
            ->map(fn (CarbonInterface $dia): string => $dia->toDateString())
            ->all();

        $restantes = $this->plazo_dias_habiles;

        while ($restantes > 0) {
            $fecha = $fecha->addDay();

            if (! $fecha->isWeekend() && ! in_array($fecha->toDateString(), $feriados, true)) {
                $restantes--;
            }
        }

        return $fecha;
    }
}
