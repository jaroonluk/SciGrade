<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Models\GradeReport;
use App\Services\FacultyAdmin\GradeReportCentralApprovalService;
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

        return view('faculty-admin.settings.reg-grade-status.index', [
            'departments' => $this->service->departments(),
            'courses' => $courses,
            'summary' => $summary,
            'term' => $term,
            'year' => $year,
            'departmentId' => $departmentId,
            'years' => AcademicTerm::yearOptions(2565, 2580),
        ]);
    }

    public function approveFaculty(GradeReport $gradeReport): JsonResponse
    {
        abort_unless(session('scigrade_role') === 'faculty_admin', 403);

        try {
            $this->approvalService->approve($gradeReport, $this->approverUsername());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'status' => 3,
            'approv' => 2,
            'grade_id' => $gradeReport->grade_id,
            'message' => 'ผ่านที่ประชุมกรรมการคณะฯ เรียบร้อย',
        ]);
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
