<?php

namespace App\Api;

use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Token JWT (HS256) con el que el panel habla con la API en nombre del funcionario.
 *
 * Lleva los mismos datos que entrega POST /api/v1/panel/auth/login de la API (sub, rol y
 * nombre) y vence a los 15 minutos, igual que la sesión (RNF-06). La API vuelve a comprobar
 * en cada pedido que la cuenta siga activa, así que desactivar a alguien corta su acceso
 * al instante también aquí.
 */
final class TokenPanel
{
    public const DURACION_SEGUNDOS = 15 * 60;

    public static function para(User $usuario, ?int $ahora = null): string
    {
        $clave = (string) config('services.fixa_api.jwt_clave');
        if (strlen($clave) < 32) {
            throw new RuntimeException('Falta configurar FIXA_API_JWT_CLAVE (la misma clave Jwt__Clave de la API).');
        }

        $ahora ??= time();
        $cabecera = ['alg' => 'HS256', 'typ' => 'JWT'];
        $datos = [
            'sub' => (string) $usuario->getKey(),
            'jti' => (string) Str::uuid(),
            // Nombres que la API lee como ClaimTypes.Role y ClaimTypes.Name.
            'role' => $usuario->tipo->value,
            'unique_name' => $usuario->getFilamentName(),
            'iat' => $ahora,
            'nbf' => $ahora,
            'exp' => $ahora + self::DURACION_SEGUNDOS,
        ];

        $sinFirma = self::base64Url(json_encode($cabecera, JSON_THROW_ON_ERROR)).'.'
            .self::base64Url(json_encode($datos, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        return $sinFirma.'.'.self::base64Url(hash_hmac('sha256', $sinFirma, $clave, true));
    }

    private static function base64Url(string $valor): string
    {
        return rtrim(strtr(base64_encode($valor), '+/', '-_'), '=');
    }
}
