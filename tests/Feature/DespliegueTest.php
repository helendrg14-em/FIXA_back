<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DespliegueTest extends TestCase
{
    public function test_detras_del_proxy_laravel_detecta_https(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security');
    }

    public function test_sin_proxy_la_peticion_sigue_siendo_http(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_mailgun_envia_por_api_y_no_por_smtp(): void
    {
        config([
            'services.mailgun.domain' => 'sandbox.example.test',
            'services.mailgun.secret' => 'clave-de-prueba',
        ]);

        $transporte = (string) Mail::mailer('mailgun')->getSymfonyTransport();

        $this->assertStringStartsWith('mailgun+https://api.mailgun.net', $transporte);
    }
}
