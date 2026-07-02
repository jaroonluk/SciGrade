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
            ->with(['gradeStds', 'files', 'latestDeptApprovalLog.approver'])
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

        if (! empty($filters['created_from']) && ! empty($filters['created_to'])) {
            $query->whereBetween('created', [$filters['created_from'], $filters['created_to']]);
        } elseif (! empty($filters['created_from'])) {
            $query->whereDate('created', '>=', $filters['created_from']);
        } elseif (! empty($filters['created_to'])) {
            $query->whereDate('created', '<=', $filters['created_to']);
        }

        if (array_key_exists('report_status', $filters) && $filters['report_status'] !== null && $filters['report_status'] !== '') {
            $query->where('approv', (int) $filters['report_status']);
        }

        $this->applyEducationLevel($query, $filters['education_level'] ?? null);

        return $query;
    }

    /**
     * @param  Collection<int, GradeReport>  $reports
     * @return Collection<int, GradeReport>
     */
    private function sortReportsByLegacyOrder(Collection $reports): Collection
    {
        if ($reports->isEmpty()) {
            return $reports;
        }

        $minCodes = $reports
            ->groupBy(fn (GradeReport $report) => $report->subject_code2 ?: $report->subject_code)
            ->map(fn (Collection $group) => $group->min('subject_code'));

        return $reports
            ->sortBy([
                fn (GradeReport $report) => $minCodes[$report->subject_code2 ?: $report->subject_code] ?? $report->subject_code,
                fn (GradeReport $report) => $report->subject_code,
            ])
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
