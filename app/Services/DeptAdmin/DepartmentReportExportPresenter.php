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
     * รวมรายงานรหัสวิชาเดียวกันไว้กลุ่มเดียว เพื่อแสดงหลาย section ในตารางเดียว
     * เรียงลำดับวิชาตามเงื่อนไขเดิม gt_report_68.php:
     *   ORDER BY MIN(subject_code) ของ subject_code2, แล้วตาม subject_code
     * เรียง section ในตารางเป็น 1, 2, 3, ... ตามลำดับตัวเลข
     *
     * @param  Collection<int, GradeReport>  $reports
     * @return Collection<int, object>
     */
    public function groupBySubjectCode(Collection $reports): Collection
    {
        if ($reports->isEmpty()) {
            return collect();
        }

        // ตาม buildOptimizedSQL ใน gt_report_68.php:
        // ORDER BY MIN(subject_code) ของ subject_code2 ASC, subject_code ASC
        // (ใช้ sortBy แบบ key เดียว — sortBy([...]) ใน Laravel เป็น comparator 2 args)
        $minCodes = $reports
            ->groupBy(fn (GradeReport $report) => trim((string) ($report->subject_code2 ?: $report->subject_code)))
            ->map(fn (Collection $group) => strtoupper(trim((string) $group->min('subject_code'))))
            ->all();

        return $reports
            ->groupBy(fn (GradeReport $report) => strtoupper(trim((string) $report->subject_code)))
            ->map(function (Collection $group) {
                $primary = $group->sortBy('grade_id')->first();
                $subjectCode2 = trim((string) ($primary->subject_code2 ?: $primary->subject_code));

                $sections = $group
                    ->flatMap(fn (GradeReport $report) => $report->gradeStds)
                    ->sortBy(fn (GradeStd $std) => sprintf(
                        '%05d|%s|%02d|%010d',
                        (int) $std->sec,
                        strtoupper((string) $std->fac),
                        (int) $std->type_course,
                        (int) ($std->grade_std_id ?? 0),
                    ))
                    ->values();

                $teachers = $group
                    ->map(fn (GradeReport $report) => trim((string) $report->teacher))
                    ->filter()
                    ->unique()
                    ->values();

                $reasons = $group
                    ->map(fn (GradeReport $report) => trim((string) $report->reason))
                    ->filter(fn (string $reason) => $reason !== '' && $reason !== '-')
                    ->unique()
                    ->values();

                $scoreSource = $this->preferredScoreSource($group);

                return (object) [
                    'subject_code' => strtoupper(trim((string) $primary->subject_code)),
                    'subject_code2' => $subjectCode2,
                    'subject' => $this->preferredSubjectName($group),
                    'teacher' => $teachers->implode(', '),
                    'reason' => $reasons->isEmpty() ? '-' : $reasons->implode(' / '),
                    'mean' => $this->preferredMetric($group, 'mean'),
                    'sd' => $this->preferredMetric($group, 'sd'),
                    'score_a' => $scoreSource->score_a,
                    'score_bb' => $scoreSource->score_bb,
                    'score_b' => $scoreSource->score_b,
                    'score_cc' => $scoreSource->score_cc,
                    'score_c' => $scoreSource->score_c,
                    'score_dd' => $scoreSource->score_dd,
                    'score_d' => $scoreSource->score_d,
                    'score_f' => $scoreSource->score_f,
                    'sections' => $sections,
                    'reports' => $group->values(),
                ];
            })
            ->sortBy(function (object $course) use ($minCodes) {
                $minCode = $minCodes[$course->subject_code2] ?? $course->subject_code;

                return strtoupper((string) $minCode)."\0".$course->subject_code;
            })
            ->values();
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
            'total_std' => (int) $sections->sum(fn (GradeStd $std) => (int) $std->total_std),
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

    /**
     * @param  Collection<int, GradeReport>  $group
     */
    private function preferredSubjectName(Collection $group): string
    {
        $names = $group
            ->map(fn (GradeReport $report) => trim((string) $report->subject))
            ->filter()
            ->unique()
            ->values();

        $real = $names->first(fn (string $name) => ! str_starts_with($name, '[TEST]'));

        return $real ?? ($names->first() ?? '');
    }

    /**
     * @param  Collection<int, GradeReport>  $group
     */
    private function preferredScoreSource(Collection $group): GradeReport
    {
        return $group->first(function (GradeReport $report) {
            foreach (['score_a', 'score_bb', 'score_b', 'score_cc', 'score_c', 'score_dd', 'score_d', 'score_f'] as $key) {
                if (trim((string) $report->{$key}) !== '') {
                    return true;
                }
            }

            return false;
        }) ?? $group->sortBy('grade_id')->first();
    }

    /**
     * @param  Collection<int, GradeReport>  $group
     */
    private function preferredMetric(Collection $group, string $field): ?string
    {
        $value = $group
            ->map(fn (GradeReport $report) => $report->{$field})
            ->first(fn ($v) => $v !== null && $v !== '' && (float) $v != 0.0);

        return $value !== null ? (string) $value : null;
    }
}
