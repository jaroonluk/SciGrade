<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class KkuEmailService
{
    /**
     * Send HTML email via KKU API v3 (same flow as ricoh_request_paperII).
     *
     * @param  list<string>|string  $to
     */
    public function send(string|array $to, string $subject, string $htmlMessage, ?string $fromName = null): bool
    {
        $recipients = array_values(array_unique(array_filter(array_map(
            fn ($email) => strtolower(trim((string) $email)),
            is_array($to) ? $to : [$to],
        ), fn (string $email) => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL))));

        if ($recipients === []) {
            Log::warning('KkuEmailService: no valid recipients');

            return false;
        }

        $clientId = trim((string) config('services.kku_api.client_id', ''));
        $secretKey = trim((string) config('services.kku_api.secret_key', ''));
        if ($clientId === '' || $secretKey === '') {
            Log::warning('KkuEmailService: KKU_API_CLIENT_ID / KKU_API_SECRET_KEY not configured');

            return false;
        }

        try {
            $token = $this->fetchToken($clientId, $secretKey);
            if ($token === null) {
                return false;
            }

            $fromName ??= (string) config('services.kku_api.from_name', 'SciGrade');
            $from = (string) config('services.kku_api.from_address', 'noreply@kku.ac.th');
            $ok = true;

            foreach ($recipients as $email) {
                $response = Http::asForm()
                    ->withToken($token)
                    ->timeout(30)
                    ->post('https://api.kku.ac.th/v3/email/send', [
                        'from' => $from,
                        'fromName' => $fromName,
                        'to' => $email,
                        'subject' => $subject,
                        'message' => $htmlMessage,
                    ]);

                if (! $response->successful()) {
                    $ok = false;
                    Log::warning('KkuEmailService: send failed', [
                        'to' => $email,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            }

            return $ok;
        } catch (Throwable $e) {
            Log::error('KkuEmailService: '.$e->getMessage());

            return false;
        }
    }

    private function fetchToken(string $clientId, string $secretKey): ?string
    {
        $response = Http::asForm()
            ->timeout(20)
            ->post('https://api.kku.ac.th/v3/auth/token', [
                'client_id' => $clientId,
                'secret_key' => $secretKey,
            ]);

        if (! $response->successful()) {
            Log::warning('KkuEmailService: token request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $token = $response->json('token');

        return is_string($token) && $token !== '' ? $token : null;
    }
}
