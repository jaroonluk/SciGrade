<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Models\ThesisGrade;
use App\Models\ThesisGradeFile;
use App\Services\AuditLogService;
use App\Services\FacultyAdmin\FacultyReportQueryService;
use App\Services\StaffAuthService;
use App\Services\ThesisGrade\ThesisGradeApprovalService;
use App\Services\ThesisGrade\ThesisGradeDocxExportService;
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
        private readonly ThesisGradeDocxExportService $docxExport,
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

        return back()->with('status', 'ผ่านที่ประชุมกรรมการคณะฯ เรียบร้อย');
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
            $complete = request()->boolean('complete');

            return $complete
                ? $this->zipService->downloadCompleteReports(
                    collect([$thesisGrade]),
                    preg_replace('/\.pdf$/i', '-complete.zip', $thesisGrade->tsFilename()) ?: 'thesis-complete.zip',
                )
                : $this->zipService->downloadReports(
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

        if ($ids === [] && $request->boolean('all_filtered')) {
            $filters = [
                'term' => $request->filled('term') ? (int) $request->input('term') : null,
                'year' => $request->filled('year') ? (int) $request->input('year') : null,
                'status' => (string) $request->input('status', ''),
                'department_id' => $request->filled('department_id') ? (int) $request->input('department_id') : null,
                'subject_code' => trim((string) $request->input('subject_code', '')),
                'q' => trim((string) $request->input('q', '')),
            ];
            $reports = $this->queryService
                ->facultyQuery($filters)
                ->with('files')
                ->get();
        } else {
            if ($ids === []) {
                return back()->with('error', 'เลือกอย่างน้อย 1 รายการ หรือดาวน์โหลดทั้งหมดตามเงื่อนไข');
            }

            $reports = $this->queryService
                ->facultyQuery([])
                ->whereIn('thesis_grade_id', $ids)
                ->with('files')
                ->get();
        }

        if ($reports->isEmpty()) {
            return back()->with('error', 'ไม่พบรายการที่เลือก');
        }

        try {
            $complete = $request->boolean('complete');

            return $complete
                ? $this->zipService->downloadCompleteReports(
                    $reports,
                    'TS-faculty-complete-'.now()->format('Ymd-His').'.zip',
                )
                : $this->zipService->downloadReports(
                    $reports,
                    'TS-faculty-'.now()->format('Ymd-His').'.zip',
                );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function summary(Request $request): View
    {
        $this->requireReviewer();

        $filters = [
            'term' => (int) $request->input('term', AcademicTerm::defaultTerm()),
            'year' => (int) $request->input('year', AcademicTerm::defaultYear()),
            'status' => (string) $request->input('status', ''),
            'department_id' => $request->filled('department_id') ? (int) $request->input('department_id') : null,
            'subject_code' => trim((string) $request->input('subject_code', '')),
            'q' => trim((string) $request->input('q', '')),
        ];

        $reports = $this->queryService
            ->facultyQuery($filters)
            ->with('students')
            ->reorder()
            ->orderBy('subject_code')
            ->orderBy('section')
            ->get();

        return view('faculty-admin.thesis-grades.summary', [
            'reports' => $reports,
            'departments' => $this->facultyReports->filterDepartments(),
            'filters' => $filters,
            'years' => AcademicTerm::yearOptions(),
        ]);
    }

    public function exportSummary(Request $request): BinaryFileResponse
    {
        $this->requireReviewer();

        $term = (int) $request->input('term', AcademicTerm::defaultTerm());
        $year = (int) $request->input('year', AcademicTerm::defaultYear());
        $filters = [
            'term' => $term,
            'year' => $year,
            'status' => (string) $request->input('status', ''),
            'department_id' => $request->filled('department_id') ? (int) $request->input('department_id') : null,
            'subject_code' => trim((string) $request->input('subject_code', '')),
            'q' => trim((string) $request->input('q', '')),
        ];

        $reports = $this->queryService
            ->facultyQuery($filters)
            ->with('students')
            ->reorder()
            ->orderBy('subject_code')
            ->orderBy('section')
            ->get();

        return $this->docxExport->downloadSummary($reports, $term, $year);
    }

    private function requireReviewer(): void
    {
        abort_unless(SciGradeRole::canReviewThesisGrades(), 403, 'เฉพาะเจ้าหน้าที่งานบริการ (บัณฑิตศึกษา) และ Super Admin เท่านั้น');
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
