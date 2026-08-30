<?php

namespace Tests\Feature;

use Tests\TestCase;

class GoogleOAuthRedirectHostTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://gms.sc.kku.ac.th',
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'https://gms.sc.kku.ac.th/auth/google/callback',
        ]);
    }

    public function test_google_oauth_callback_stays_on_local_host(): void
    {
        $location = $this->get('http://127.0.0.1:8000/auth/google')->headers->get('Location');

        $this->assertNotEmpty($location);
        $this->assertStringContainsString('accounts.google.com', (string) $location);
        $this->assertStringContainsString(
            rawurlencode('http://127.0.0.1:8000/auth/google/callback'),
            (string) $location,
        );
        $this->assertStringNotContainsString(
            rawurlencode('https://gms.sc.kku.ac.th/auth/google/callback'),
            (string) $location,
        );
    }

    public function test_google_oauth_callback_stays_on_production_host(): void
    {
        $location = $this->get('https://gms.sc.kku.ac.th/auth/google')->headers->get('Location');

        $this->assertNotEmpty($location);
        $this->assertStringContainsString('accounts.google.com', (string) $location);
        $this->assertStringContainsString(
            rawurlencode('https://gms.sc.kku.ac.th/auth/google/callback'),
            (string) $location,
        );
    }

    public function test_guest_root_redirects_to_login_on_the_same_host(): void
    {
        $this->get('http://127.0.0.1:8000/')
            ->assertRedirect('http://127.0.0.1:8000/login');

        $this->get('https://gms.sc.kku.ac.th/')
            ->assertRedirect('https://gms.sc.kku.ac.th/login');
    }
}
