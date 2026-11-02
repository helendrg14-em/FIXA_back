<?php

return [

    // Enlace de descarga de la app Android. Vacío: el portal avisa que estará disponible pronto.
    'app_url' => env('FIXA_APP_URL'),

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
