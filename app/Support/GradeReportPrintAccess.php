<?php

namespace App\Support;

use App\Models\GradeReport;

/**
 * สิทธิ์เปิดหน้าพิมพ์ใบขวาง
 *
 * อาจารย์ไม่พิมพ์/ดาวน์โหลดใบขวางแล้ว — สาขาจัดทำใบขวางรวม
 * สาขา / คณะ / Super Admin พิมพ์ได้ตามบทบาท
 */
class GradeReportPrintAccess
{
    public static function allows(?string $role, ?string $staffUsername, GradeReport $report): bool
    {
        return ($role ?: SciGradeRole::INSTRUCTOR) !== SciGradeRole::INSTRUCTOR;
    }
}
