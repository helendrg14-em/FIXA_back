<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;
use Symfony\Component\Mime\Email;

trait SinSeguimientoDeClics
{
    /**
     * Mailgun reemplaza cada enlace por uno suyo para contar los clics. En los correos de
     * contraseña eso rompe el botón y además le pasa el token a un tercero, así que se
     * desactiva por mensaje con la opción o:tracking-clicks de su API.
     */
    protected function sinSeguimientoDeClics(MailMessage $mensaje): MailMessage
    {
        return $mensaje->withSymfonyMessage(function (Email $correo): void {
            $correo->getHeaders()->addTextHeader('o:tracking-clicks', 'no');
        });
    }
}
