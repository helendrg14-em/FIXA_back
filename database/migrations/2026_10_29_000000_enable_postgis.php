<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Supabase recomienda instalar las extensiones fuera del esquema public.
        DB::statement('CREATE SCHEMA IF NOT EXISTS extensions');
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis WITH SCHEMA extensions');
    }

    public function down(): void
    {
        // No se elimina la extension: otras bases o esquemas podrian depender de ella.
    }
};
