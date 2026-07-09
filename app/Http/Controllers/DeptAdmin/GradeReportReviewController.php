<?php

namespace App\Http\Controllers\DeptAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeptAdmin\GradeReportApprovalRequest;
use App\Http\Requests\DeptAdmin\GradeReportReviewFilterRequest;
use App\Models\GradeReport;
use App\Services\DeptAdmin\DepartmentAccessService;
use App\Services\DeptAdmin\DepartmentReportQueryService;
use App\Services\DeptAdmin\GradeReportApprovalService;
use App\Services\StaffAuthService;
use App\Support\AcademicTerm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class GradeReportReviewController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly DepartmentAccessService $departmentAccess,
        private readonly DepartmentReportQueryService $queryService,
        private readonly GradeReportApprovalService $approvalService,
    ) {}

    public function index(GradeReportReviewFilterRequest $request): View
    {
        $staff = $this->requireStaff();
        $departments = $this->departmentAccess->allowedDepartments($staff);
        $departmentIds = $departments->pluck('department_id')->map(fn ($id) => (int) $id)->all();

        $filters = $request->filters($departmentIds);
        $filters['term'] = $filters['term'] ?? AcademicTerm::defaultTerm();
        $filters['year'] = $filters['year'] ?? AcademicTerm::defaultYear();

        if ($request->filled('department_id') && ! $this->departmentAccess->canAccessDepartment($staff, (int) $request->department_id)) {
            abort(403, 'ไม่มีสิทธิ์เข้าถึงสาขานี้');
        }

        $perPage = (int) $request->input('per_page', 20);
        $reports = $this->queryService
            ->baseQuery($filters)
            ->paginate($perPage)
            ->withQueryString();

        return view('dept-admin.reviews.index', [
            'reports' => $reports,
            'departments' => $departments,
            'filters' => $filters,
            'years' => AcademicTerm::yearOptions(),
        ]);
    }

    public function approve(GradeReportApprovalRequest $request, GradeReport $gradeReport): JsonResponse|RedirectResponse
    {
        $this->authorize('reviewDept', $gradeReport);

        try {
            $wasResubmit = $gradeReport->awaitingDeptResubmit();
            $this->approvalService->approve(
                $gradeReport,
                $this->staffUsername(),
                $request->input('remark'),
            );
        } catch (InvalidArgumentException $e) {
            return $this->failureResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse(
            $request,
            $wasResubmit
                ? 'ส่งรายงานผลการสอบไล่อีกครั้งเรียบร้อย'
                : 'บันทึกผ่านการรับรองผลสอบเรียบร้อย',
        );
    }

    public function reject(GradeReportApprovalRequest $request, GradeReport $gradeReport): JsonResponse|RedirectResponse
    {
        $this->authorize('reviewDept', $gradeReport);

        try {
            $this->approvalService->reject(
                $gradeReport,
                $this->staffUsername(),
                $request->input('remark'),
            );
        } catch (InvalidArgumentException $e) {
            return $this->failureResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse($request, 'บันทึกสถานะยังไม่ผ่านการรับรองผลสอบเรียบร้อย');
    }

    public function sendBack(GradeReportApprovalRequest $request, GradeReport $gradeReport): JsonResponse|RedirectResponse
    {
        $this->authorize('reviewDept', $gradeReport);

        try {
            $this->approvalService->sendBackForInstructorEdit(
                $gradeReport,
                $this->staffUsername(),
                $request->input('remark'),
            );
        } catch (InvalidArgumentException $e) {
            return $this->failureResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse($request, 'ส่งกลับให้อาจารย์แก้ไขเรียบร้อย');
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

    private function successResponse(GradeReportApprovalRequest $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return back()->with('status', $message);
    }

    private function failureResponse(GradeReportApprovalRequest $request, string $message, int $code): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $code);
        }

        return back()->withErrors(['approval' => $message]);
    }
}
