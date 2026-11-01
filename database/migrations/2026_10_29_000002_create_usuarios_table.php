<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);
            $table->string('nombres', 80);
            $table->string('apellidos', 80);
            $table->string('correo', 120)->unique();
            $table->string('password_hash', 255);
            $table->boolean('activo')->default(true);
            $table->boolean('debe_registrar_password')->default(true);
            $table->boolean('requiere_2fa')->default(false);
            $table->string('codigo_2fa_hash', 255)->nullable();
            $table->timestamp('codigo_2fa_expira')->nullable();
            $table->string('token_recuperacion_hash', 255)->nullable();
            $table->timestamp('token_expira')->nullable();
            $table->timestamp('ultimo_acceso')->nullable();
            $table->timestamp('fecha_creacion')->useCurrent();

            // Operador de atencion (version futura, R07)
            $table->string('punto_atencion', 80)->nullable();
            $table->foreignId('distrito_id')->nullable()->index()->constrained('distritos')->restrictOnDelete();

            // Supervisor
            $table->string('cargo', 80)->nullable();
            $table->foreignId('unidad_id')->nullable()->index()->constrained('unidades_responsables')->restrictOnDelete();

            // Tecnico
            $table->string('especialidad', 80)->nullable();
            $table->foreignId('cuadrilla_id')->nullable()->index()->constrained('cuadrillas')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE usuarios ADD CONSTRAINT usuarios_tipo_check CHECK (tipo IN ('administrador', 'supervisor', 'tecnico', 'operador'))");

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('usuarios');
    }
};
