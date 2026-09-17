<?php

namespace App\Support;

use App\Models\TblDepartment;
use App\Models\TblProgramQa;
use Illuminate\Support\Collection;

/**
 * หน่วยงานที่แสดงในหน้ารายงานผลการสอบไล่ (Admin กลาง /grade-reports/reports)
 */
final class FacultyReportDepartments
{
    /**
     * ชื่อหน่วยงานที่ตัดออกจากรายการเลือกพิมพ์รายงาน
     *
     * @var list<string>
     */
    public const EXCLUDED_NAME_NEEDLES = [
        'วัสดุศาสตร์และนาโนเทคโนโลยี',
        'วิสดุศาสตร์และนาโนเทคโนโลยี', // ชื่อสะกดผิดที่อาจมีในฐานข้อมูล
        'กองบริหารงานคณะ',
        'วิทยาการข้อมูล',
        'ปัญญาประดิษฐ์',
        'นิติวิทยาศาสตร์',
        'วิทยาศาสตร์ชีวภาพ',
    ];

    public static function isExcludedFromSelect(?string $departmentName): bool
    {
        if (! is_string($departmentName) || trim($departmentName) === '') {
            return false;
        }

        foreach (self::EXCLUDED_NAME_NEEDLES as $needle) {
            if (str_contains($departmentName, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, TblDepartment>
     */
    public static function selectable(): Collection
    {
        return TblDepartment::query()
            ->whereIn('department_id', TblProgramQa::ALLOWED_DEPARTMENT_IDS)
            ->orderBy('department_name')
            ->get()
            ->reject(fn (TblDepartment $dept) => self::isExcludedFromSelect((string) $dept->department_name))
            ->values();
    }

    /**
     * @return list<int>
     */
    public static function selectableIds(): array
    {
        return self::selectable()
            ->pluck('department_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
