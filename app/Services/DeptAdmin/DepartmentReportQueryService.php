<?php

namespace App\Services\DeptAdmin;

use App\Models\GradeReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DepartmentReportQueryService
{
    public function __construct(
        private readonly DepartmentSubjectFilter $subjectFilter,
    ) {}

    /**
     * @param  array{
     *     department_ids: list<int>,
     *     department_id?: int|null,
     *     subject_code?: string|null,
     *     subject?: string|null,
     *     created_from?: string|null,
     *     created_to?: string|null,
     *     status?: int|null,
     *     term?: int|null,
     *     year?: int|null,
     *     report_status?: int|null,
     *     education_level?: string|null,
     * }  $filters
     */
    public function baseQuery(array $filters): Builder
    {
        $departmentIds = $filters['department_ids'];
        if (isset($filters['department_id']) && $filters['department_id']) {
            $departmentIds = [(int) $filters['department_id']];
        }

        $query = GradeReport::query()
            ->with(['gradeStds', 'files', 'latestDeptApprovalLog.approver', 'approvalLogs'])
            ->whereHas('gradeStds');

        $this->subjectFilter->applyDepartmentsToQuery($query, $departmentIds);

        if (! empty($filters['term'])) {
            $query->where('term', (string) $filters['term']);
        }

        if (! empty($filters['year'])) {
            $query->where('year', (string) $filters['year']);
        }

        if (! empty($filters['subject_code'])) {
            $query->where('subject_code', 'like', '%'.$filters['subject_code'].'%');
        }

        if (! empty($filters['subject'])) {
            $query->where('subject', 'like', '%'.$filters['subject'].'%');
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created', '<=', $filters['created_to']);
        }

        if (array_key_exists('status', $filters) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('approv', (int) $filters['status']);
        }

        if (array_key_exists('report_status', $filters) && $filters['report_status'] !== null && $filters['report_status'] !== '') {
            $query->where('approv', (int) $filters['report_status']);
        }

        $this->applyEducationLevel($query, $filters['education_level'] ?? null);

        return $query->orderBy('subject_code')->orderBy('grade_id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, GradeReport>
     */
    public function reportsForExport(array $filters): Collection
    {
        $reports = $this->buildExportQuery($filters)
            ->with(['gradeStds' => fn ($query) => $query->orderBy('grade_std_id')])
            ->get();

        return $this->sortReportsByLegacyOrder($reports);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildExportQuery(array $filters): Builder
    {
        $departmentIds = $filters['department_ids'];
        if (isset($filters['department_id']) && $filters['department_id']) {
            $departmentIds = [(int) $filters['department_id']];
        }

        $query = GradeReport::query()->whereHas('gradeStds');
        $this->subjectFilter->applyDepartmentsToQuery($query, $departmentIds);

        if (! empty($filters['term'])) {
            $query->where('term', (string) $filters['term']);
        }

        if (! empty($filters['year'])) {
            $query->where('year', (string) $filters['year']);
        }

        if (! empty($filters['subject_code'])) {
            $query->where('subject_code', 'like', '%'.$filters['subject_code'].'%');
        }

        if (! empty($filters['subject'])) {
            $query->where('subject', 'like', '%'.$filters['subject'].'%');
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created', '<=', $filters['created_to']);
        }

        if (array_key_exists('report_status', $filters) && $filters['report_status'] !== null && $filters['report_status'] !== '') {
            $query->where('approv', (int) $filters['report_status']);
        }

        $this->applyEducationLevel($query, $filters['education_level'] ?? null);

        return $query;
    }

    /**
     * สรุปช่วงวันที่ที่อาจารย์รายงานผลสอบ ตามสาขา/ภาค/ปี (ไม่กรองช่วงวันที่และสถานะอนุมัติ)
     *
     * @param  array{
     *     department_ids: list<int>,
     *     department_id?: int|null,
     *     term?: int|null,
     *     year?: int|null,
     *     education_level?: string|null,
     * }  $filters
     * @return array{
     *     count: int,
     *     min_date: string|null,
     *     max_date: string|null,
     *     min_date_display: string|null,
     *     max_date_display: string|null,
     *     term: int|null,
     *     year: int|null,
     *     term_label: string,
     * }
     */
    public function submissionDateSummary(array $filters): array
    {
        $summaryFilters = $filters;
        unset($summaryFilters['created_from'], $summaryFilters['created_to'], $summaryFilters['report_status'], $summaryFilters['status']);

        $statsQuery = $this->buildExportQuery($summaryFilters);

        $count = (clone $statsQuery)->count();
        $minDate = $count > 0 ? $this->normalizeDateString((clone $statsQuery)->min('created')) : null;
        $maxDate = $count > 0 ? $this->normalizeDateString((clone $statsQuery)->max('created')) : null;

        $term = isset($filters['term']) && $filters['term'] !== null && $filters['term'] !== ''
            ? (int) $filters['term']
            : null;
        $year = isset($filters['year']) && $filters['year'] !== null && $filters['year'] !== ''
            ? (int) $filters['year']
            : null;

        return [
            'count' => $count,
            'min_date' => $minDate,
            'max_date' => $maxDate,
            'min_date_display' => $minDate ? $this->formatThaiDate($minDate) : null,
            'max_date_display' => $maxDate ? $this->formatThaiDate($maxDate) : null,
            'term' => $term,
            'year' => $year,
            'term_label' => $this->termLabel($term),
        ];
    }

    private function normalizeDateString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatThaiDate(string $ymd): string
    {
        try {
            return \Illuminate\Support\Carbon::parse($ymd)->format('d/m/Y');
        } catch (\Throwable) {
            return $ymd;
        }
    }

    private function termLabel(?int $term): string
    {
        return match ($term) {
            1 => 'ภาคต้น',
            2 => 'ภาคปลาย',
            3 => 'ภาคการศึกษาพิเศษ',
            default => 'ทุกภาค',
        };
    }

    /**
     * เรียงตามเงื่อนไขเดิมใน project_old/eservice/gt_report_68.php:
     * ORDER BY MIN(subject_code) ของกลุ่ม subject_code2 ASC, subject_code ASC
     *
     * @param  Collection<int, GradeReport>  $reports
     * @return Collection<int, GradeReport>
     */
    private function sortReportsByLegacyOrder(Collection $reports): Collection
    {
        if ($reports->isEmpty()) {
            return $reports;
        }

        $minCodes = $reports
            ->groupBy(fn (GradeReport $report) => trim((string) ($report->subject_code2 ?: $report->subject_code)))
            ->map(fn (Collection $group) => strtoupper(trim((string) $group->min('subject_code'))))
            ->all();

        return $reports
            ->sortBy(function (GradeReport $report) use ($minCodes) {
                $code2 = trim((string) ($report->subject_code2 ?: $report->subject_code));
                $minCode = $minCodes[$code2] ?? strtoupper(trim((string) $report->subject_code));

                return sprintf(
                    "%s\0%s\0%010d",
                    $minCode,
                    strtoupper(trim((string) $report->subject_code)),
                    (int) $report->grade_id,
                );
            })
            ->values();
    }

    private function applyEducationLevel(Builder $query, ?string $level): void
    {
        if ($level === null || $level === '' || $level === 'all') {
            return;
        }

        $degrees = match ($level) {
            'bachelor' => [0, 3],
            'master' => [5],
            'doctoral' => [7],
            'graduate' => [5, 7],
            default => [],
        };

        if ($degrees !== []) {
            $query->whereIn('degree', $degrees);
        }
    }
}
