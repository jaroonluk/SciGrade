<?php

namespace App\Services\FacultyAdmin;

use App\Models\GradeReport;
use App\Models\TblDepartment;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FacultyReportQueryService
{
    /** @var list<int> */
    public const FACULTY_DEPARTMENT_IDS = [5, 6, 7, 8, 9, 10, 11, 12, 21, 25, 30, 31, 32, 35, 37];

    /** @var array<string, string|null> */
    private array $departmentNameCache = [];

    public function __construct(
        private readonly DepartmentSubjectFilter $subjectFilter,
    ) {}

    /**
     * @param  array{
     *     department_id?: int|null,
     *     subject_code?: string|null,
     *     subject?: string|null,
     *     created_from?: string|null,
     *     created_to?: string|null,
     *     status?: int|null,
     *     term?: int|null,
     *     year?: int|null,
     *     sort_by?: string|null,
     *     sort_dir?: string|null,
     * }  $filters
     */
    public function baseQuery(array $filters): Builder
    {
        $query = GradeReport::query()
            ->with(['gradeStds', 'files', 'latestDeptApprovalLog.approver', 'latestCentralApprovalLog.approver'])
            ->whereHas('gradeStds');

        if (! empty($filters['department_id'])) {
            $this->subjectFilter->applyToQuery($query, (int) $filters['department_id']);
        }

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

        return $this->applySort($query, $filters);
    }

    /**
     * @param  array{sort_by?: string|null, sort_dir?: string|null}  $filters
     */
    public function applySort(Builder $query, array $filters): Builder
    {
        $sortBy = $filters['sort_by'] ?? 'subject_code';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        $column = match ($sortBy) {
            'subject' => 'subject',
            'created' => 'created',
            'status' => 'approv',
            default => 'subject_code',
        };

        return $query->orderBy($column, $sortDir)->orderByDesc('grade_id');
    }

    /**
     * @return Collection<int, TblDepartment>
     */
    public function filterDepartments(): Collection
    {
        return TblDepartment::query()
            ->whereIn('department_id', self::FACULTY_DEPARTMENT_IDS)
            ->orderBy('department_name')
            ->get();
    }

    /**
     * @return Collection<int, TblDepartment>
     */
    public function allDepartments(): Collection
    {
        return $this->filterDepartments();
    }

    public function resolveDepartmentName(string $subjectCode): ?string
    {
        if (array_key_exists($subjectCode, $this->departmentNameCache)) {
            return $this->departmentNameCache[$subjectCode];
        }

        foreach ($this->filterDepartments() as $department) {
            $matches = GradeReport::query()
                ->where('subject_code', $subjectCode)
                ->where(function ($query) use ($department): void {
                    $this->subjectFilter->applyToQuery($query, (int) $department->department_id);
                })
                ->exists();

            if ($matches) {
                $this->departmentNameCache[$subjectCode] = $department->department_name;

                return $department->department_name;
            }
        }

        $this->departmentNameCache[$subjectCode] = null;

        return null;
    }
}
