<?php

namespace App\Services\DeptAdmin;

use App\Models\GradeReport;
use App\Models\GradeStd;
use Illuminate\Support\Collection;

class DepartmentReportExportPresenter
{
    public function typeCourseLabel(?int $type): string
    {
        return match ((int) $type) {
            2 => '(โครงการพิเศษ)',
            3 => '(ปริญญาตรี ก้าวหน้า)',
            4 => '(นานาชาติ)',
            5 => '(นานาชาติ โครงการพิเศษ)',
            default => '',
        };
    }

    public function scoreDisplay(?string $value): string
    {
        return ($value !== null && trim($value) !== '') ? $value : '-';
    }

    public function formatPercent(int $count, int $total): string
    {
        return $total > 0
            ? number_format(($count * 100) / $total, 2, '.', ',')
            : '-';
    }

    public function formatSectionLabel(GradeStd $std): string
    {
        $label = trim($std->sec.' '.strtoupper((string) $std->fac));

        $typeLabel = $this->typeCourseLabel($std->type_course);

        return $typeLabel !== '' ? trim($label.' '.$typeLabel) : $label;
    }

    public function formatMean(?string $value): string
    {
        if ($value === null || $value === '' || (float) $value == 0.0) {
            return '-';
        }

        return number_format((float) $value, 2, '.', ',');
    }

    public function formatSd(?string $value): string
    {
        return $this->formatMean($value);
    }

    /**
     * @return Collection<int, GradeStd>
     */
    public function sortedSections(GradeReport $report): Collection
    {
        return $report->gradeStds->sortBy(fn (GradeStd $row) => (int) $row->sec)->values();
    }

    /**
     * @param  Collection<int, GradeStd>  $sections
     * @return array{
     *     total_std: int,
     *     num_a: int,
     *     num_bb: int,
     *     num_b: int,
     *     num_cc: int,
     *     num_c: int,
     *     num_dd: int,
     *     num_d: int,
     *     num_f: int,
     *     num_i: int,
     *     num_s: int,
     *     num_v: int,
     *     num_w: int,
     * }
     */
    public function summaryTotals(Collection $sections): array
    {
        return [
            'total_std' => (int) $sections->sum('total_std'),
            'num_a' => (int) $sections->sum('num_a'),
            'num_bb' => (int) $sections->sum('num_bb'),
            'num_b' => (int) $sections->sum('num_b'),
            'num_cc' => (int) $sections->sum('num_cc'),
            'num_c' => (int) $sections->sum('num_c'),
            'num_dd' => (int) $sections->sum('num_dd'),
            'num_d' => (int) $sections->sum('num_d'),
            'num_f' => (int) $sections->sum('num_f'),
            'num_i' => (int) $sections->sum('num_i'),
            'num_s' => (int) $sections->sum('num_s'),
            'num_v' => (int) $sections->sum('num_v'),
            'num_w' => (int) $sections->sum('num_w'),
        ];
    }
}
