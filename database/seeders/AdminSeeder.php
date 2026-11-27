<?php

namespace Database\Seeders;

use App\Enums\TipoUsuario;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Crea el primer administrador con los datos del .env (nunca escritos en el codigo).
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $correo = config('fixa.admin.correo');
        $password = config('fixa.admin.password');

        if (blank($correo) || blank($password)) {
            throw new RuntimeException('Define ADMIN_EMAIL y ADMIN_PASSWORD en el .env antes de ejecutar el seeder.');
        }

        if (mb_strlen($password) < 12) {
            throw new RuntimeException('ADMIN_PASSWORD debe tener al menos 12 caracteres.');
        }

        $admin = User::firstOrNew(['correo' => mb_strtolower(trim($correo))]);

        if ($admin->exists) {
            $this->command?->info("El administrador {$admin->correo} ya existe; no se modifico.");

            return;
        }

        $admin->fill([
            'rol' => TipoUsuario::Administrador,
            'activo' => true,
            'debe_registrar_password' => false,
            'nombres' => config('fixa.admin.nombres') ?: 'Administrador',
            'apellidos' => config('fixa.admin.apellidos') ?: 'FIXA',
            'password_hash' => $password,
        ])->save();

        $this->command?->info("Administrador {$admin->correo} creado.");
    }
}
