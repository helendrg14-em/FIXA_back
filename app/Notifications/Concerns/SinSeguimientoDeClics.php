<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;
use Symfony\Component\Mime\Email;

trait SinSeguimientoDeClics
{
    /**
     * Mailgun reemplaza cada enlace por uno suyo para contar los clics. En los correos de
     * contraseña eso rompe el botón y además le pasa el token a un tercero, así que se
     * desactiva por mensaje. El panel envía el correo como MIME (transporte mailgun+https),
     * donde Mailgun solo lee las cabeceras X-Mailgun-*; o:tracking-clicks sirve con su API.
     */
    protected function sinSeguimientoDeClics(MailMessage $mensaje): MailMessage
    {
        return $mensaje->withSymfonyMessage(function (Email $correo): void {
            $cabeceras = $correo->getHeaders();
            $cabeceras->addTextHeader('X-Mailgun-Track', 'no');
            $cabeceras->addTextHeader('X-Mailgun-Track-Clicks', 'no');
            $cabeceras->addTextHeader('o:tracking-clicks', 'no');
        });
    }
}
