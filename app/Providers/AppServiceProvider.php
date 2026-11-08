<?php

namespace App\Providers;

use App\Auth\ServicioAutenticacion;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                ServicioAutenticacion::getInstance()->registrarAcceso($event->user);
            }
        });
    }
}
