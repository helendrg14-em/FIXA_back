<?php

namespace App\Http\Controllers\Cuenta;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegistrarPasswordController extends Controller
{
    public function show(Request $request, int $usuario): View
    {
        $user = $this->usuarioConToken($usuario, (string) $request->query('token'));

        return view('cuenta.contrasena', [
            'usuario' => $user->id,
            'token' => $request->query('token'),
            // Cuenta nueva o contraseña olvidada: mismo formulario, distinto título.
            'nueva' => $user->debe_registrar_password,
        ]);
    }

    public function store(Request $request, int $usuario): View
    {
        $user = $this->usuarioConToken($usuario, (string) $request->input('token'));

        $datos = $request->validate([
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ], [
            'password.required' => 'Escribe una contraseña.',
            'password.confirmed' => 'Las dos contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 12 caracteres.',
            'password.letters' => 'La contraseña debe incluir al menos una letra.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
        ]);

        $user->registrarPassword($datos['password']);

        return view('cuenta.contrasena-lista');
    }

    private function usuarioConToken(int $id, string $token): User
    {
        $user = User::find($id);

        abort_unless($user?->activo && $user->validarToken($token), 403, 'Este enlace no es válido o ya venció.');

        return $user;
    }
}
