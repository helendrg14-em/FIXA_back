<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidades_responsables', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('sigla', 20)->unique();
        });

        Schema::create('distritos', function (Blueprint $table) {
            $table->id();
            $table->integer('numero')->unique();
            $table->string('nombre', 80);
            $table->geometry('limite', subtype: 'polygon', srid: 4326);

            $table->spatialIndex('limite');
        });

        Schema::create('entidades_externas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('contacto', 120)->nullable();
        });

        Schema::create('cuadrillas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_id')->index()->constrained('unidades_responsables')->restrictOnDelete();
            $table->string('nombre', 60);
            $table->string('turno', 10);
        });

        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_id')->index()->constrained('unidades_responsables')->restrictOnDelete();
            $table->string('nombre', 60)->unique();
            $table->text('descripcion');
            $table->boolean('activa')->default(true);
            $table->integer('radio_cercania_m');
        });

        Schema::create('metas_atencion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->index()->constrained('categorias')->restrictOnDelete();
            $table->string('prioridad', 10);
            $table->integer('plazo_horas');
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metas_atencion');
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('cuadrillas');
        Schema::dropIfExists('entidades_externas');
        Schema::dropIfExists('distritos');
        Schema::dropIfExists('unidades_responsables');
    }
};
