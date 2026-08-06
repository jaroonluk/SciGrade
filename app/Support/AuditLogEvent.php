<?php

namespace App\Support;

class AuditLogEvent
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'auth.login' => 'เข้าสู่ระบบ',
            'auth.logout' => 'ออกจากระบบ',
            'auth.denied' => 'ถูกปฏิเสธการเข้าสู่ระบบ',
            'role.switch' => 'สลับบทบาท',
            'impersonation.start' => 'เริ่มเข้าใช้งานแทน',
            'impersonation.stop' => 'สิ้นสุดการเข้าใช้งานแทน',
            'grade_report.create' => 'สร้างรายงานผล',
            'grade_report.update' => 'แก้ไขรายงานผล',
            'grade_report.delete' => 'ลบรายงานผล',
            'grade_report.submit_corrections' => 'ส่งการแก้ไขรายงานผล',
            'grade_report.review' => 'ตรวจ/อนุมัติรายงานผล',
            'grade_report_file.upload' => 'อัปโหลดไฟล์แนบ',
            'grade_report_file.view' => 'ดูไฟล์แนบ',
            'grade_report_file.delete' => 'ลบไฟล์แนบ',
            'grade_report_file.download_zip' => 'ดาวน์โหลดไฟล์ ZIP',
            'privilege.create' => 'เพิ่มสิทธิ์ผู้ใช้',
            'privilege.update' => 'แก้ไขสิทธิ์ผู้ใช้',
            'privilege.delete' => 'ลบสิทธิ์ผู้ใช้',
            'dept_submission.receive' => 'รับเอกสารจากสาขา',
        ];
    }

    public static function label(string $event): string
    {
        return self::options()[$event] ?? $event;
    }
}
