<?php

namespace App\Policies;

use App\Models\ThesisGrade;
use App\Models\User;
use App\Services\DeptAdmin\DepartmentAccessService;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use App\Services\StaffAuthService;
use App\Support\SciGradeRole;

class ThesisGradePolicy
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly DepartmentAccessService $departmentAccess,
        private readonly DepartmentSubjectFilter $subjectFilter,
    ) {}

    public function view(User $user, ThesisGrade $report): bool
    {
        return $this->owns($user, $report) || $this->reviewDept($user, $report);
    }

    public function update(User $user, ThesisGrade $report): bool
    {
        return $this->owns($user, $report) && $report->isEditable();
    }

    public function delete(User $user, ThesisGrade $report): bool
    {
        return $this->owns($user, $report) && $report->isDeletable();
    }

    public function submit(User $user, ThesisGrade $report): bool
    {
        return $this->owns($user, $report) && $report->isEditable();
    }

    public function reviewDept(User $user, ThesisGrade $report): bool
    {
        if (! SciGradeRole::isDeptAdmin()) {
            return false;
        }

        $staff = $this->staffAuth->findByEmail($user->email);
        if (! $staff) {
            return false;
        }

        $allowedIds = $this->departmentAccess->allowedDepartmentIds($staff);
        if ($allowedIds === []) {
            return false;
        }

        return ThesisGrade::query()
            ->whereKey($report->thesis_grade_id)
            ->where('status', '!=', ThesisGrade::STATUS_DRAFT)
            ->where(function ($query) use ($allowedIds): void {
                $this->subjectFilter->applyDepartmentsToQuery($query, $allowedIds);
            })
            ->exists();
    }

    private function owns(User $user, ThesisGrade $report): bool
    {
        $username = session('staff_username');
        if (! $username) {
            $staff = $this->staffAuth->findByEmail($user->email);
            $username = $staff?->username;
        }

        return $username !== null && $username !== '' && $report->username === $username;
    }
}
