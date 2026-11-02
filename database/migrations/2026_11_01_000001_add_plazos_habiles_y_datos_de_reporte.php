<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // RN-06: un plazo se mide en horas corridas o en días hábiles, nunca en ambos.
        Schema::table('metas_atencion', function (Blueprint $table) {
            $table->integer('plazo_horas')->nullable()->change();
            $table->integer('plazo_dias_habiles')->nullable()->after('plazo_horas');
        });
        DB::statement('ALTER TABLE metas_atencion ADD CONSTRAINT metas_atencion_plazo_check CHECK ((plazo_horas IS NULL) <> (plazo_dias_habiles IS NULL))');

        Schema::create('feriados', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->string('nombre', 100);
            $table->string('fuente', 200);
        });

        // Último número entregado por gestión, para FX-AAAA-000001 sin saltos ni repetidos.
        Schema::create('contadores_codigo', function (Blueprint $table) {
            $table->integer('anio')->primary();
            $table->integer('ultimo');
        });

        Schema::table('reportes', function (Blueprint $table) {
            $table->string('tipo_incidencia', 30)->after('descripcion');
            $table->integer('numero_luminarias')->nullable()->after('tipo_incidencia');
        });
        DB::statement('ALTER TABLE reportes ADD CONSTRAINT reportes_numero_luminarias_check CHECK (numero_luminarias > 0)');
        DB::statement('ALTER TABLE reportes ALTER COLUMN ubicacion SET NOT NULL');

        DB::statement('ALTER TABLE public.feriados ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE public.contadores_codigo ENABLE ROW LEVEL SECURITY');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE reportes ALTER COLUMN ubicacion DROP NOT NULL');
        DB::statement('ALTER TABLE reportes DROP CONSTRAINT IF EXISTS reportes_numero_luminarias_check');

        Schema::table('reportes', function (Blueprint $table) {
            $table->dropColumn(['tipo_incidencia', 'numero_luminarias']);
        });

        Schema::dropIfExists('contadores_codigo');
        Schema::dropIfExists('feriados');

        // Los plazos en días no tienen equivalente exacto en horas: volver atrás exige borrarlos.
        DB::statement('ALTER TABLE metas_atencion DROP CONSTRAINT IF EXISTS metas_atencion_plazo_check');
        DB::table('metas_atencion')->whereNull('plazo_horas')->delete();

        Schema::table('metas_atencion', function (Blueprint $table) {
            $table->dropColumn('plazo_dias_habiles');
            $table->integer('plazo_horas')->nullable(false)->change();
        });
    }
};
