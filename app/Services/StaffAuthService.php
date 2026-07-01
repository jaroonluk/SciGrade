<?php

namespace App\Services;

use App\Models\TblUser;

class StaffAuthService
{
    public const ACCESS_DENIED_MESSAGE = 'ระบบนี้พัฒนาสำหรับบุคลากร คณะวิทยาศาสตร์ หากไม่สามารถเข้าสู่ระบบได้ กรุณาติดต่อผู้ดูแลระบบ';

    public function findByEmail(string $email): ?TblUser
    {
        return TblUser::findByEmail($email);
    }

    public function storeInSession(TblUser $staff): void
    {
        session([
            'staff_username' => $staff->username,
            'staff_display_name' => $staff->displayName(),
            'staff_teacher_name' => $staff->teacherName(),
            'staff_department_id' => (int) $staff->department_id,
        ]);
    }

    public function clearSession(): void
    {
        session()->forget(['staff_username', 'staff_display_name', 'staff_teacher_name', 'staff_department_id']);
    }

    public function displayNameFor(?string $email, ?string $fallback = null): string
    {
        if ($name = session('staff_display_name')) {
            return $name;
        }

        if ($email && ($staff = $this->findByEmail($email))) {
            $this->storeInSession($staff);

            return $staff->displayName();
        }

        return $fallback ?? '';
    }

    public function teacherNameFor(?string $email, ?string $fallback = null): string
    {
        if ($name = session('staff_teacher_name')) {
            return $name;
        }

        if ($email && ($staff = $this->findByEmail($email))) {
            $this->storeInSession($staff);

            return $staff->teacherName();
        }

        return $fallback ?? '';
    }
}
