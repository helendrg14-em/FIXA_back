<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use LogicException;
use SensitiveParameter;

/**
 * Singleton: toda la autenticación de funcionarios pasa por un único punto de acceso, así
 * las reglas de acceso no se repiten en el login, el panel y la futura API.
 *
 * Laravel no se reemplaza: la sesión, el hash y los intentos siguen en su sistema de Auth.
 * Por eso esta clase no guarda al usuario en un atributo: la instancia es compartida y cada
 * llamada pregunta a Auth, para que un funcionario nunca reciba la sesión de otro.
 */
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

    /**
     * Credenciales que Auth espera: la cuenta se busca por correo y sin distinguir mayúsculas.
     *
     * @return array{correo: string, password: string}
     */
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
