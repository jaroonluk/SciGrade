<?php

namespace App\Enums;

enum GradeApprovalStatus: int
{
    case Saved = 0;
    case DepartmentApproved = 1;
    case CentralApproved = 2;
    case FacultyChecked = 3;
    /** นำเข้าที่ประชุมสาขา — ยังไม่ผ่านมติที่ประชุม */
    case DepartmentMeetingQueued = 4;
    case DepartmentRejected = -1;

    public function label(): string
    {
        return match ($this) {
            self::Saved => 'บันทึกแล้ว / รออนุมัติ',
            self::DepartmentMeetingQueued => 'นำเข้าที่ประชุมสาขา',
            self::DepartmentApproved => 'ผ่านที่ประชุมสาขา',
            self::FacultyChecked => 'ตรวจแล้ว — รอกรรมการคณะฯ',
            self::CentralApproved => 'ผ่านที่ประชุมกรรมการคณะฯ',
            self::DepartmentRejected => 'ยังไม่ผ่านการรับรองผลสอบ',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Saved => 'บันทึกแล้ว',
            self::DepartmentMeetingQueued => 'นำเข้าที่ประชุมสาขา',
            self::DepartmentApproved => 'ผ่านที่ประชุมสาขา',
            self::FacultyChecked => 'ตรวจแล้ว',
            self::CentralApproved => 'ผ่านที่ประชุมกรรมการคณะฯ',
            self::DepartmentRejected => 'ส่งกลับแก้ไข',
        };
    }

    /**
     * สถานะที่ Admin กลางตรวจ/อนุมัติได้ (ต้องผ่านที่ประชุมสาขาแล้ว)
     *
     * @return list<int>
     */
    public static function facultyReviewableValues(): array
    {
        return [
            self::DepartmentApproved->value,
            self::FacultyChecked->value,
        ];
    }

    /**
     * สถานะฝั่งสาขาก่อนผ่านมติที่ประชุม (ยังส่งกลับแก้ไขได้)
     *
     * @return list<int>
     */
    public static function departmentPreMeetingValues(): array
    {
        return [
            self::Saved->value,
            self::DepartmentMeetingQueued->value,
        ];
    }

    public static function tryFromValue(int $value): self
    {
        return self::tryFrom($value) ?? self::Saved;
    }
}
