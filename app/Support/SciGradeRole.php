<?php

namespace App\Support;

use App\Models\TblPrivilege;

class SciGradeRole
{
    public const INSTRUCTOR = 'instructor';

    public const DEPT_ADMIN = 'dept_admin';

    public const FACULTY_ADMIN = 'faculty_admin';

    public const SUPER_ADMIN = 'super_admin';

    public const INBOX_ALL = 'all';

    public const INBOX_BACHELOR = 'bachelor';

    public const INBOX_NON_BACHELOR = 'non_bachelor';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::INSTRUCTOR,
            self::DEPT_ADMIN,
            self::FACULTY_ADMIN,
            self::SUPER_ADMIN,
        ];
    }

    public static function current(?string $default = self::INSTRUCTOR): string
    {
        return (string) session('scigrade_role', $default);
    }

    public static function label(?string $role = null): string
    {
        return match ($role ?? self::current()) {
            self::DEPT_ADMIN => 'Admin สาขา',
            self::FACULTY_ADMIN => 'Admin กลาง',
            self::SUPER_ADMIN => 'Super Admin',
            default => 'อาจารย์',
        };
    }

    public static function isFacultyCapable(?string $role = null): bool
    {
        return in_array($role ?? self::current(), [self::FACULTY_ADMIN, self::SUPER_ADMIN], true);
    }

    public static function isDeptAdmin(?string $role = null): bool
    {
        return ($role ?? self::current()) === self::DEPT_ADMIN;
    }

    public static function isSuperAdmin(?string $role = null): bool
    {
        return ($role ?? self::current()) === self::SUPER_ADMIN;
    }

    public static function isImpersonating(): bool
    {
        return session()->has('impersonator_user_id');
    }

    /**
     * username ของผู้ใช้จริง (ไม่ใช่คนที่ถูก impersonate)
     */
    public static function realStaffUsername(): ?string
    {
        if (self::isImpersonating()) {
            $username = session('impersonator_staff_username');

            return $username ? (string) $username : null;
        }

        $username = session('staff_username');

        return $username ? (string) $username : null;
    }

    public static function staffHasSuperPrivilege(?string $username = null): bool
    {
        $username = $username ?? self::realStaffUsername();
        if (! $username) {
            return false;
        }

        $fromEnv = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('SCIGRADE_SUPER_ADMIN_USERNAMES', '')),
        )));

        if (in_array($username, $fromEnv, true)) {
            return true;
        }

        return TblPrivilege::query()
            ->where('system_id', TblPrivilege::SYSTEM_GRADE_REPORT)
            ->where('username', $username)
            ->where('level', TblPrivilege::LEVEL_SUPER)
            ->exists();
    }

    /**
     * กำหนดสิทธิ์ Super Admin ได้เฉพาะตอนเข้าด้วยบทบาท Super Admin เท่านั้น
     * (Admin กลางทำไม่ได้แม้บัญชีจะมีสิทธิ์ Super)
     */
    public static function canAssignSuperPrivilege(): bool
    {
        return self::isSuperAdmin()
            && self::staffHasSuperPrivilege()
            && ! self::isImpersonating();
    }

    public static function canImpersonate(): bool
    {
        return self::isSuperAdmin()
            && self::staffHasSuperPrivilege()
            && ! self::isImpersonating();
    }

    /**
     * @return list<string>
     */
    public static function selectableRolesForCurrentUser(): array
    {
        $roles = [self::INSTRUCTOR, self::DEPT_ADMIN, self::FACULTY_ADMIN];

        if (self::staffHasSuperPrivilege() && ! self::isImpersonating()) {
            $roles[] = self::SUPER_ADMIN;
        }

        return $roles;
    }

    public static function inboxScopeForPrivilegeLevel(?int $level): string
    {
        return match ($level) {
            TblPrivilege::LEVEL_SERVICE_BACHELOR => self::INBOX_BACHELOR,
            TblPrivilege::LEVEL_SERVICE_GRADUATE => self::INBOX_NON_BACHELOR,
            default => self::INBOX_ALL,
        };
    }

    public static function staffPrivilegeLevel(?string $username = null): ?int
    {
        $username = $username ?? (string) session('staff_username', '');
        if ($username === '') {
            return null;
        }

        $level = TblPrivilege::query()
            ->where('system_id', TblPrivilege::SYSTEM_GRADE_REPORT)
            ->where('username', $username)
            ->value('level');

        return $level === null ? null : (int) $level;
    }

    /**
     * ขอบเขตรายการรับเอกสารจากหน่วยงาน ตามสิทธิ์งานบริการ
     */
    public static function deptSubmissionInboxScope(?string $username = null): string
    {
        if (self::isSuperAdmin()) {
            return self::INBOX_ALL;
        }

        return self::inboxScopeForPrivilegeLevel(self::staffPrivilegeLevel($username));
    }
}
