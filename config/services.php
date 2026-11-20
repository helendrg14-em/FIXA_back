<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    // API en C# (Fixa_API). El panel firma el token de cada funcionario con la misma clave
    // que usa la API (Jwt__Clave en Render), así no se pide la contraseña dos veces.
    'fixa_api' => [
        'url' => env('FIXA_API_URL', 'https://fixa-api-nhua.onrender.com'),
        'jwt_clave' => env('FIXA_API_JWT_CLAVE'),
        'espera' => (int) env('FIXA_API_ESPERA', 90), // Render gratis tarda en despertar
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
