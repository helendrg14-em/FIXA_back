<?php

namespace App\Notifications;

use App\Notifications\Concerns\SinSeguimientoDeClics;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrarPassword extends Notification
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
            ->subject('Crea tu contraseña de FIXA')
            ->greeting("Hola, {$notifiable->nombres}")
            ->line('Se creó tu cuenta de funcionario en FIXA. Para entrar al panel, primero crea tu contraseña.')
            ->action('Crear mi contraseña', $notifiable->enlaceContrasena($this->token))
            ->line('Este enlace vence en 48 horas y solo se puede usar una vez.')
            ->salutation('Si no esperabas este correo, ignóralo.');
    }
}
