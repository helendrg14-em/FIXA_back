<?php

namespace App\Api;

use App\Auth\ServicioAutenticacion;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Las acciones que cambian un caso pasan por la API de C#: ahí están la máquina de estados,
 * el historial, los plazos y los avisos al vecino. El panel solo lee directo de la base
 * (listas y detalle) y nunca escribe casos por su cuenta, así las reglas viven en un solo lugar.
 */
class ClienteFixaApi
{
    // ---------------------------------------------------------------- Casos

    public function pasarARevision(int $casoId): void
    {
        $this->enviar(fn (PendingRequest $h) => $h->patch("casos/{$casoId}/estado", ['estado' => 'en_revision']));
    }

    public function rechazar(int $casoId, string $motivo): void
    {
        $this->enviar(fn (PendingRequest $h) => $h->patch("casos/{$casoId}/estado", ['estado' => 'rechazada', 'motivo' => $motivo]));
    }

    public function clasificarPrioridad(int $casoId, string $prioridad, string $justificacion): void
    {
        $this->enviar(fn (PendingRequest $h) => $h->patch("casos/{$casoId}/prioridad", ['prioridad' => $prioridad, 'justificacion' => $justificacion]));
    }

    public function programar(int $casoId, int $cuadrillaId, string $fecha, ?string $motivoReprogramacion = null): void
    {
        $this->enviar(fn (PendingRequest $h) => $h->post("casos/{$casoId}/programaciones", array_filter([
            'cuadrillaId' => $cuadrillaId,
            'fecha' => $fecha,
            'motivoReprogramacion' => $motivoReprogramacion,
        ], fn ($valor) => $valor !== null && $valor !== '')));
    }

    public function derivar(int $casoId, int $entidadId, string $motivo): void
    {
        $this->enviar(fn (PendingRequest $h) => $h->post("casos/{$casoId}/derivaciones", ['entidadId' => $entidadId, 'motivo' => $motivo]));
    }

    public function reclasificar(int $casoId, string $categoria, string $motivo): void
    {
        $this->enviar(fn (PendingRequest $h) => $h->patch("casos/{$casoId}/categoria", ['categoria' => $categoria, 'motivo' => $motivo]));
    }

    public function marcarDuplicado(int $casoId, int $casoPrincipalId, ?string $motivo): void
    {
        $this->enviar(fn (PendingRequest $h) => $h->post("casos/{$casoId}/vinculacion", ['casoPrincipalId' => $casoPrincipalId, 'motivo' => $motivo]));
    }

    public function validarIntervencion(int $intervencionId, bool $aprobar, ?string $observacion): void
    {
        $this->enviar(fn (PendingRequest $h) => $h->post("casos/intervenciones/{$intervencionId}/validacion", ['aprobar' => $aprobar, 'observacion' => $observacion]));
    }

    public function resolverRevision(int $solicitudId, bool $aceptar, ?string $respuesta): void
    {
        $this->enviar(fn (PendingRequest $h) => $h->post("casos/revisiones/{$solicitudId}", ['aceptar' => $aceptar, 'respuesta' => $respuesta]));
    }

    // ---------------------------------------------------------------- Reportes

    /**
     * Registro asistido por teléfono o en ventanilla (solo administrador).
     *
     * @param  array<string, scalar|null>  $datos
     * @param  list<string>  $fotos  rutas de archivos ya comprimidos (WebP o JPEG)
     * @return array{codigo: string, clave: ?string}
     */
    public function registrarReporte(array $datos, array $fotos): array
    {
        $respuesta = $this->enviar(function (PendingRequest $h) use ($datos, $fotos) {
            $h->asMultipart();
            foreach ($fotos as $i => $ruta) {
                $h->attach('fotos', file_get_contents($ruta), 'foto'.($i + 1).'.'.pathinfo($ruta, PATHINFO_EXTENSION));
            }

            return $h->post('reportes', array_filter($datos, fn ($valor) => $valor !== null && $valor !== ''));
        });

        return ['codigo' => (string) $respuesta->json('codigo'), 'clave' => $respuesta->json('clave')];
    }

    /**
     * Tipos de incidencia de una categoría (ruta pública de la API, no necesita token).
     *
     * @return array<string, string> valor => texto
     */
    public function tipos(string $categoria): array
    {
        try {
            $respuesta = $this->http(conToken: false)->get(rtrim($this->base(), '/').'/api/v1/publico/categorias/'.rawurlencode($categoria).'/tipos');
        } catch (ConnectionException) {
            throw new ErrorApi('No se pudo conectar con la API de FIXA. Intenta de nuevo en un minuto.');
        }
        if ($respuesta->failed()) {
            throw $this->traducir($respuesta);
        }

        return collect($respuesta->json())->mapWithKeys(fn (array $t): array => [$t['valor'] => $t['texto']])->all();
    }

    // ---------------------------------------------------------------- Comunes

    /** @param callable(PendingRequest): Response $pedido */
    private function enviar(callable $pedido): Response
    {
        try {
            $respuesta = $pedido($this->http());
        } catch (ConnectionException) {
            throw new ErrorApi('No se pudo conectar con la API de FIXA. Si estuvo inactiva, tarda hasta un minuto en despertar: intenta de nuevo.');
        }

        if ($respuesta->failed()) {
            throw $this->traducir($respuesta);
        }

        return $respuesta;
    }

    private function http(bool $conToken = true): PendingRequest
    {
        $http = Http::acceptJson()->timeout((int) config('services.fixa_api.espera', 90))->connectTimeout(30);
        if (! $conToken) {
            return $http;
        }

        $usuario = ServicioAutenticacion::getInstance()->usuario()
            ?? throw new ErrorApi('Tu sesión venció. Vuelve a ingresar.', 401);

        return $http->baseUrl(rtrim($this->base(), '/').'/api/v1/panel/')->withToken(TokenPanel::para($usuario));
    }

    private function base(): string
    {
        return (string) config('services.fixa_api.url');
    }

    /** ProblemDetails de la API: reglas de negocio en "title", validación en "errors". */
    private function traducir(Response $respuesta): ErrorApi
    {
        $estado = $respuesta->status();

        return match (true) {
            $estado === 401 => new ErrorApi('La API no aceptó tu sesión. Vuelve a ingresar al panel.', 401),
            $estado === 403 => new ErrorApi('Tu perfil no puede hacer esta acción.', 403),
            $estado === 404 => new ErrorApi($respuesta->json('title') ?: 'El caso no existe o no te corresponde.', 404),
            $estado === 429 => new ErrorApi('Demasiados intentos seguidos. Espera un minuto.', 429),
            is_array($respuesta->json('errors')) && $respuesta->json('errors') !== [] => new ErrorApi(
                collect($respuesta->json('errors'))->flatten()->implode(' '), $estado),
            is_string($respuesta->json('title')) && $estado < 500 => new ErrorApi($respuesta->json('title'), $estado),
            default => new ErrorApi('La API de FIXA tuvo un problema. Intenta de nuevo.', $estado),
        };
    }
}
