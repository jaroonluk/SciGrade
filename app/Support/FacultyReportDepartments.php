<?php

namespace App\Support;

use App\Models\TblDepartment;
use App\Models\TblProgramQa;
use Illuminate\Support\Collection;

/**
 * หน่วยงานที่แสดงในหน้ารายงานผลการสอบไล่ (Admin กลาง)
 */
final class FacultyReportDepartments
{
    /**
     * ชื่อหน่วยงานที่ตัดออกจากรายการเลือกพิมพ์รายงาน
     *
     * @var list<string>
     */
    public const EXCLUDED_NAME_NEEDLES = [
        'วิทยาการข้อมูล',
        'ปัญญาประดิษฐ์',
        'บัฐฐาประดิษฐ์',
        'นิติวิทยาศาสตร์',
        'วัสดุศาสตร์และนาโนเทคโนโลยี',
        'วิทยาศาสตร์ชีวภาพ',
        'กองบริหารงานคณะ',
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
            ->map(fn (TblDepartment $dept) => (int) $dept->department_id)
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();
    }
}
