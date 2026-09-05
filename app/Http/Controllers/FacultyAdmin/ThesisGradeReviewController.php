<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Models\ThesisGrade;
use App\Models\ThesisGradeFile;
use App\Services\AuditLogService;
use App\Services\FacultyAdmin\FacultyReportQueryService;
use App\Services\StaffAuthService;
use App\Services\ThesisGrade\ThesisGradeApprovalService;
use App\Services\ThesisGrade\ThesisGradeQueryService;
use App\Services\ThesisGrade\ThesisGradeZipService;
use App\Support\AcademicTerm;
use App\Support\SciGradeRole;
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
        private readonly FacultyReportQueryService $facultyReports,
        private readonly ThesisGradeQueryService $queryService,
        private readonly ThesisGradeApprovalService $approval,
        private readonly ThesisGradeZipService $zipService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        $this->requireReviewer();

        $filters = [
            'term' => (int) $request->input('term', AcademicTerm::defaultTerm()),
            'year' => (int) $request->input('year', AcademicTerm::defaultYear()),
            'status' => (string) $request->input('status', ThesisGrade::STATUS_RECEIVED),
            'department_id' => $request->filled('department_id') ? (int) $request->input('department_id') : null,
            'subject_code' => trim((string) $request->input('subject_code', '')),
            'q' => trim((string) $request->input('q', '')),
        ];

        $reports = $this->queryService
            ->facultyQuery($filters)
            ->paginate((int) $request->input('per_page', 20))
            ->withQueryString();

        return view('faculty-admin.thesis-grades.index', [
            'reports' => $reports,
            'departments' => $this->facultyReports->filterDepartments(),
            'filters' => $filters,
            'years' => AcademicTerm::yearOptions(),
        ]);
    }

    public function show(ThesisGrade $thesisGrade): View
    {
        $this->requireReviewer();
        $this->authorize('reviewFaculty', $thesisGrade);
        $thesisGrade->load(['students', 'files']);

        return view('faculty-admin.thesis-grades.show', [
            'report' => $thesisGrade,
            's0FormUrl' => (string) config('scigrade.s0_letter_form_url'),
        ]);
    }

    public function receive(ThesisGrade $thesisGrade): RedirectResponse
    {
        $this->requireReviewer();
        $this->authorize('reviewFaculty', $thesisGrade);

        try {
            $this->approval->facultyReceive($thesisGrade, $this->staffUsername());
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditLog->record('thesis_grade.faculty_receive', 'thesis_grade', $thesisGrade->thesis_grade_id, [
            'subject_code' => $thesisGrade->subject_code,
            'section' => $thesisGrade->section,
        ], actorRole: SciGradeRole::current());

        return back()->with('status', 'คณะรับเรื่องเรียบร้อย');
    }

    public function sendBack(Request $request, ThesisGrade $thesisGrade): RedirectResponse
    {
        $this->requireReviewer();
        $this->authorize('reviewFaculty', $thesisGrade);
        $reason = trim((string) $request->input('return_reason', ''));

        try {
            $this->approval->facultySendBack($thesisGrade, $this->staffUsername(), $reason);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditLog->record('thesis_grade.faculty_send_back', 'thesis_grade', $thesisGrade->thesis_grade_id, [
            'reason' => $reason,
        ], actorRole: SciGradeRole::current());

        return redirect()
            ->route('faculty-admin.thesis-grades.index')
            ->with('status', 'ส่งกลับให้อาจารย์แก้ไขแล้ว');
    }

    public function showFile(ThesisGrade $thesisGrade, ThesisGradeFile $file): StreamedResponse
    {
        $this->requireReviewer();
        $this->authorize('reviewFaculty', $thesisGrade);
        abort_unless((int) $file->thesis_grade_id === (int) $thesisGrade->thesis_grade_id, 404);

        return UploadStorage::inlineResponse($file->stored_path, $file->original_name, 'application/pdf');
    }

    public function downloadReport(ThesisGrade $thesisGrade): BinaryFileResponse
    {
        $this->requireReviewer();
        $this->authorize('reviewFaculty', $thesisGrade);
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
        $this->requireReviewer();
        $ids = array_values(array_filter(array_map('intval', (array) $request->input('ids', []))));

        if ($ids === []) {
            return back()->with('error', 'เลือกอย่างน้อย 1 รายการ');
        }

        $reports = $this->queryService
            ->facultyQuery([])
            ->whereIn('thesis_grade_id', $ids)
            ->with('files')
            ->get();

        if ($reports->isEmpty()) {
            return back()->with('error', 'ไม่พบรายการที่เลือก');
        }

        try {
            return $this->zipService->downloadReports(
                $reports,
                'TS-faculty-'.now()->format('Ymd-His').'.zip',
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function requireReviewer(): void
    {
        abort_unless(SciGradeRole::canReviewThesisGrades(), 403, 'เฉพาะเจ้าหน้าที่งานบริการ (ป.บัณฑิต) เท่านั้น');
        $this->requireStaff();
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
