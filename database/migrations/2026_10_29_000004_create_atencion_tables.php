<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caso_id')->index()->constrained('casos')->restrictOnDelete();
            $table->foreignId('cuadrilla_id')->index()->constrained('cuadrillas')->restrictOnDelete();
            $table->date('fecha_prevista');
            $table->string('motivo_reprogramacion', 200)->nullable();
            $table->boolean('vigente')->default(true);
        });

        Schema::create('intervenciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caso_id')->index()->constrained('casos')->restrictOnDelete();
            $table->foreignId('tecnico_id')->index()->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('supervisor_id')->nullable()->index()->constrained('usuarios')->restrictOnDelete();
            $table->timestamp('fecha')->useCurrent();
            $table->text('trabajo_realizado');
            $table->string('estado_validacion', 15);
            $table->text('observacion_validacion')->nullable();
        });

        Schema::create('evidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intervencion_id')->index()->constrained('intervenciones')->restrictOnDelete();
            $table->string('tipo', 15);
            $table->string('url', 500);
            $table->string('descripcion', 200)->nullable();
        });

        Schema::create('derivaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caso_id')->index()->constrained('casos')->restrictOnDelete();
            $table->foreignId('entidad_id')->index()->constrained('entidades_externas')->restrictOnDelete();
            $table->foreignId('responsable_id')->index()->constrained('usuarios')->restrictOnDelete();
            $table->text('motivo');
            $table->timestamp('fecha_derivacion')->useCurrent();
            $table->string('estado_seguimiento', 25);
            $table->text('respuesta_entidad')->nullable();
            $table->timestamp('fecha_respuesta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('derivaciones');
        Schema::dropIfExists('evidencias');
        Schema::dropIfExists('intervenciones');
        Schema::dropIfExists('programaciones');
    }
};
