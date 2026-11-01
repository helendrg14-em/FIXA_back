<?php

return [

    // Primer administrador, usado solo por AdminSeeder.
    'admin' => [
        'nombres' => env('ADMIN_NOMBRES'),
        'apellidos' => env('ADMIN_APELLIDOS'),
        'correo' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
