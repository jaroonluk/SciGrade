<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StaffAuthService;
use App\Support\SciGradeRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
    ) {}

    public function redirect(): SymfonyRedirectResponse|RedirectResponse
    {
        try {
            return Socialite::driver('google')->stateless()->redirect();
        } catch (\Throwable $e) {
            Log::error('Google OAuth redirect failed', [
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return $this->oauthFailureRedirect(
                'ไม่สามารถเริ่มเข้าสู่ระบบด้วย Google ได้ กรุณาลองใหม่อีกครั้ง',
            );
        }
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback failed', [
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return $this->oauthFailureRedirect(
                'ไม่สามารถเข้าสู่ระบบด้วย Google ได้ กรุณาลองใหม่อีกครั้ง',
            );
        }

        $email = $googleUser->getEmail();
        $staff = $this->staffAuth->findByEmail($email);

        if (! $staff) {
            return $this->oauthFailureRedirect(StaffAuthService::ACCESS_DENIED_MESSAGE);
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $staff->displayName(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                'password' => bcrypt(str()->random(32)),
            ],
        );

        Auth::login($user, remember: true);

        $this->staffAuth->storeInSession($staff);

        session([
            'scigrade_role' => SciGradeRole::staffHasSuperPrivilege($staff->username)
                ? SciGradeRole::SUPER_ADMIN
                : SciGradeRole::INSTRUCTOR,
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();

        $this->staffAuth->clearSession();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function oauthFailureRedirect(string $message): RedirectResponse
    {
        return redirect()
            ->route('login', ['oauth_error' => 1])
            ->withErrors(['email' => $message]);
    }
}
