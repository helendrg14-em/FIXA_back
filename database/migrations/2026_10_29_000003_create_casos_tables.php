<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->index()->constrained('categorias')->restrictOnDelete();
            $table->foreignId('unidad_id')->nullable()->index()->constrained('unidades_responsables')->restrictOnDelete();
            $table->string('estado', 25)->index();
            $table->string('prioridad', 10)->nullable();
            $table->text('justificacion_prioridad')->nullable();
            $table->boolean('requiere_verificacion_campo')->default(false);
            $table->boolean('vencido')->default(false);
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_objetivo')->nullable();
            $table->timestamp('fecha_cierre')->nullable();
        });

        Schema::create('reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caso_id')->index()->constrained('casos')->restrictOnDelete();
            $table->foreignId('distrito_id')->index()->constrained('distritos')->restrictOnDelete();
            $table->foreignId('operador_id')->nullable()->index()->constrained('usuarios')->restrictOnDelete();
            $table->string('codigo', 20)->unique();
            $table->string('clave_hash', 255);
            $table->text('descripcion');
            $table->string('canal_origen', 25);
            $table->geometry('ubicacion', subtype: 'point', srid: 4326)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('referencia', 200)->nullable();
            $table->string('codigo_aviso_cobranza', 30)->nullable();
            $table->string('motivo_sin_foto', 200)->nullable();
            $table->string('correo_contacto', 120)->nullable();
            $table->string('token_dispositivo', 255)->nullable();
            $table->string('clave_idempotencia', 64)->unique();
            $table->boolean('es_apoyo')->default(false);
            $table->boolean('conformidad')->nullable();
            $table->timestamp('fecha_conformidad')->nullable();
            $table->timestamp('fecha_registro')->useCurrent();

            $table->spatialIndex('ubicacion');
        });

        Schema::create('fotos_reporte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporte_id')->index()->constrained('reportes')->restrictOnDelete();
            $table->string('url', 500);
            $table->string('public_id', 200);
            $table->timestamp('fecha_carga')->useCurrent();
        });

        Schema::create('eventos_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caso_id')->index()->constrained('casos')->restrictOnDelete();
            $table->foreignId('usuario_id')->nullable()->index()->constrained('usuarios')->restrictOnDelete();
            $table->timestamp('fecha_hora')->useCurrent();
            $table->string('tipo', 20);
            $table->string('estado_anterior', 25)->nullable();
            $table->string('estado_nuevo', 25)->nullable();
            $table->text('detalle');
            $table->text('motivo')->nullable();
        });

        // RNF08: el historial es inmutable; las correcciones se registran como un evento nuevo.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.impedir_cambios_historial()
            RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = ''
            AS $$
            BEGIN
                RAISE EXCEPTION 'eventos_historial es inmutable: registre un evento nuevo';
            END;
            $$;

            CREATE TRIGGER eventos_historial_inmutable
                BEFORE UPDATE OR DELETE ON public.eventos_historial
                FOR EACH ROW EXECUTE FUNCTION public.impedir_cambios_historial();

            CREATE TRIGGER eventos_historial_sin_truncate
                BEFORE TRUNCATE ON public.eventos_historial
                FOR EACH STATEMENT EXECUTE FUNCTION public.impedir_cambios_historial();
        SQL);

        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')->index()->constrained('eventos_historial')->restrictOnDelete();
            $table->foreignId('reporte_id')->index()->constrained('reportes')->restrictOnDelete();
            $table->string('canal', 10);
            $table->string('destino', 255);
            $table->timestamp('fecha_envio')->nullable();
            $table->string('estado_entrega', 15);
        });

        Schema::create('solicitudes_revision', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporte_id')->index()->constrained('reportes')->restrictOnDelete();
            $table->foreignId('supervisor_id')->nullable()->index()->constrained('usuarios')->restrictOnDelete();
            $table->timestamp('fecha_solicitud')->useCurrent();
            $table->text('motivo');
            $table->string('estado', 15);
            $table->text('respuesta')->nullable();
            $table->timestamp('fecha_resolucion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_revision');
        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('eventos_historial');
        DB::statement('DROP FUNCTION IF EXISTS public.impedir_cambios_historial()');
        Schema::dropIfExists('fotos_reporte');
        Schema::dropIfExists('reportes');
        Schema::dropIfExists('casos');
    }
};
