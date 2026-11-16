<?php

namespace Tests\Concerns;

use App\Enums\TipoUsuario;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Las migraciones reales usan PostGIS y RLS, que SQLite no tiene. Las pruebas crean solo
 * las tablas que necesitan y nunca tocan la base de Supabase.
 */
trait EsquemaDePrueba
{
    protected function crearEsquema(): void
    {
        Schema::create('unidades_responsables', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->string('sigla')->unique();
        });
        Schema::create('distritos', function (Blueprint $t) {
            $t->id();
            $t->integer('numero');
            $t->string('nombre');
        });
        Schema::create('cuadrillas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('unidad_id');
            $t->string('nombre');
            $t->string('turno');
        });
        Schema::create('categorias', function (Blueprint $t) {
            $t->id();
            $t->string('codigo')->unique();
            $t->foreignId('unidad_id');
            $t->string('nombre')->unique();
            $t->text('descripcion');
            $t->boolean('activa')->default(true);
            $t->integer('radio_cercania_m');
        });
        Schema::create('metas_atencion', function (Blueprint $t) {
            $t->id();
            $t->foreignId('categoria_id');
            $t->string('prioridad');
            $t->integer('plazo_horas')->nullable();
            $t->integer('plazo_dias_habiles')->nullable();
            $t->date('vigente_desde');
            $t->date('vigente_hasta')->nullable();
        });
        Schema::create('feriados', function (Blueprint $t) {
            $t->id();
            $t->date('fecha')->unique();
            $t->string('nombre');
            $t->string('fuente');
        });
        Schema::create('contadores_codigo', function (Blueprint $t) {
            $t->integer('anio')->primary();
            $t->integer('ultimo');
        });
        Schema::create('casos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('categoria_id');
            $t->foreignId('unidad_id')->nullable();
            $t->string('estado');
            $t->string('prioridad')->nullable();
            $t->text('justificacion_prioridad')->nullable();
            $t->boolean('requiere_verificacion_campo')->default(false);
            $t->boolean('vencido')->default(false);
            $t->timestamp('fecha_creacion')->useCurrent();
            $t->timestamp('fecha_objetivo')->nullable();
            $t->timestamp('fecha_cierre')->nullable();
        });
        Schema::create('reportes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('caso_id');
            $t->foreignId('distrito_id');
            $t->foreignId('operador_id')->nullable();
            $t->string('codigo')->unique();
            $t->string('clave_hash');
            $t->text('descripcion');
            $t->string('tipo_incidencia');
            $t->integer('numero_luminarias')->nullable();
            $t->string('canal_origen');
            $t->string('ubicacion');
            $t->string('direccion')->nullable();
            $t->string('referencia')->nullable();
            $t->string('codigo_aviso_cobranza')->nullable();
            $t->string('motivo_sin_foto')->nullable();
            $t->string('correo_contacto')->nullable();
            $t->string('token_dispositivo')->nullable();
            $t->string('clave_idempotencia')->unique();
            $t->boolean('es_apoyo')->default(false);
            $t->boolean('conformidad')->nullable();
            $t->timestamp('fecha_conformidad')->nullable();
            $t->timestamp('fecha_registro')->useCurrent();
        });
        Schema::create('fotos_reporte', function (Blueprint $t) {
            $t->id();
            $t->foreignId('reporte_id');
            $t->string('url');
            $t->string('public_id');
            $t->timestamp('fecha_carga')->useCurrent();
        });
        Schema::create('eventos_historial', function (Blueprint $t) {
            $t->id();
            $t->foreignId('caso_id');
            $t->foreignId('usuario_id')->nullable();
            $t->timestamp('fecha_hora')->useCurrent();
            $t->string('tipo');
            $t->string('estado_anterior')->nullable();
            $t->string('estado_nuevo')->nullable();
            $t->text('detalle');
            $t->text('motivo')->nullable();
        });

        Schema::create('programaciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('caso_id');
            $t->foreignId('cuadrilla_id');
            $t->date('fecha_prevista');
            $t->string('motivo_reprogramacion')->nullable();
            $t->boolean('vigente')->default(true);
        });
        Schema::create('intervenciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('caso_id');
            $t->foreignId('tecnico_id');
            $t->foreignId('supervisor_id')->nullable();
            $t->timestamp('fecha')->useCurrent();
            $t->text('trabajo_realizado');
            $t->string('estado_validacion');
            $t->text('observacion_validacion')->nullable();
        });
        Schema::create('evidencias', function (Blueprint $t) {
            $t->id();
            $t->foreignId('intervencion_id');
            $t->string('tipo');
            $t->string('url')->nullable();
            $t->string('descripcion')->nullable();
        });
        Schema::create('entidades_externas', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->string('contacto')->nullable();
        });
        Schema::create('solicitudes_revision', function (Blueprint $t) {
            $t->id();
            $t->foreignId('reporte_id');
            $t->foreignId('supervisor_id')->nullable();
            $t->timestamp('fecha_solicitud')->useCurrent();
            $t->text('motivo');
            $t->string('estado')->default('pendiente');
            $t->text('respuesta')->nullable();
            $t->timestamp('fecha_resolucion')->nullable();
        });

        // Sustitutos de PostGIS: el punto se guarda como texto para poder comprobarlo.
        $pdo = DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('ST_MakePoint', fn ($lng, $lat) => "POINT($lng $lat)", 2);
        $pdo->sqliteCreateFunction('ST_SetSRID', fn ($punto, $srid) => "SRID=$srid;$punto", 2);
        Schema::create('usuarios', function (Blueprint $t) {
            $t->id();
            $t->string('tipo');
            $t->string('nombres');
            $t->string('apellidos');
            $t->string('correo')->unique();
            $t->string('password_hash');
            $t->boolean('activo')->default(true);
            $t->boolean('debe_registrar_password')->default(true);
            $t->boolean('requiere_2fa')->default(false);
            $t->string('codigo_2fa_hash')->nullable();
            $t->timestamp('codigo_2fa_expira')->nullable();
            $t->string('token_recuperacion_hash')->nullable();
            $t->timestamp('token_expira')->nullable();
            $t->timestamp('ultimo_acceso')->nullable();
            $t->timestamp('fecha_creacion')->useCurrent();
            $t->string('punto_atencion')->nullable();
            $t->foreignId('distrito_id')->nullable();
            $t->string('cargo')->nullable();
            $t->foreignId('unidad_id')->nullable();
            $t->string('especialidad')->nullable();
            $t->foreignId('cuadrilla_id')->nullable();
        });
    }

    protected function usuario(TipoUsuario $tipo = TipoUsuario::Administrador, array $datos = []): User
    {
        return User::forceCreate([
            'tipo' => $tipo,
            'nombres' => 'Nombre',
            'apellidos' => 'Prueba',
            'correo' => uniqid('prueba').'@fixa.test',
            'password_hash' => 'clave-de-prueba-123',
            'activo' => true,
            'debe_registrar_password' => false,
            ...$datos,
        ]);
    }
}
