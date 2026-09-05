<?php

namespace App\Http\Controllers\DeptAdmin;

use App\Http\Controllers\Controller;
use App\Models\ThesisGrade;
use App\Models\ThesisGradeFile;
use App\Services\AuditLogService;
use App\Services\DeptAdmin\DepartmentAccessService;
use App\Services\StaffAuthService;
use App\Services\ThesisGrade\ThesisGradeApprovalService;
use App\Services\ThesisGrade\ThesisGradeQueryService;
use App\Services\ThesisGrade\ThesisGradeZipService;
use App\Support\AcademicTerm;
use App\Support\UploadStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ThesisGradeReviewController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly DepartmentAccessService $departmentAccess,
        private readonly ThesisGradeQueryService $queryService,
        private readonly ThesisGradeApprovalService $approval,
        private readonly ThesisGradeZipService $zipService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        $staff = $this->requireStaff();
        $departments = $this->departmentAccess->allowedDepartments($staff);
        $departmentIds = $departments->pluck('department_id')->map(fn ($id) => (int) $id)->all();

        $filters = [
            'term' => (int) $request->input('term', AcademicTerm::defaultTerm()),
            'year' => (int) $request->input('year', AcademicTerm::defaultYear()),
            'status' => (string) $request->input('status', ThesisGrade::STATUS_SUBMITTED),
            'department_id' => $request->filled('department_id') ? (int) $request->input('department_id') : null,
            'subject_code' => trim((string) $request->input('subject_code', '')),
            'q' => trim((string) $request->input('q', '')),
        ];

        if ($filters['department_id'] && ! $this->departmentAccess->canAccessDepartment($staff, $filters['department_id'])) {
            abort(403, 'ไม่มีสิทธิ์เข้าถึงสาขานี้');
        }

        $reports = $this->queryService
            ->deptQuery($departmentIds, $filters)
            ->paginate((int) $request->input('per_page', 20))
            ->withQueryString();

        return view('dept-admin.thesis-grades.index', [
            'reports' => $reports,
            'departments' => $departments,
            'filters' => $filters,
            'years' => AcademicTerm::yearOptions(),
        ]);
    }

    public function show(ThesisGrade $thesisGrade): View
    {
        $this->authorize('reviewDept', $thesisGrade);
        $thesisGrade->load(['students', 'files']);

        return view('dept-admin.thesis-grades.show', [
            'report' => $thesisGrade,
            's0FormUrl' => (string) config('scigrade.s0_letter_form_url'),
        ]);
    }

    public function receive(ThesisGrade $thesisGrade): RedirectResponse
    {
        $this->authorize('reviewDept', $thesisGrade);

        try {
            $this->approval->receive($thesisGrade, $this->staffUsername());
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditLog->record('thesis_grade.receive', 'thesis_grade', $thesisGrade->thesis_grade_id, [
            'subject_code' => $thesisGrade->subject_code,
            'section' => $thesisGrade->section,
        ], actorRole: 'dept_admin');

        return back()->with('status', 'รับเรื่องเรียบร้อย');
    }

    public function sendBack(Request $request, ThesisGrade $thesisGrade): RedirectResponse
    {
        $this->authorize('reviewDept', $thesisGrade);
        $reason = trim((string) $request->input('return_reason', ''));

        try {
            $this->approval->sendBack($thesisGrade, $this->staffUsername(), $reason);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditLog->record('thesis_grade.send_back', 'thesis_grade', $thesisGrade->thesis_grade_id, [
            'reason' => $reason,
        ], actorRole: 'dept_admin');

        return redirect()
            ->route('dept-admin.thesis-grades.index')
            ->with('status', 'ส่งกลับให้อาจารย์แก้ไขแล้ว');
    }

    public function showFile(ThesisGrade $thesisGrade, ThesisGradeFile $file): StreamedResponse
    {
        $this->authorize('reviewDept', $thesisGrade);
        abort_unless((int) $file->thesis_grade_id === (int) $thesisGrade->thesis_grade_id, 404);

        return UploadStorage::inlineResponse($file->stored_path, $file->original_name, 'application/pdf');
    }

    public function downloadReport(ThesisGrade $thesisGrade): BinaryFileResponse
    {
        $this->authorize('reviewDept', $thesisGrade);
        $thesisGrade->load('files');

        try {
            return $this->zipService->downloadReports(
                collect([$thesisGrade]),
                preg_replace('/\.pdf$/i', '-files.zip', $thesisGrade->tsFilename()) ?: 'thesis-files.zip',
            );
        } catch (RuntimeException $e) {
            abort(404, $e->getMessage());
        }
    }

    public function downloadSelected(Request $request): BinaryFileResponse|RedirectResponse
    {
        $staff = $this->requireStaff();
        $departmentIds = $this->departmentAccess->allowedDepartmentIds($staff);
        $ids = array_values(array_filter(array_map('intval', (array) $request->input('ids', []))));

        if ($ids === []) {
            return back()->with('error', 'เลือกอย่างน้อย 1 รายการ');
        }

        $reports = $this->queryService
            ->deptQuery($departmentIds, [])
            ->whereIn('thesis_grade_id', $ids)
            ->with('files')
            ->get();

        if ($reports->isEmpty()) {
            return back()->with('error', 'ไม่พบรายการที่เลือก');
        }

        try {
            return $this->zipService->downloadReports(
                $reports,
                'TS-dept-'.now()->format('Ymd-His').'.zip',
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function requireStaff()
    {
        $staff = $this->staffAuth->findByEmail(auth()->user()->email);
        abort_if($staff === null, 403, 'ไม่พบข้อมูลบุคลากรในระบบ');
        $this->staffAuth->storeInSession($staff);

        return $staff;
    }

    private function staffUsername(): string
    {
        return (string) (session('staff_username') ?: $this->requireStaff()->username);
    }
}
