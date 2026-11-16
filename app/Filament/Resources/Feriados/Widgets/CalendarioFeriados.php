<?php

namespace App\Filament\Resources\Feriados\Widgets;

use App\Models\Feriado;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

/**
 * Calendario del mes con los feriados marcados (RN-06). Tocar un día libre abre «Agregar
 * feriado» con esa fecha; tocar un feriado abre su edición. Los fines de semana se ven
 * apagados porque ya no cuentan como días hábiles.
 */
class CalendarioFeriados extends Widget
{
    protected string $view = 'panel.feriados.calendario';

    protected int|string|array $columnSpan = 'full';

    /** Se dibuja con la página: si no, al entrar se ve un recuadro vacío un instante. */
    protected static bool $isLazy = false;

    /** Mes visible, como 2026-11. */
    public string $mes = '';

    public function mount(): void
    {
        $this->mes = today()->format('Y-m');
    }

    public function anterior(): void
    {
        $this->mes = $this->inicio()->subMonthNoOverflow()->format('Y-m');
    }

    public function siguiente(): void
    {
        $this->mes = $this->inicio()->addMonthNoOverflow()->format('Y-m');
    }

    public function hoy(): void
    {
        $this->mes = today()->format('Y-m');
    }

    #[On('feriados-actualizados')]
    public function refrescar(): void
    {
        // Livewire vuelve a dibujar el calendario con los datos nuevos.
    }

    protected function getViewData(): array
    {
        $inicio = $this->inicio();
        $fin = $inicio->copy()->endOfMonth();
        $feriados = Feriado::query()->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->get()->keyBy(fn (Feriado $f): string => $f->fecha->toDateString());

        // La semana empieza el lunes, como en los calendarios de Bolivia.
        $dias = [];
        for ($dia = $inicio->copy()->startOfWeek(Carbon::MONDAY); $dia <= $fin->copy()->endOfWeek(Carbon::SUNDAY); $dia->addDay()) {
            $fecha = $dia->toDateString();
            $dias[] = [
                'fecha' => $fecha,
                'numero' => $dia->day,
                'delMes' => $dia->month === $inicio->month,
                'finDeSemana' => $dia->isWeekend(),
                'hoy' => $dia->isToday(),
                'feriado' => $feriados->get($fecha),
            ];
        }

        return [
            'titulo' => ucfirst($inicio->locale('es')->isoFormat('MMMM [de] YYYY')),
            'dias' => $dias,
            'total' => $feriados->count(),
        ];
    }

    private function inicio(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $this->mes.'-01')->startOfDay();
    }
}
