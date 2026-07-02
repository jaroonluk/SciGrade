<?php

namespace App\Enums;

enum GradeApprovalStatus: int
{
    case Saved = 0;
    case DepartmentApproved = 1;
    case CentralApproved = 2;
    case DepartmentRejected = -1;

    public function label(): string
    {
        return match ($this) {
            self::Saved => 'บันทึกแล้ว / รออนุมัติ',
            self::DepartmentApproved => 'สาขาอนุมัติ',
            self::CentralApproved => 'คณะอนุมัติ',
            self::DepartmentRejected => 'ยังไม่ผ่านการรับรองผลสอบ',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Saved => 'บันทึกแล้ว',
            self::DepartmentApproved => 'สาขาอนุมัติ',
            self::CentralApproved => 'คณะอนุมัติ',
            self::DepartmentRejected => 'ส่งกลับแก้ไข',
        };
    }

    public static function tryFromValue(int $value): self
    {
        return self::tryFrom($value) ?? self::Saved;
    }
}
