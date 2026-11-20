<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;
use Symfony\Component\Mime\Email;

trait SinSeguimientoDeClics
{
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
