<?php

namespace App\Http\Controllers\Cuenta;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Throwable;

use function Illuminate\Support\defer;

/**
 * "¿Olvidaste tu contraseña?". La respuesta es siempre la misma y el correo sale después
 * de responder, para que ni el mensaje ni la demora revelen si una cuenta existe.
 */
class RecuperarPasswordController extends Controller
{
    public function create(): View
    {
        return view('cuenta.recuperar');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate(
            ['correo' => ['required', 'email', 'max:120']],
            ['correo.required' => 'Escribe tu correo.', 'correo.email' => 'Revisa que el correo esté bien escrito.'],
        );

        $correo = mb_strtolower(trim($datos['correo']));
        $usuario = User::where('correo', $correo)->first();

        // Hasta 3 correos por cuenta cada 15 minutos: nadie puede llenar la bandeja de otro.
        if ($usuario?->activo && $usuario->tipo?->puedeEntrarAlPanel()
            && RateLimiter::attempt("recuperar:{$usuario->id}", 3, fn () => true, 15 * 60)) {
            defer(function () use ($usuario): void {
                try {
                    $usuario->enviarEnlaceRecuperacion();
                } catch (Throwable $e) {
                    report($e);
                }
            });
        }

        return to_route('cuenta.recuperar')->with('enviado', $correo);
    }
}
