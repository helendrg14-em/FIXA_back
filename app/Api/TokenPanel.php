<?php

namespace App\Api;

use App\Models\User;
use Illuminate\Support\Str;

final class TokenPanel
{
    public const DURACION_SEGUNDOS = 15 * 60;

    public static function para(User $usuario, ?int $ahora = null): string
    {
        $clave = (string) config('services.fixa_api.jwt_clave');
        if (strlen($clave) < 32) {
            report(new \RuntimeException('Falta configurar FIXA_API_JWT_CLAVE.'));

            throw new ErrorApi('No se pudo completar la acción. Avisa al equipo de soporte de FIXA.');
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
