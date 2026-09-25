<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Enums\GradeApprovalStatus;
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
            4 => $courses->where('status', 4)->count(),
            5 => $courses->where('status', 5)->count(),
            6 => $courses->where('status', 6)->count(),
        ];

        $statusFilter = $request->input('status', 'all');
        if ($statusFilter !== 'all' && ! in_array((string) $statusFilter, ['0', '1', '2', '3', '4', '5', '6'], true)) {
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

    public function setStatus(Request $request, GradeReport $gradeReport): JsonResponse
    {
        abort_unless(SciGradeRole::isFacultyCapable(), 403);

        $action = (string) $request->input('action', '');
        if (! in_array($action, ['check', 'approve', 'send_back'], true)) {
            return response()->json([
                'message' => 'สถานะที่ Admin กลางตั้งได้มีเพียง ตรวจแล้ว / ผ่านที่ประชุมกรรมการคณะฯ / ส่งกลับแก้ไข',
            ], 422);
        }

        [$updatedIds, $lastError] = $this->applyToCourseReports(
            $gradeReport,
            function (GradeReport $report) use ($action) {
                return match ($action) {
                    'check' => $this->setToChecked($report),
                    'approve' => $this->approvalService->approve($report, $this->approverUsername()),
                    default => $this->sendBackReport($report),
                };
            },
        );

        if ($updatedIds === []) {
            $fallback = match ($action) {
                'check' => 'ไม่มีรายการที่สามารถตั้งเป็นตรวจแล้วได้',
                'approve' => 'ไม่มีรายการที่สามารถตั้งเป็นผ่านที่ประชุมกรรมการคณะฯ ได้',
                default => 'ไม่มีรายการที่สามารถส่งกลับแก้ไขได้',
            };

            return response()->json(['message' => $lastError ?? $fallback], 422);
        }

        [$status, $approv, $message] = match ($action) {
            'check' => [4, GradeApprovalStatus::FacultyChecked->value, 'ตั้งเป็นตรวจแล้วทุก Section เรียบร้อย'],
            'approve' => [5, GradeApprovalStatus::CentralApproved->value, 'ผ่านที่ประชุมกรรมการคณะฯ ทุก Section เรียบร้อย'],
            default => [6, GradeApprovalStatus::DepartmentRejected->value, 'ส่งกลับแก้ไขทุก Section เรียบร้อย'],
        };

        return response()->json([
            'ok' => true,
            'status' => $status,
            'approv' => $approv,
            'action' => $action,
            'grade_id' => $gradeReport->grade_id,
            'grade_ids' => $updatedIds,
            'message' => $message,
        ]);
    }

    public function approveFaculty(GradeReport $gradeReport): JsonResponse
    {
        request()->merge(['action' => 'approve']);

        return $this->setStatus(request(), $gradeReport);
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
            'status' => 3,
            'approv' => GradeApprovalStatus::DepartmentApproved->value,
            'grade_id' => $gradeReport->grade_id,
            'grade_ids' => $updatedIds,
            'message' => 'เปลี่ยนกลับเป็นผ่านที่ประชุมสาขาเรียบร้อย',
        ]);
    }

    private function setToChecked(GradeReport $report): GradeReport
    {
        $from = (int) $report->approv;

        if ($from === GradeApprovalStatus::FacultyChecked->value) {
            return $report->fresh(['gradeStds', 'files', 'latestCentralApprovalLog.approver']) ?? $report;
        }

        if ($from === GradeApprovalStatus::CentralApproved->value) {
            return $this->approvalService->revertToFacultyChecked($report, $this->approverUsername());
        }

        return $this->approvalService->markChecked($report, $this->approverUsername());
    }

    private function sendBackReport(GradeReport $report): GradeReport
    {
        $from = (int) $report->approv;

        if ($from === GradeApprovalStatus::CentralApproved->value) {
            return $this->approvalService->sendBackForInstructorEdit(
                $report,
                $this->approverUsername(),
                'ส่งกลับให้อาจารย์แก้ไข',
            );
        }

        return $this->approvalService->reject(
            $report,
            $this->approverUsername(),
            'ส่งกลับให้อาจารย์แก้ไข',
        );
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
