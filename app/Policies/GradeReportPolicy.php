<?php

namespace App\Policies;

use App\Models\GradeReport;
use App\Models\User;
use App\Services\DeptAdmin\DepartmentAccessService;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use App\Services\StaffAuthService;
use App\Support\SciGradeRole;

class GradeReportPolicy
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly DepartmentAccessService $departmentAccess,
        private readonly DepartmentSubjectFilter $subjectFilter,
    ) {}

    public function reviewFaculty(User $user, GradeReport $report): bool
    {
        return SciGradeRole::isFacultyCapable()
            && $this->staffAuth->findByEmail($user->email) !== null;
    }

    public function reviewDept(User $user, GradeReport $report): bool
    {
        if (! SciGradeRole::isDeptAdmin()) {
            return false;
        }

        $staff = $this->staffAuth->findByEmail($user->email);
        if (! $staff) {
            return false;
        }

        return $this->reportInAllowedDepartments($staff, $report, requireDepartmentInstructor: true);
    }

    /**
     * เปลี่ยนสถานะบนหน้า reg-grade-status — ตามรหัสวิชาของสาขา
     * (ไม่บังคับว่าผู้กรอกต้องเป็นอาจารย์ในสาขา เพราะหน้านี้อ้างอิงจาก REG)
     */
    public function manageRegGradeStatus(User $user, GradeReport $report): bool
    {
        if (! SciGradeRole::isDeptAdmin()) {
            return false;
        }

        $staff = $this->staffAuth->findByEmail($user->email);
        if (! $staff) {
            return false;
        }

        return $this->reportInAllowedDepartments($staff, $report, requireDepartmentInstructor: false);
    }

    private function reportInAllowedDepartments($staff, GradeReport $report, bool $requireDepartmentInstructor = true): bool
    {
        $allowedIds = $this->departmentAccess->allowedDepartmentIds($staff);
        if ($allowedIds === []) {
            return false;
        }

        if (! $requireDepartmentInstructor) {
            return GradeReport::query()
                ->whereKey($report->grade_id)
                ->where(function ($query) use ($allowedIds): void {
                    $this->subjectFilter->applyDepartmentsToQuery($query, $allowedIds);
                })
                ->exists();
        }

        foreach ($allowedIds as $departmentId) {
            $matches = GradeReport::query()
                ->whereKey($report->grade_id)
                ->where(function ($query) use ($departmentId): void {
                    $this->subjectFilter->applyToQuery($query, $departmentId);
                    if (! $this->subjectFilter->isEducationServicesDepartment($departmentId)) {
                        $this->subjectFilter->applyFilledByDepartmentInstructors($query, $departmentId);
                    }
                })
                ->exists();

            if ($matches) {
                return true;
            }
        }

        return false;
    }
}
