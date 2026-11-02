<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->string('codigo', 20)->nullable()->after('id');
        });

        // Se asigna por unidad porque el nombre de la categoría se puede editar.
        $codigos = ['SMOP' => 'bache', 'UOD-AP' => 'alumbrado', 'EMACRUZ' => 'residuos'];

        foreach ($codigos as $sigla => $codigo) {
            DB::table('categorias')
                ->whereIn('unidad_id', DB::table('unidades_responsables')->where('sigla', $sigla)->select('id'))
                ->update(['codigo' => $codigo]);
        }

        Schema::table('categorias', function (Blueprint $table) {
            $table->string('codigo', 20)->nullable(false)->unique()->change();
        });

        DB::statement("ALTER TABLE categorias ADD CONSTRAINT categorias_codigo_check CHECK (codigo IN ('bache', 'alumbrado', 'residuos'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE categorias DROP CONSTRAINT IF EXISTS categorias_codigo_check');

        Schema::table('categorias', function (Blueprint $table) {
            $table->dropUnique(['codigo']);
            $table->dropColumn('codigo');
        });
    }
};
