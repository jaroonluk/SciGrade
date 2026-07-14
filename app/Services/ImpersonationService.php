<?php

namespace App\Services;

use App\Models\TblUser;
use App\Models\User;
use App\Support\SciGradeRole;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class ImpersonationService
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
    ) {}

    /**
     * @param  string  $targetRole  instructor|dept_admin|faculty_admin
     */
    public function start(TblUser $targetStaff, string $targetRole): void
    {
        if (! SciGradeRole::canImpersonate()) {
            throw new InvalidArgumentException('เฉพาะ Super Admin เท่านั้นที่เข้าใช้งานแทนได้');
        }

        if (SciGradeRole::isImpersonating()) {
            throw new InvalidArgumentException('กำลังเข้าใช้งานแทนผู้อื่นอยู่แล้ว กรุณาออกก่อน');
        }

        if (! in_array($targetRole, [SciGradeRole::INSTRUCTOR, SciGradeRole::DEPT_ADMIN, SciGradeRole::FACULTY_ADMIN], true)) {
            throw new InvalidArgumentException('บทบาทที่เลือกไม่ถูกต้อง');
        }

        $email = trim((string) ($targetStaff->email ?? ''));
        if ($email === '') {
            throw new InvalidArgumentException('ผู้ใช้งานคนนี้ยังไม่มีอีเมลในระบบ ไม่สามารถเข้าแทนได้');
        }

        $actor = Auth::user();
        abort_unless($actor, 403);

        session([
            'impersonator_user_id' => $actor->id,
            'impersonator_staff_username' => session('staff_username') ?: $actor->email,
            'impersonator_role' => SciGradeRole::current(),
            'impersonator_display_name' => session('staff_display_name', $actor->name),
        ]);

        $targetUser = User::query()->updateOrCreate(
            ['email' => strtolower($email)],
            [
                'name' => $targetStaff->displayName() ?: trim($targetStaff->fname.' '.$targetStaff->lname),
                'password' => bcrypt(str()->random(32)),
            ],
        );

        Auth::login($targetUser, true);
        $this->staffAuth->storeInSession($targetStaff);
        session(['scigrade_role' => $targetRole]);
    }

    public function stop(): void
    {
        if (! SciGradeRole::isImpersonating()) {
            throw new InvalidArgumentException('ไม่ได้เข้าใช้งานแทนผู้อื่น');
        }

        $actorUserId = session('impersonator_user_id');
        $actorStaffUsername = session('impersonator_staff_username');
        $actorRole = session('impersonator_role', SciGradeRole::SUPER_ADMIN);

        $actor = User::query()->find($actorUserId);
        abort_unless($actor, 403, 'ไม่พบบัญชี Super Admin เดิม');

        Auth::login($actor, true);

        $staff = TblUser::query()->find($actorStaffUsername)
            ?? $this->staffAuth->findByEmail((string) $actor->email);

        if ($staff) {
            $this->staffAuth->storeInSession($staff);
        }

        session()->forget([
            'impersonator_user_id',
            'impersonator_staff_username',
            'impersonator_role',
            'impersonator_display_name',
        ]);

        session([
            'scigrade_role' => SciGradeRole::staffHasSuperPrivilege($staff?->username)
                ? SciGradeRole::SUPER_ADMIN
                : $actorRole,
        ]);
    }
}
