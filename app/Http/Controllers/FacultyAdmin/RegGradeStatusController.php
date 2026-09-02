<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Models\GradeReport;
use App\Services\FacultyAdmin\GradeReportCentralApprovalService;
use App\Services\FacultyAdmin\RegGradeDepartmentService;
use App\Services\StaffAuthService;
use App\Support\AcademicTerm;
use App\Support\SciGradeRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class RegGradeStatusController extends Controller
{
    public function __construct(
        private readonly RegGradeDepartmentService $service,
        private readonly GradeReportCentralApprovalService $approvalService,
        private readonly StaffAuthService $staffAuth,
    ) {}

    public function index(Request $request): View
    {
        $term = (int) $request->input('term', AcademicTerm::defaultTerm());
        $year = (int) $request->input('year', AcademicTerm::defaultYear());
        $departmentId = $request->filled('department_id') ? $request->integer('department_id') : null;

        if (! in_array($term, [1, 2, 3], true)) {
            $term = AcademicTerm::defaultTerm();
        }

        if ($departmentId !== null && ! in_array($departmentId, RegGradeDepartmentService::DEPARTMENT_IDS, true)) {
            $departmentId = null;
        }

        $courses = $this->service->coursesWithStatus($term, $year, $departmentId);

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

        return view('faculty-admin.settings.reg-grade-status.index', [
            'departments' => $this->service->departments(),
            'courses' => $courses,
            'summary' => $summary,
            'term' => $term,
            'year' => $year,
            'departmentId' => $departmentId,
            'statusFilter' => $statusFilter,
            'years' => AcademicTerm::yearOptions(2565, 2580),
        ]);
    }

    public function approveFaculty(GradeReport $gradeReport): JsonResponse
    {
        abort_unless(SciGradeRole::isFacultyCapable(), 403);

        [$updatedIds, $lastError] = $this->applyToCourseReports(
            $gradeReport,
            fn (GradeReport $report) => $this->approvalService->approve($report, $this->approverUsername()),
        );

        if ($updatedIds === []) {
            return response()->json(['message' => $lastError ?? 'ไม่มีรายการที่สามารถอนุมัติได้'], 422);
        }

        return response()->json([
            'ok' => true,
            'status' => 3,
            'approv' => 2,
            'grade_id' => $gradeReport->grade_id,
            'grade_ids' => $updatedIds,
            'message' => 'ผ่านที่ประชุมกรรมการคณะฯ เรียบร้อย',
        ]);
    }

    public function revertFaculty(GradeReport $gradeReport): JsonResponse
    {
        abort_unless(SciGradeRole::isFacultyCapable(), 403);

        [$updatedIds, $lastError] = $this->applyToCourseReports(
            $gradeReport,
            fn (GradeReport $report) => $this->approvalService->revertToDepartmentApproved($report, $this->approverUsername()),
        );

        if ($updatedIds === []) {
            return response()->json(['message' => $lastError ?? 'ไม่มีรายการที่สามารถเปลี่ยนกลับได้'], 422);
        }

        return response()->json([
            'ok' => true,
            'status' => 2,
            'approv' => 1,
            'grade_id' => $gradeReport->grade_id,
            'grade_ids' => $updatedIds,
            'message' => 'เปลี่ยนกลับเป็นผ่านสาขาฯ เรียบร้อย',
        ]);
    }

    /**
     * @param  callable(GradeReport): GradeReport  $action
     * @return array{0: list<int>, 1: string|null}
     */
    private function applyToCourseReports(GradeReport $seed, callable $action): array
    {
        $updatedIds = [];
        $lastError = null;

        foreach ($this->service->siblingReports($seed) as $report) {
            try {
                $action($report);
                $updatedIds[] = (int) $report->grade_id;
            } catch (InvalidArgumentException $e) {
                $lastError = $e->getMessage();
            }
        }

        return [$updatedIds, $lastError];
    }

    private function approverUsername(): string
    {
        $username = session('staff_username');
        if (! empty($username)) {
            return (string) $username;
        }

        $staff = $this->staffAuth->findByEmail(auth()->user()->email);
        abort_unless($staff, 403, 'ไม่พบข้อมูลเจ้าหน้าที่');
        $this->staffAuth->storeInSession($staff);

        return (string) $staff->username;
    }
}
