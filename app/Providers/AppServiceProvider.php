<?php

namespace App\Providers;

use App\Auth\ServicioAutenticacion;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // En Render las URL de los archivos se generan antes de leer el proxy y salían con http,
        // así que el navegador bloqueaba las fuentes del panel por contenido mixto.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                ServicioAutenticacion::getInstance()->registrarAcceso($event->user);
            }
        });
    }
}
