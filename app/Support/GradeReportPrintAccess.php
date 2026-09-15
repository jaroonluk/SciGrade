<?php

namespace App\Support;

use App\Models\GradeReport;

/**
 * สิทธิ์เปิดหน้าพิมพ์ใบขวาง
 *
 * อาจารย์เจ้าของรายงานพิมพ์ได้เสมอ
 * อาจารย์ที่กรอกเพิ่ม Section ในรายงานวิชาร่วม (ฉบับร่าง) พิมพ์ได้เหมือนตอนอัปโหลดไฟล์
 * สาขา / คณะ พิมพ์ได้ตามบทบาท
 */
class GradeReportPrintAccess
{
    public static function allows(?string $role, ?string $staffUsername, GradeReport $report): bool
    {
        if (($role ?: SciGradeRole::INSTRUCTOR) !== SciGradeRole::INSTRUCTOR) {
            return true;
        }

        $staff = trim((string) $staffUsername);
        if ($staff === '') {
            return false;
        }

        if ($staff === trim((string) $report->username)) {
            return true;
        }

        return (int) $report->approv <= 0 && ! $report->awaitingDeptResubmit();
    }
}
