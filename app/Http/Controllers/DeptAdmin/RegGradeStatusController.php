<?php

namespace App\Http\Controllers\DeptAdmin;

use App\Http\Controllers\Controller;
use App\Models\GradeReport;
use App\Services\DeptAdmin\DepartmentAccessService;
use App\Services\DeptAdmin\GradeReportApprovalService;
use App\Services\FacultyAdmin\RegGradeDepartmentService;
use App\Services\FacultyAdmin\RegGradeDumpService;
use App\Services\StaffAuthService;
use App\Support\AcademicTerm;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class RegGradeStatusController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly DepartmentAccessService $departmentAccess,
        private readonly RegGradeDepartmentService $regService,
        private readonly GradeReportApprovalService $approvalService,
        private readonly RegGradeDumpService $dumpService,
    ) {}

    public function index(Request $request): View
    {
        $staff = $this->requireStaff();
        $allowedIds = $this->departmentAccess->allowedDepartmentIds($staff);
        $departments = $this->departmentAccess->allowedDepartments($staff);

        $term = (int) $request->input('term', AcademicTerm::defaultTerm());
        $year = (int) $request->input('year', AcademicTerm::defaultYear());
        $departmentId = $request->filled('department_id') ? $request->integer('department_id') : null;
        $educationLevel = strtolower(trim((string) $request->input('education_level', 'all')));
        if (! in_array($educationLevel, ['bachelor', 'master', 'doctoral', 'graduate', 'all'], true)) {
            $educationLevel = 'all';
        }

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

        $courses = $this->regService->coursesWithStatus(
            $term,
            $year,
            $departmentId,
            $allowedIds,
            $educationLevel,
        );

        $summary = [
            0 => $courses->where('status', 0)->count(),
            1 => $courses->where('status', 1)->count(),
            2 => $courses->where('status', 2)->count(),
            3 => $courses->where('status', 3)->count(),
            4 => $courses->where('status', 4)->count() + $courses->where('status', 5)->count(),
            5 => $courses->where('status', 6)->count(),
        ];

        $statusFilter = $request->input('status', 'all');
        if ($statusFilter !== 'all' && ! in_array((string) $statusFilter, ['0', '1', '2', '3', '4', '5'], true)) {
            $statusFilter = 'all';
        }

        if ($statusFilter !== 'all') {
            $statusValue = (int) $statusFilter;
            if ($statusValue === 4) {
                $courses = $courses->whereIn('status', [4, 5])->values();
            } elseif ($statusValue === 5) {
                $courses = $courses->where('status', 6)->values();
            } else {
                $courses = $courses->where('status', $statusValue)->values();
            }
        }

        $programTypeMap = [];
        if ($courses->isNotEmpty()) {
            try {
                $programTypeMap = $this->dumpService->courseProgramTypeMap($year, $term, $courses);
            } catch (Throwable) {
                $programTypeMap = [];
            }
        }

        $courses = $courses->map(function (object $row) use ($programTypeMap) {
            $row->program_types = $programTypeMap[RegGradeDumpService::courseSectionKey(
                (string) $row->COURSECODE,
                $row->SECTION,
            )] ?? [];

            return $row;
        });

        return view('dept-admin.reg-grade-status.index', [
            'departments' => $departments,
            'courses' => $courses,
            'summary' => $summary,
            'term' => $term,
            'year' => $year,
            'departmentId' => $departmentId,
            'educationLevel' => $educationLevel,
            'statusFilter' => $statusFilter,
            'years' => AcademicTerm::yearOptions(2565, 2580),
        ]);
    }

    public function queueMeeting(GradeReport $gradeReport): JsonResponse
    {
        return $this->setCourseDisplayStatus($gradeReport, 2);
    }

    public function approveDepartment(GradeReport $gradeReport): JsonResponse
    {
        return $this->setCourseDisplayStatus($gradeReport, 3);
    }

    public function revertDepartment(GradeReport $gradeReport): JsonResponse
    {
        return $this->setCourseDisplayStatus($gradeReport, 1);
    }

    public function setStatus(Request $request, GradeReport $gradeReport): JsonResponse
    {
        $status = $request->integer('status');
        if (! in_array($status, [1, 2, 3], true)) {
            return response()->json([
                'message' => 'สถานะที่สาขาตั้งได้มีเพียง ส่งแล้ว / นำเข้าที่ประชุมสาขา / ผ่านที่ประชุมสาขา',
            ], 422);
        }

        return $this->setCourseDisplayStatus($gradeReport, $status);
    }

    private function setCourseDisplayStatus(GradeReport $gradeReport, int $displayStatus): JsonResponse
    {
        try {
            $this->authorize('manageRegGradeStatus', $gradeReport);
        } catch (AuthorizationException) {
            return response()->json([
                'message' => 'ไม่มีสิทธิ์เปลี่ยนสถานะรายวิชานี้ (รหัสวิชาอยู่นอกสาขาที่รับผิดชอบ)',
            ], 403);
        }

        [$updatedIds, $lastError, $lastReport] = $this->applyToCourseReports(
            $gradeReport,
            fn (GradeReport $report) => $this->approvalService->setDisplayStatus(
                $report,
                $displayStatus,
                $this->staffUsername(),
            ),
        );

        if ($updatedIds === []) {
            $fallback = match ($displayStatus) {
                2 => 'ไม่มีรายการที่สามารถนำเข้าที่ประชุมสาขาได้',
                3 => 'ไม่มีรายการที่สามารถผ่านที่ประชุมสาขาได้',
                default => 'ไม่มีรายการที่สามารถเปลี่ยนกลับเป็นส่งแล้วได้',
            };

            return response()->json(['message' => $lastError ?? $fallback], 422);
        }

        $fresh = ($lastReport ?? $gradeReport)->fresh(['latestDeptApprovalLog.approver']);
        $message = match ($displayStatus) {
            2 => 'นำเข้าที่ประชุมสาขาทุก Section เรียบร้อย',
            3 => 'ผ่านที่ประชุมสาขาทุก Section เรียบร้อย',
            default => 'เปลี่ยนเป็นส่งแล้วทุก Section เรียบร้อย',
        };

        return response()->json([
            'ok' => true,
            'status' => $displayStatus,
            'approv' => match ($displayStatus) {
                2 => 4,
                3 => 1,
                default => 0,
            },
            'grade_id' => $gradeReport->grade_id,
            'grade_ids' => $updatedIds,
            'approved_at' => $displayStatus === 3 ? $fresh?->dateapprove1 : null,
            'approver' => $displayStatus === 3
                ? $fresh?->latestDeptApprovalLog?->approver?->displayName()
                : null,
            'message' => $message,
        ]);
    }

    /**
     * @param  callable(GradeReport): GradeReport  $action
     * @return array{0: list<int>, 1: string|null, 2: GradeReport|null}
     */
    private function applyToCourseReports(GradeReport $seed, callable $action): array
    {
        $updatedIds = [];
        $lastError = null;
        $lastReport = null;

        $user = auth()->user();

        foreach ($this->regService->siblingReports($seed) as $report) {
            if ($user === null || $user->cannot('manageRegGradeStatus', $report)) {
                continue;
            }

            try {
                $lastReport = $action($report);
                $updatedIds[] = (int) $report->grade_id;
            } catch (InvalidArgumentException $e) {
                $lastError = $e->getMessage();
            }
        }

        return [$updatedIds, $lastError, $lastReport];
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
