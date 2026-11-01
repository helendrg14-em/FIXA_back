<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Supabase publica una API REST sobre el esquema public. Con RLS activo y sin
     * politicas, los roles anon y authenticated no pueden leer ni escribir nada;
     * Laravel entra como dueno de las tablas y no se ve afectado.
     *
     * Toda tabla nueva debe activar RLS en su propia migracion.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            DO $$
            DECLARE t record;
            BEGIN
                FOR t IN SELECT tablename FROM pg_tables WHERE schemaname = 'public' LOOP
                    EXECUTE format('ALTER TABLE public.%I ENABLE ROW LEVEL SECURITY', t.tablename);
                END LOOP;
            END;
            $$;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DO $$
            DECLARE t record;
            BEGIN
                FOR t IN SELECT tablename FROM pg_tables WHERE schemaname = 'public' LOOP
                    EXECUTE format('ALTER TABLE public.%I DISABLE ROW LEVEL SECURITY', t.tablename);
                END LOOP;
            END;
            $$;
        SQL);
    }
};
