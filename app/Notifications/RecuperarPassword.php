<?php

namespace App\Notifications;

use App\Notifications\Concerns\SinSeguimientoDeClics;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecuperarPassword extends Notification
{
    use SinSeguimientoDeClics;

    public function __construct(private readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->sinSeguimientoDeClics(new MailMessage)
            ->subject('Cambia tu contraseña de FIXA')
            ->greeting("Hola, {$notifiable->nombres}")
            ->line('Recibimos una solicitud para cambiar la contraseña de tu cuenta de FIXA.')
            ->action('Crear una contraseña nueva', $notifiable->enlaceContrasena($this->token))
            ->line('Este enlace vence en 60 minutos y solo se puede usar una vez.')
            ->salutation('Si no lo pediste, ignora este correo: tu contraseña actual sigue funcionando.');
    }
}
