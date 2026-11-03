<?php

return [

    // Enlace de descarga de la app Android. Sin FIXA_APP_URL se usa el último APK publicado en GitHub Releases.
    'app_url' => env('FIXA_APP_URL') ?: 'https://github.com/helendrg14-em/FIXA_app/releases/latest/download/fixa.apk',

    // Calendario de Bolivia: define la gestión del código y qué día es hábil o feriado.
    'zona_horaria' => 'America/La_Paz',

    // Primer administrador, usado solo por AdminSeeder.
    'admin' => [
        'nombres' => env('ADMIN_NOMBRES'),
        'apellidos' => env('ADMIN_APELLIDOS'),
        'correo' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
