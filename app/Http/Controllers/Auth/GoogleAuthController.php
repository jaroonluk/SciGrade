<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StaffAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
    ) {}

    public function redirect(): SymfonyRedirectResponse|RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors([
                'email' => 'ไม่สามารถเข้าสู่ระบบด้วย Google ได้ กรุณาลองใหม่อีกครั้ง',
            ]);
        }

        $email = $googleUser->getEmail();
        $staff = $this->staffAuth->findByEmail($email);

        if (! $staff) {
            return redirect()->route('login')->withErrors([
                'email' => StaffAuthService::ACCESS_DENIED_MESSAGE,
            ]);
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
}
