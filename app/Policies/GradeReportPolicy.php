<?php

namespace App\Policies;

use App\Models\GradeReport;
use App\Models\User;
use App\Services\DeptAdmin\DepartmentAccessService;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use App\Services\StaffAuthService;

class GradeReportPolicy
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly DepartmentAccessService $departmentAccess,
        private readonly DepartmentSubjectFilter $subjectFilter,
    ) {}

    public function reviewFaculty(User $user, GradeReport $report): bool
    {
        return session('scigrade_role') === 'faculty_admin'
            && $this->staffAuth->findByEmail($user->email) !== null;
    }

    public function reviewDept(User $user, GradeReport $report): bool
    {
        if (session('scigrade_role') !== 'dept_admin') {
            return false;
        }

        $staff = $this->staffAuth->findByEmail($user->email);
        if (! $staff) {
            return false;
        }

        return $this->reportInAllowedDepartments($staff, $report);
    }

    private function reportInAllowedDepartments($staff, GradeReport $report): bool
    {
        $allowedIds = $this->departmentAccess->allowedDepartmentIds($staff);

        foreach ($allowedIds as $departmentId) {
            $matches = GradeReport::query()
                ->whereKey($report->grade_id)
                ->where(function ($query) use ($departmentId): void {
                    $this->subjectFilter->applyToQuery($query, $departmentId);
                })
                ->exists();

            if ($matches) {
                return true;
            }
        }

        return false;
    }
}
