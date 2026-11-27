<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE usuarios DROP CONSTRAINT IF EXISTS usuarios_tipo_check');

        Schema::table('usuarios', function (Blueprint $table) {
            $table->renameColumn('tipo', 'rol');
        });

        DB::statement("ALTER TABLE usuarios ADD CONSTRAINT usuarios_rol_check CHECK (rol IN ('administrador', 'supervisor', 'tecnico', 'operador'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE usuarios DROP CONSTRAINT IF EXISTS usuarios_rol_check');

        Schema::table('usuarios', function (Blueprint $table) {
            $table->renameColumn('rol', 'tipo');
        });

        DB::statement("ALTER TABLE usuarios ADD CONSTRAINT usuarios_tipo_check CHECK (tipo IN ('administrador', 'supervisor', 'tecnico', 'operador'))");
    }
};
