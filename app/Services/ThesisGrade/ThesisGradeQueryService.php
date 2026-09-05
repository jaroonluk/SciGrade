<?php

namespace App\Services\ThesisGrade;

use App\Models\ThesisGrade;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use Illuminate\Database\Eloquent\Builder;

class ThesisGradeQueryService
{
    public function __construct(
        private readonly DepartmentSubjectFilter $subjectFilter,
    ) {}

    /**
     * @param  list<int>  $departmentIds
     * @param  array{term?: int, year?: int, status?: string, department_id?: int, subject_code?: string, q?: string}  $filters
     */
    public function deptQuery(array $departmentIds, array $filters): Builder
    {
        $query = ThesisGrade::query()
            ->with(['students', 'files'])
            ->where('status', '!=', ThesisGrade::STATUS_DRAFT)
            ->orderByDesc('submitted_at')
            ->orderByDesc('thesis_grade_id');

        $scopedIds = $departmentIds;
        if (! empty($filters['department_id'])) {
            $scopedIds = in_array((int) $filters['department_id'], $departmentIds, true)
                ? [(int) $filters['department_id']]
                : [];
        }

        $this->subjectFilter->applyDepartmentsToQuery($query, $scopedIds);

        if (! empty($filters['term'])) {
            $query->where('term', (int) $filters['term']);
        }

        if (! empty($filters['year'])) {
            $query->where('year', (int) $filters['year']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $code = trim((string) ($filters['subject_code'] ?? ''));
        if ($code !== '') {
            $query->where('subject_code', 'like', '%'.$code.'%');
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $query->where(function (Builder $inner) use ($q): void {
                $inner->where('subject_code', 'like', '%'.$q.'%')
                    ->orWhere('subject', 'like', '%'.$q.'%')
                    ->orWhere('teacher', 'like', '%'.$q.'%')
                    ->orWhere('username', 'like', '%'.$q.'%');
            });
        }

        return $query;
    }

    /**
     * @param  array{term?: int, year?: int, status?: string, department_id?: int, subject_code?: string, q?: string}  $filters
     */
    public function facultyQuery(array $filters): Builder
    {
        $query = ThesisGrade::query()
            ->with(['students', 'files'])
            ->where('status', '!=', ThesisGrade::STATUS_DRAFT)
            ->orderByDesc('submitted_at')
            ->orderByDesc('thesis_grade_id');

        if (! empty($filters['department_id'])) {
            $this->subjectFilter->applyToQuery($query, (int) $filters['department_id']);
        }

        if (! empty($filters['term'])) {
            $query->where('term', (int) $filters['term']);
        }

        if (! empty($filters['year'])) {
            $query->where('year', (int) $filters['year']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $code = trim((string) ($filters['subject_code'] ?? ''));
        if ($code !== '') {
            $query->where('subject_code', 'like', '%'.$code.'%');
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $query->where(function (Builder $inner) use ($q): void {
                $inner->where('subject_code', 'like', '%'.$q.'%')
                    ->orWhere('subject', 'like', '%'.$q.'%')
                    ->orWhere('teacher', 'like', '%'.$q.'%')
                    ->orWhere('username', 'like', '%'.$q.'%');
            });
        }

        return $query;
    }
}
