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
     * ชื่อหน่วยงานที่ตัดออกจากรายการเลือกพิมพ์รายงาน (จับแบบมีคำนี้ในชื่อ)
     *
     * @var list<string>
     */
    public const EXCLUDED_NAME_NEEDLES = [
        'วัสดุศาสตร์และนาโนเทคโนโลยี',
        'วิสดุศาสตร์และนาโนเทคโนโลยี',
        'กองบริหารงานคณะ',
        'วิทยาการข้อมูล',
        'ปัญญาประดิษฐ์',
        'นิติวิทยาศาสตร์',
        'วิทยาศาสตร์ชีวภาพ',
    ];

    /**
     * รหัสหน่วยงานที่ตัดออกแน่นอน (สำรองเมื่อชื่อใน DB ไม่ตรงข้อความ)
     * 35 = หลักสูตรวัสดุศาสตร์และนาโนเทคโนโลยี (รหัสวิชา SC027/SC028)
     *
     * @var list<int>
     */
    public const EXCLUDED_DEPARTMENT_IDS = [
        35,
    ];

    public static function isExcludedFromSelect(?string $departmentName, int|string|null $departmentId = null): bool
    {
        if ($departmentId !== null && $departmentId !== '') {
            if (in_array((int) $departmentId, self::EXCLUDED_DEPARTMENT_IDS, true)) {
                return true;
            }
        }

        if (! is_string($departmentName) || trim($departmentName) === '') {
            return false;
        }

        $normalized = preg_replace('/\s+/u', '', $departmentName) ?? $departmentName;

        foreach (self::EXCLUDED_NAME_NEEDLES as $needle) {
            $needleNorm = preg_replace('/\s+/u', '', $needle) ?? $needle;
            if ($needleNorm !== '' && str_contains($normalized, $needleNorm)) {
                return true;
            }
        }

        // จับชื่อที่เว้นวรรค/สะกดแยกคำ เช่น "วัสดุศาสตร์ และ นาโนเทคโนโลยี"
        $hasMaterials = str_contains($normalized, 'วัสดุศาสตร์')
            || str_contains($normalized, 'วิสดุศาสตร์');
        $hasNano = str_contains($normalized, 'นาโนเทคโนโลยี')
            || str_contains($normalized, 'นาโนฯ');

        return $hasMaterials && $hasNano;
    }

    /**
     * @return Collection<int, TblDepartment>
     */
    public static function selectable(): Collection
    {
        return TblDepartment::query()
            ->whereIn('department_id', TblProgramQa::ALLOWED_DEPARTMENT_IDS)
            ->whereNotIn('department_id', self::EXCLUDED_DEPARTMENT_IDS)
            ->orderBy('department_name')
            ->get()
            ->reject(fn (TblDepartment $dept) => self::isExcludedFromSelect(
                (string) $dept->department_name,
                $dept->department_id,
            ))
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
