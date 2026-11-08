<?php

namespace App\Reportes;

use App\Categorias\CreadorCategoria;
use App\Enums\CanalOrigen;
use App\Enums\EstadoCaso;
use App\Models\Caso;
use App\Models\Categoria;
use App\Models\Reporte;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * un reporte tiene datos comunes, datos de su categoría y datos que dependen del
 * canal. Se arma paso a paso y registrar() recién valida el conjunto y lo guarda de una
 * sola vez (RNF: guardado atómico e idempotente).
 */
class ReporteBuilder
{
    private const ALFABETO_CLAVE = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const LARGO_CLAVE = 6;

    /** @var array<string, mixed> */
    private array $datos = [];

    /** @var array<int, array{url: string, public_id: string}> */
    private array $fotos = [];

    public function __construct(
        private readonly Categoria $categoria,
        private readonly CanalOrigen $canal,
    ) {}

    public function enDistrito(?int $distritoId): self
    {
        return $this->con('distrito_id', $distritoId);
    }

    public function enUbicacion(float $latitud, float $longitud): self
    {
        return $this->con('latitud', $latitud)->con('longitud', $longitud);
    }

    public function conDireccion(?string $direccion, ?string $referencia = null): self
    {
        return $this->con('direccion', $direccion)->con('referencia', $referencia);
    }

    public function conTipo(string $tipo): self
    {
        return $this->con('tipo_incidencia', $tipo);
    }

    public function conDescripcion(string $descripcion): self
    {
        return $this->con('descripcion', trim($descripcion));
    }

    public function conLuminarias(?int $numero): self
    {
        return $this->con('numero_luminarias', $numero);
    }
    public function conAvisoCobranza(?string $codigo): self
    {
        return $this->con('codigo_aviso_cobranza', $codigo);
    }
    public function conFoto(string $url, string $publicId): self
    {
        $this->fotos[] = ['url' => $url, 'public_id' => $publicId];

        return $this;
    }
    public function sinFoto(string $motivo): self
    {
        return $this->con('motivo_sin_foto', trim($motivo));
    }
    public function registradoPor(int $operadorId): self
    {
        return $this->con('operador_id', $operadorId);
    }
    public function conContacto(?string $correo, ?string $tokenDispositivo = null): self
    {
        return $this->con('correo_contacto', $correo)->con('token_dispositivo', $tokenDispositivo);
    }

    
    public function conClaveIdempotencia(string $clave): self
    {
        return $this->con('clave_idempotencia', $clave);
    }

    public function registrar(): ReporteRegistrado
    {
        $datos = $this->validar();

        $existente = Reporte::where('clave_idempotencia', $datos['clave_idempotencia'])->first();

        if ($existente) {
            return new ReporteRegistrado($existente, null);
        }

        try {
            return DB::transaction(fn (): ReporteRegistrado => $this->guardar($datos));
        } catch (UniqueConstraintViolationException $e) {
            
            $existente = Reporte::where('clave_idempotencia', $datos['clave_idempotencia'])->first();

            return $existente ? new ReporteRegistrado($existente, null) : throw $e;
        }
    }

    private function con(string $campo, mixed $valor): self
    {
        $this->datos[$campo] = $valor;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(): array
    {
        $asistido = $this->canal->esAsistido();
        $conFotos = ['fotos' => $this->fotos, ...$this->datos];

        $reglas = [
            ...CreadorCategoria::para($this->categoria->codigo)->reglas(),
            'distrito_id' => ['required', 'integer', Rule::exists('distritos', 'id')],
            'clave_idempotencia' => ['required', 'string', 'max:64'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'referencia' => ['nullable', 'string', 'max:200'],
            'correo_contacto' => ['nullable', 'email', 'max:120'],
            'token_dispositivo' => ['nullable', 'string', 'max:255'],
            // RN-02: la foto solo se puede omitir en registro asistido, indicando el motivo.
            'fotos' => $asistido ? ['required_without:motivo_sin_foto', 'array'] : ['required', 'array', 'min:1'],
            'motivo_sin_foto' => $asistido ? ['nullable', 'string', 'max:200'] : ['prohibited'],
            'operador_id' => $asistido ? ['required', 'integer', Rule::exists('usuarios', 'id')] : ['prohibited'],
        ];

        Validator::make($conFotos, $reglas, [
            'latitud.required' => 'Marca en el mapa dónde está el problema.',
            'longitud.required' => 'Marca en el mapa dónde está el problema.',
            'fotos.required' => 'Agrega una foto del problema.',
            'fotos.required_without' => 'Agrega una foto o indica por qué no hay.',
            'numero_luminarias.required_unless' => 'Indica cuántas luminarias están afectadas o elige "Otro".',
        ])->validate();

        return $this->datos;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function guardar(array $datos): ReporteRegistrado
    {
        $sinFoto = $this->fotos === [];

        $caso = Caso::create([
            'categoria_id' => $this->categoria->id,
            'unidad_id' => $this->categoria->unidad_id,
            'estado' => EstadoCaso::Recibida,
            'requiere_verificacion_campo' => $sinFoto,
        ]);

        $clave = $this->generarClave();
        $ubicacion = DB::raw(sprintf('ST_SetSRID(ST_MakePoint(%F, %F), 4326)', $datos['longitud'], $datos['latitud']));

        $reporte = $caso->reportes()->create([
            ...collect($datos)->except(['latitud', 'longitud'])->all(),
            'codigo' => CodigoSeguimiento::siguiente(),
            'clave_hash' => Hash::make($clave),
            'canal_origen' => $this->canal,
            'ubicacion' => $ubicacion,
        ]);

        $reporte->fotos()->createMany($this->fotos);

        $caso->eventos()->create([
            'usuario_id' => $datos['operador_id'] ?? null,
            'tipo' => 'registro',
            'estado_nuevo' => EstadoCaso::Recibida->value,
            'detalle' => "Reporte {$reporte->codigo} registrado por {$this->canal->value}.",
        ]);

        return new ReporteRegistrado($reporte, $clave);
    }

    private function generarClave(): string
    {
        $clave = '';

        for ($i = 0; $i < self::LARGO_CLAVE; $i++) {
            $clave .= self::ALFABETO_CLAVE[random_int(0, strlen(self::ALFABETO_CLAVE) - 1)];
        }

        return $clave;
    }
}
