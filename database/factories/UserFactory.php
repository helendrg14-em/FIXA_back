<?php

namespace Database\Factories;

use App\Enums\TipoUsuario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => TipoUsuario::Tecnico,
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'correo' => fake()->unique()->safeEmail(),
            'password_hash' => static::$password ??= Hash::make('password'),
            'activo' => true,
            'debe_registrar_password' => false,
        ];
    }

    public function administrador(): static
    {
        return $this->state(fn () => ['tipo' => TipoUsuario::Administrador]);
    }

    public function supervisor(): static
    {
        return $this->state(fn () => ['tipo' => TipoUsuario::Supervisor]);
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
