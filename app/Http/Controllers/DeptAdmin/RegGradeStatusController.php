<?php

namespace App\Http\Controllers\DeptAdmin;

use App\Http\Controllers\Controller;
use App\Models\GradeReport;
use App\Services\DeptAdmin\DepartmentAccessService;
use App\Services\DeptAdmin\GradeReportApprovalService;
use App\Services\FacultyAdmin\RegGradeDepartmentService;
use App\Services\StaffAuthService;
use App\Support\AcademicTerm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class RegGradeStatusController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly DepartmentAccessService $departmentAccess,
        private readonly RegGradeDepartmentService $regService,
        private readonly GradeReportApprovalService $approvalService,
    ) {}

    public function index(Request $request): View
    {
        $staff = $this->requireStaff();
        $allowedIds = $this->departmentAccess->allowedDepartmentIds($staff);
        $departments = $this->departmentAccess->allowedDepartments($staff);

        $term = (int) $request->input('term', AcademicTerm::defaultTerm());
        $year = (int) $request->input('year', AcademicTerm::defaultYear());
        $departmentId = $request->filled('department_id') ? $request->integer('department_id') : null;

        if (! in_array($term, [1, 2, 3], true)) {
            $term = AcademicTerm::defaultTerm();
        }

        if ($departmentId !== null && ! $this->departmentAccess->canAccessDepartment($staff, $departmentId)) {
            abort(403, 'ไม่มีสิทธิ์เข้าถึงสาขานี้');
        }

        // ถ้ามีสาขาเดียว ให้ default เลือกสาขานั้น
        if ($departmentId === null && count($allowedIds) === 1) {
            $departmentId = $allowedIds[0];
        }

        $courses = $this->regService->coursesWithStatus($term, $year, $departmentId, $allowedIds);

        $summary = [
            0 => $courses->where('status', 0)->count(),
            1 => $courses->where('status', 1)->count(),
            2 => $courses->where('status', 2)->count(),
            3 => $courses->where('status', 3)->count(),
        ];

        $statusFilter = $request->input('status', 'all');
        if ($statusFilter !== 'all' && ! in_array((string) $statusFilter, ['0', '1', '2', '3'], true)) {
            $statusFilter = 'all';
        }

        if ($statusFilter !== 'all') {
            $statusValue = (int) $statusFilter;
            $courses = $courses->where('status', $statusValue)->values();
        }

        return view('dept-admin.reg-grade-status.index', [
            'departments' => $departments,
            'courses' => $courses,
            'summary' => $summary,
            'term' => $term,
            'year' => $year,
            'departmentId' => $departmentId,
            'statusFilter' => $statusFilter,
            'years' => AcademicTerm::yearOptions(2565, 2580),
        ]);
    }

    public function approveDepartment(GradeReport $gradeReport): JsonResponse
    {
        $this->authorize('reviewDept', $gradeReport);

        try {
            $this->approvalService->approve($gradeReport, $this->staffUsername());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $fresh = $gradeReport->fresh(['latestDeptApprovalLog.approver']);

        return response()->json([
            'ok' => true,
            'status' => 2,
            'approv' => 1,
            'grade_id' => $gradeReport->grade_id,
            'approved_at' => $fresh?->dateapprove1,
            'approver' => $fresh?->latestDeptApprovalLog?->approver?->displayName(),
            'message' => 'ผ่านที่ประชุมสาขาฯ เรียบร้อย',
        ]);
    }

    public function revertDepartment(GradeReport $gradeReport): JsonResponse
    {
        $this->authorize('reviewDept', $gradeReport);

        try {
            $this->approvalService->resetToSaved($gradeReport, $this->staffUsername());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'status' => 1,
            'approv' => 0,
            'grade_id' => $gradeReport->grade_id,
            'message' => 'เปลี่ยนกลับเป็นส่งแล้วเรียบร้อย',
        ]);
    }

    private function requireStaff()
    {
        $staff = $this->staffAuth->findByEmail(auth()->user()->email);
        abort_unless($staff, 403, 'ไม่พบข้อมูลเจ้าหน้าที่');
        $this->staffAuth->storeInSession($staff);

        return $staff;
    }

    private function staffUsername(): string
    {
        return (string) session('staff_username', $this->requireStaff()->username);
    }
}
