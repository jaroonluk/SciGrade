<?php

namespace App\Support;

use App\Models\GradeReport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * เลือกแถวกลุ่มเรียนสำหรับหน้าพิมพ์ใบขวาง
 *
 * - ไม่มี ?sections (หน้ารายการของฉัน / สาขา) หรือ ?scope=all — ทุก Sec. ที่กรอกแล้ว
 * - มี ?sections= (wizard กรอกเพิ่ม) — เฉพาะกลุ่มในรอบนั้น
 */
class GradeReportPrintStds
{
    /**
     * @return Collection<int, \App\Models\GradeStd>
     */
    public static function forRequest(Request $request, GradeReport $gradeReport): Collection
    {
        $stds = $gradeReport->gradeStds->sortBy(fn ($row) => (int) $row->sec)->values();

        if ($request->input('scope') === 'all' || ! $request->exists('sections')) {
            return $stds;
        }

        $wanted = [];
        $raw = $request->input('sections', []);
        if (! is_array($raw)) {
            $raw = preg_split('/[,\s]+/', (string) $raw) ?: [];
        }
        foreach ($raw as $value) {
            $n = (int) $value;
            if ($n > 0) {
                $wanted[$n] = $n;
            }
        }

        return $stds
            ->filter(fn ($row) => isset($wanted[(int) $row->sec]))
            ->values();
    }
}
