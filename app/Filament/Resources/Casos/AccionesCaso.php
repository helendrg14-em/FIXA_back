<?php

namespace App\Filament\Resources\Casos;

use App\Api\ClienteFixaApi;
use App\Api\ErrorApi;
use App\Auth\ServicioAutenticacion;
use App\Enums\EstadoCaso;
use App\Enums\Prioridad;
use App\Models\Caso;
use App\Models\Categoria;
use App\Models\Cuadrilla;
use App\Models\EntidadExterna;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;

/**
 * Acciones sobre un caso (RF-05, RF-06, RF-08, RF-10, RF-12). Cada una llama a la API, que
 * valida la transición (RN-03), guarda el historial y avisa al vecino. Los botones solo se
 * muestran en los estados donde la API acepta la acción; si igual falla, se muestra su mensaje.
 *
 * Las ve el supervisor (casos de su unidad) y el administrador (todos).
 */
final class AccionesCaso
{
    /** @return list<Action> */
    public static function todas(): array
    {
        return [
            self::pasarARevision(),
            self::prioridad(),
            self::programar(),
            self::aprobar(),
            self::rechazarEvidencia(),
            self::corregirCategoria(),
            self::duplicado(),
            self::derivar(),
            self::rechazar(),
        ];
    }

    public static function pasarARevision(): Action
    {
        return Action::make('pasarARevision')->label('Pasar a revisión')->icon(Heroicon::OutlinedMagnifyingGlass)
            ->visible(fn (Caso $record): bool => $record->estado === EstadoCaso::Recibida)
            ->requiresConfirmation()
            ->modalDescription('El vecino verá que su reporte está en revisión.')
            ->action(fn (Caso $record) => self::ejecutar(fn (ClienteFixaApi $api) => $api->pasarARevision($record->id), 'El caso pasó a revisión.'));
    }

    public static function prioridad(): Action
    {
        return Action::make('prioridad')->icon(Heroicon::OutlinedFlag)
            ->label(fn (Caso $record): string => $record->prioridad ? 'Cambiar prioridad' : 'Asignar prioridad')
            ->visible(fn (Caso $record): bool => in_array($record->estado, [EstadoCaso::EnRevision, EstadoCaso::Asignada, EstadoCaso::Programada], true))
            ->modalDescription(fn (Caso $record): string => $record->estado === EstadoCaso::EnRevision
                ? 'Al confirmar la prioridad el caso queda Asignado y se calcula su fecha objetivo.'
                : 'La fecha objetivo se vuelve a calcular con la nueva prioridad.')
            ->fillForm(fn (Caso $record): array => ['prioridad' => $record->prioridad?->value])
            ->schema([
                Select::make('prioridad')->required()->native(false)->options(Prioridad::class),
                Textarea::make('justificacion')->label('Justificación')->required()->minLength(10)->maxLength(1000)
                    ->placeholder('Ej.: está en una avenida principal y hay riesgo para motos.'),
            ])
            ->action(fn (Caso $record, array $data) => self::ejecutar(
                fn (ClienteFixaApi $api) => $api->clasificarPrioridad($record->id, $data['prioridad'], trim($data['justificacion'])),
                'Prioridad guardada.'));
    }

    public static function programar(): Action
    {
        return Action::make('programar')->icon(Heroicon::OutlinedCalendarDays)
            ->label(fn (Caso $record): string => $record->estado === EstadoCaso::Programada ? 'Reprogramar' : 'Programar trabajo')
            ->visible(fn (Caso $record): bool => in_array($record->estado, [EstadoCaso::Asignada, EstadoCaso::Programada], true))
            ->schema(fn (Caso $record): array => [
                Select::make('cuadrilla_id')->label('Cuadrilla')->required()->native(false)
                    ->options(Cuadrilla::query()->where('unidad_id', $record->unidad_id)->orderBy('nombre')->pluck('nombre', 'id')),
                DatePicker::make('fecha')->label('Fecha prevista')->required()->native(false)->minDate(today())->displayFormat('d/m/Y'),
                Textarea::make('motivo')->label('Motivo de la reprogramación')->maxLength(200)
                    ->visible($record->estado === EstadoCaso::Programada)->required($record->estado === EstadoCaso::Programada),
            ])
            ->action(fn (Caso $record, array $data) => self::ejecutar(
                fn (ClienteFixaApi $api) => $api->programar($record->id, (int) $data['cuadrilla_id'], (string) $data['fecha'], $data['motivo'] ?? null),
                'Trabajo programado. La cuadrilla ya lo ve en sus órdenes.'));
    }

    public static function aprobar(): Action
    {
        return Action::make('aprobar')->label('Aprobar y cerrar')->icon(Heroicon::OutlinedCheckBadge)->color('success')
            ->visible(fn (Caso $record): bool => $record->estado === EstadoCaso::PendienteValidacion)
            ->requiresConfirmation()
            ->modalHeading('Aprobar el trabajo y cerrar el caso')
            ->modalDescription(fn (): string => ServicioAutenticacion::getInstance()->esAdministrador()
                ? 'Revisa la evidencia del trabajo antes de cerrar. El vecino podrá confirmar la solución o pedir revisión durante 15 días.'
                : 'Revisa antes la foto del después. El vecino podrá confirmar la solución o pedir revisión durante 15 días.')
            ->schema([Textarea::make('observacion')->label('Observación (opcional)')->maxLength(1000)])
            ->action(fn (Caso $record, array $data) => self::ejecutar(
                fn (ClienteFixaApi $api) => $api->validarIntervencion(self::intervencion($record), true, $data['observacion'] ?? null),
                'Caso cerrado.'));
    }

