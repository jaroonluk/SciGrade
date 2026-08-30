<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\StaffAuthService;
use App\Support\SciGradeRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly AuditLogService $auditLog,
    ) {}

    public function redirect(): SymfonyRedirectResponse|RedirectResponse
    {
        try {
            return $this->google()->redirect();
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
            $googleUser = $this->google()->user();
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

        try {
            $staff = $this->staffAuth->findByEmail($email);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Staff lookup failed after Google login', [
                'message' => $e->getMessage(),
            ]);

            return $this->oauthFailureRedirect(
                'ไม่สามารถเชื่อมต่อฐานข้อมูลบุคลากรได้ กรุณาตรวจสอบการตั้งค่าฐานข้อมูลบนเครื่องนี้',
            );
        }

        if (! $staff) {
            $this->auditLog->record('auth.denied', metadata: [
                'email' => $email,
            ]);

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

        $role = SciGradeRole::staffHasSuperPrivilege($staff->username)
            ? SciGradeRole::SUPER_ADMIN
            : SciGradeRole::INSTRUCTOR;

        session(['scigrade_role' => $role]);

        $this->auditLog->record(
            'auth.login',
            subjectType: 'staff',
            subjectId: $staff->username,
            metadata: ['email' => $email],
            actorUsername: $staff->username,
            actorRole: $role,
        );

        return redirect()->intended(route('dashboard'));
    }

    public function logout(): RedirectResponse
    {
        $username = session('staff_username');
        $role = session('scigrade_role');

        $this->auditLog->record(
            'auth.logout',
            subjectType: 'staff',
            subjectId: $username,
            actorUsername: $username ? (string) $username : null,
            actorRole: $role ? (string) $role : null,
        );

        Auth::logout();

        $this->staffAuth->clearSession();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function google(): GoogleProvider
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirectUrl(route('auth.google.callback'));
    }

    private function oauthFailureRedirect(string $message): RedirectResponse
    {
        return redirect()
            ->route('login', ['oauth_error' => 1])
            ->withErrors(['email' => $message]);
    }
}
