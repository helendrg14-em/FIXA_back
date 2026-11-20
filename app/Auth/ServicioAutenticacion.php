<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use LogicException;
use SensitiveParameter;

final class ServicioAutenticacion
{
    private static ?self $instancia = null;

    private function __construct() {}

    private function __clone() {}

    public function __wakeup(): void
    {
        throw new LogicException('El Singleton de autenticación no se puede deserializar.');
    }

    public static function getInstance(): self
    {
        return self::$instancia ??= new self;
    }

    public function usuario(): ?User
    {
        $usuario = Auth::user();

        return $usuario instanceof User ? $usuario : null;
    }

    public function esAdministrador(): bool
    {
        return $this->usuario()?->esAdministrador() ?? false;
    }

    public function credenciales(string $correo, #[SensitiveParameter] string $password): array
    {
        return [
            'correo' => mb_strtolower(trim($correo)),
            'password' => $password,
        ];
    }

    public function registrarAcceso(User $usuario): void
    {
        $usuario->forceFill(['ultimo_acceso' => now()])->save();
    }
}