    public static function rechazarEvidencia(): Action
    {
        return Action::make('rechazarEvidencia')->label('Rechazar el trabajo')->icon(Heroicon::OutlinedArrowUturnLeft)->color('warning')
            ->visible(fn (Caso $record): bool => $record->estado === EstadoCaso::PendienteValidacion)
            ->modalDescription('El caso vuelve a «En atención» para que la cuadrilla lo corrija.')
            ->schema([Textarea::make('observacion')->label('Qué debe corregir el técnico')->required()->maxLength(1000)])
            ->action(fn (Caso $record, array $data) => self::ejecutar(
                fn (ClienteFixaApi $api) => $api->validarIntervencion(self::intervencion($record), false, trim($data['observacion'])),
                'El trabajo volvió a la cuadrilla.'));
    }

    public static function corregirCategoria(): Action
    {
        return Action::make('corregirCategoria')->label('Corregir categoría')->icon(Heroicon::OutlinedArrowsRightLeft)
            ->visible(fn (Caso $record): bool => in_array($record->estado, [EstadoCaso::Recibida, EstadoCaso::EnRevision, EstadoCaso::Asignada], true))
            ->modalDescription('El caso pasa a la unidad de la nueva categoría y pierde la prioridad asignada.')
            ->schema(fn (Caso $record): array => [
                Select::make('categoria')->label('Categoría correcta')->required()->native(false)
                    ->options(Categoria::query()->where('activa', true)->where('id', '!=', $record->categoria_id)->pluck('nombre', 'codigo')),
                Textarea::make('motivo')->required()->minLength(10)->maxLength(1000),
            ])
            ->action(fn (Caso $record, array $data) => self::ejecutar(
                fn (ClienteFixaApi $api) => $api->reclasificar($record->id, $data['categoria'], trim($data['motivo'])),
                'Categoría corregida.'));
    }

    public static function duplicado(): Action
    {
        return Action::make('duplicado')->label('Marcar como duplicado')->icon(Heroicon::OutlinedDocumentDuplicate)
            ->visible(fn (Caso $record): bool => $record->estado === EstadoCaso::EnRevision)
            ->modalDescription('Sus reportes pasan al caso principal como apoyo; los vecinos conservan su código.')
            ->schema(fn (Caso $record): array => [
                Select::make('principal')->label('Caso principal')->required()->native(false)->searchable()
                    ->options(Caso::query()
                        ->where('id', '!=', $record->id)
                        ->where('categoria_id', $record->categoria_id)
                        ->where('unidad_id', $record->unidad_id)
                        ->whereNotIn('estado', EstadoCaso::finales())
                        ->orderByDesc('id')->limit(100)->get()
                        ->mapWithKeys(fn (Caso $c): array => [$c->id => "Caso {$c->id} · {$c->estado->getLabel()} · {$c->fecha_creacion?->format('d/m/Y')}"])),
                Textarea::make('motivo')->maxLength(1000),
            ])
            ->action(fn (Caso $record, array $data) => self::ejecutar(
                fn (ClienteFixaApi $api) => $api->marcarDuplicado($record->id, (int) $data['principal'], $data['motivo'] ?? null),
                'Caso marcado como duplicado.'));
    }

    public static function derivar(): Action
    {
        return Action::make('derivar')->label('Derivar a otra entidad')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->visible(fn (Caso $record): bool => $record->estado === EstadoCaso::Asignada)
            ->modalDescription('Para casos que no son del municipio (CRE, ABC, Gobernación). Queda «Derivado», no resuelto.')
            ->schema([
                Select::make('entidad')->label('Entidad')->required()->native(false)
                    ->options(fn (): array => EntidadExterna::query()->orderBy('nombre')->pluck('nombre', 'id')->all()),
                Textarea::make('motivo')->required()->maxLength(1000),
            ])
            ->action(fn (Caso $record, array $data) => self::ejecutar(
                fn (ClienteFixaApi $api) => $api->derivar($record->id, (int) $data['entidad'], trim($data['motivo'])),
                'Caso derivado.'));
    }

    public static function rechazar(): Action
    {
        return Action::make('rechazar')->label('Rechazar')->icon(Heroicon::OutlinedXCircle)->color('danger')
            ->visible(fn (Caso $record): bool => $record->estado === EstadoCaso::EnRevision)
            ->modalDescription('Por ejemplo, si no es un problema real o está fuera de lo que atiende FIXA. El vecino verá el motivo.')
            ->schema([Textarea::make('motivo')->required()->maxLength(1000)])
            ->action(fn (Caso $record, array $data) => self::ejecutar(
                fn (ClienteFixaApi $api) => $api->rechazar($record->id, trim($data['motivo'])),
                'Caso rechazado.'));
    }

    /** Llama a la API y muestra el resultado; si falla, deja el modal abierto con el mensaje. */
    public static function ejecutar(callable $llamada, string $exito): void
    {
        try {
            $llamada(app(ClienteFixaApi::class));
        } catch (ErrorApi $error) {
            Notification::make()->title('No se pudo completar')->body($error->getMessage())->danger()->persistent()->send();

            throw new Halt;
        }

        Notification::make()->title($exito)->success()->send();
    }

    private static function intervencion(Caso $caso): int
    {
        return $caso->intervencionPendiente()?->id
            ?? throw new ErrorApi('No hay un trabajo pendiente de validar en este caso.');
    }
}
