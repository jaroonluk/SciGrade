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

    /**
     * ตรวจ/อนุมัติรายงานของสาขา — ตามรหัสวิชาที่สาขาดูแล
     * (ไม่บังคับว่าอาจารย์ผู้กรอกต้องสังกัดสาขาเดียวกัน)
     */
    public function reviewDept(User $user, GradeReport $report): bool
    {
        if (! SciGradeRole::isDeptAdmin()) {
            return false;
        }

        $staff = $this->staffAuth->findByEmail($user->email);
        if (! $staff) {
            return false;
        }

        return $this->reportInAllowedDepartments($staff, $report);
    }

    /**
     * เปลี่ยนสถานะบนหน้า reg-grade-status — ตามรหัสวิชาของสาขา
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

        return $this->reportInAllowedDepartments($staff, $report);
    }

    private function reportInAllowedDepartments($staff, GradeReport $report): bool
    {
        $allowedIds = $this->departmentAccess->allowedDepartmentIds($staff);
        if ($allowedIds === []) {
            return false;
        }

        return GradeReport::query()
            ->whereKey($report->grade_id)
            ->where(function ($query) use ($allowedIds): void {
                $this->subjectFilter->applyDepartmentsToQuery($query, $allowedIds);
            })
            ->exists();
    }
}
