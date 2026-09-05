<?php

namespace App\Http\Controllers;

use App\Http\Controllers\FacultyAdmin\FacultyReportController;
use App\Http\Controllers\GradeReportController;
use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Models\GradeType;
use App\Services\Instructor\GradeReportSubmissionService;
use App\Services\Instructor\InstructorPendingRegistrarService;
use App\Services\RegistrarGradePdfParser;
use App\Services\RegistrarPdfParseException;
use App\Services\StaffAuthService;
use App\Support\AcademicTerm;
use App\Support\SciGradeRole;
use App\Support\ThaiDateTime;
use App\Support\UploadStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GradeReportPageController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly RegistrarGradePdfParser $pdfParser,
        private readonly GradeReportSubmissionService $submissionService,
        private readonly InstructorPendingRegistrarService $pendingRegistrar,
    ) {}

    private function formView(
        ?int $reportId,
        array $nav = [],
        ?array $uploadParsed = null,
        ?array $prefillReport = null,
        bool $hasRegistrarFile = false,
        bool $hasExamReportFile = false,
    ): View {
        $teacherHelpImageUrl = file_exists(public_path('images/teacher2.png'))
            ? asset('images/teacher2.png')
            : (Storage::disk('public')->exists('teacher2.png')
                ? asset('storage/teacher2.png')
                : 'https://e.sc.kku.ac.th/sci-eoffice/teacher/images2/teacher2.png');

        $prefillTerm = $reportId === null
            ? ($nav['returnTerm'] ?? session('grade_upload_term'))
            : null;
        $prefillYear = $reportId === null
            ? ($nav['returnYear'] ?? session('grade_upload_year'))
            : null;

        $term = (int) ($nav['returnTerm'] ?? AcademicTerm::defaultTerm());
        $year = (int) ($nav['returnYear'] ?? AcademicTerm::defaultYear());

        return view('templade', [
            'reportId' => $reportId,
            'staffDisplayName' => $this->staffAuth->displayNameFor(
                auth()->user()->email,
                auth()->user()->name,
            ),
            'staffTeacherName' => $this->staffAuth->teacherNameFor(
                auth()->user()->email,
                auth()->user()->name,
            ),
            'teacherHelpImageUrl' => $teacherHelpImageUrl,
            'faculties' => GradeType::forForm(),
            'prefillTerm' => $prefillTerm,
            'prefillYear' => $prefillYear,
            'returnUrl' => $nav['returnUrl'] ?? route('grade-reports.my'),
            'dashboardUrl' => route('dashboard'),
            'trackUrl' => route('grade-reports.my', ['term' => $term, 'year' => $year]),
            'uploadParsed' => $uploadParsed,
            'prefillReport' => $prefillReport,
            'cameFromUpload' => is_array($uploadParsed),
            'hasPendingRegistrar' => $this->pendingRegistrar->hasPending(),
            'hasRegistrarFile' => $hasRegistrarFile,
            'hasExamReportFile' => $hasExamReportFile,
        ]);
    }

    /**
     * @return array{returnUrl: string, returnTo: string, returnTerm: int, returnYear: int}
     */
    private function buildReturnContext(Request $request, ?GradeReport $report = null, bool $isCreate = false): array
    {
        $returnTo = $request->input('return', $isCreate ? 'dashboard' : 'my');

        $term = $request->has('term')
            ? $request->integer('term')
            : ($report ? (int) $report->term : null);
        $year = $request->has('year')
            ? $request->integer('year')
            : ($report ? (int) $report->year : null);

        if ($term === null) {
            $term = AcademicTerm::defaultTerm();
        }
        if ($year === null) {
            $year = AcademicTerm::defaultYear();
        }

        $params = ['term' => $term, 'year' => $year];
        $returnUrl = match ($returnTo) {
            'dashboard' => route('dashboard', $params),
            default => route('grade-reports.my', $params),
        };

        return [
            'returnUrl' => $returnUrl,
            'returnTo' => $returnTo,
            'returnTerm' => $term,
            'returnYear' => $year,
        ];
    }

    public function create(Request $request): View
    {
        $uploadParsed = session()->pull('grade_upload_parsed');

        return $this->formView(
            null,
            $this->buildReturnContext($request, isCreate: true),
            is_array($uploadParsed) ? $uploadParsed : null,
        );
    }

    public function edit(Request $request, GradeReport $gradeReport, GradeReportController $gradeReports): View
    {
        $username = $this->resolveStaffUsername();
        abort_unless($username && $gradeReport->username === $username, 403);
        abort_unless($gradeReport->canEdit(), 403, 'ไม่สามารถแก้ไขรายการนี้ได้');

        $gradeReport->load(['gradeStds', 'files']);

        $hasRegistrarFile = $gradeReport->files->contains(
            fn ($file) => $file->resolvedType() === GradeReportFile::TYPE_REGISTRAR
        );
        $hasExamReportFile = $gradeReport->files->contains(
            fn ($file) => $file->resolvedType() === GradeReportFile::TYPE_EXAM_REPORT
        );

        return $this->formView(
            $gradeReport->grade_id,
            $this->buildReturnContext($request, $gradeReport),
            null,
            $gradeReports->formPayload($gradeReport),
            $hasRegistrarFile,
            $hasExamReportFile,
        );
    }

    public function submitCorrections(GradeReport $gradeReport): RedirectResponse
    {
        $username = $this->resolveStaffUsername();
        abort_unless($username && $gradeReport->username === $username, 403);

        try {
            $this->submissionService->submitCorrections($gradeReport, $username);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'ส่งการแก้ไขเรียบร้อยแล้ว รอสาขาวิชาส่งรายงานผลการสอบไล่อีกครั้ง');
    }

    public function upload(): View
    {
        return view('grade-reports.upload', [
            'term' => AcademicTerm::defaultTerm(),
            'year' => AcademicTerm::defaultYear(),
            'years' => AcademicTerm::yearOptions(),
        ]);
    }

    public function storeUpload(Request $request): RedirectResponse
    {
        $request->validate([
            'term' => ['required', 'integer', 'in:1,2,3'],
            'year' => ['required', 'integer', 'min:2500', 'max:2600'],
            'grade_file' => [
                'required',
                'file',
                'mimetypes:application/pdf,application/x-pdf,application/octet-stream',
                'max:20480',
            ],
        ], [
            'grade_file.mimetypes' => 'รองรับเฉพาะไฟล์ PDF จากสำนักทะเบียน',
            'grade_file.required' => 'กรุณาเลือกไฟล์ PDF',
        ]);

        $uploaded = $request->file('grade_file');
        $tmpPath = $uploaded->getRealPath() ?: $uploaded->getPathname();

        if (! is_string($tmpPath) || $tmpPath === '' || ! is_readable($tmpPath)) {
            return redirect()
                ->route('grade-reports.upload')
                ->withInput()
                ->withErrors(['grade_file' => $this->pdfParser->invalidFormatMessage()]);
        }

        try {
            $parsed = $this->pdfParser->parse(
                $tmpPath,
                $uploaded->getClientOriginalName(),
                $request->integer('term'),
                $request->integer('year'),
            );
        } catch (RegistrarPdfParseException $e) {
            return redirect()
                ->route('grade-reports.upload')
                ->withInput()
                ->withErrors(['grade_file' => $e->getMessage()]);
        }

        $path = $uploaded->store('grade-uploads/'.auth()->id(), UploadStorage::diskName());
        $section = (int) ($parsed['grade_stds'][0]['sec'] ?? 0);
        $canonicalName = $this->pdfParser->canonicalFilename(
            (string) ($parsed['subject_code'] ?? 'SUBJECT'),
            $section > 0 ? $section : 1,
        );

        $this->pendingRegistrar->remember([
            'path' => $path,
            'name' => $canonicalName,
            'term' => (int) ($parsed['term'] ?? $request->integer('term')),
            'year' => (int) ($parsed['year'] ?? $request->integer('year')),
            'subject_code' => (string) ($parsed['subject_code'] ?? ''),
            'section' => $parsed['grade_stds'][0]['sec'] ?? null,
            'owner' => auth()->id(),
        ]);
        session(['grade_upload_parsed' => $parsed]);

        return redirect()
            ->route('grade-reports.create', [
                'term' => (int) ($parsed['term'] ?? $request->integer('term')),
                'year' => (int) ($parsed['year'] ?? $request->integer('year')),
                'return' => 'dashboard',
            ])
            ->with('status', 'อ่านไฟล์ PDF สำเร็จ — เมื่อบันทึกรายงาน ระบบจะแนบเป็นใบส่งผลการศึกษา (REG) ให้อัตโนมัติ');
    }

    /**
     * อัปโหลด PDF ในหน้าฟอร์มกรอกจำนวนนักศึกษา — ตรวจรหัสวิชา/ภาค/ปีกับค่าในฟอร์ม
     */
    public function parseSectionPdf(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject_code' => ['required', 'string', 'max:32'],
            'term' => ['required', 'integer', 'in:1,2,3'],
            'year' => ['required', 'integer', 'min:2500', 'max:2600'],
            'grade_file' => [
                'required',
                'file',
                'mimetypes:application/pdf,application/x-pdf,application/octet-stream',
                'max:20480',
            ],
        ], [
            'subject_code.required' => 'กรุณากรอกรหัสวิชาก่อนอัปโหลดไฟล์',
            'term.required' => 'กรุณาเลือกภาคการศึกษาก่อนอัปโหลดไฟล์',
            'year.required' => 'กรุณาเลือกปีการศึกษาก่อนอัปโหลดไฟล์',
            'grade_file.required' => 'กรุณาเลือกไฟล์ PDF',
            'grade_file.mimetypes' => 'รองรับเฉพาะไฟล์ PDF จากสำนักทะเบียน',
        ]);

        $uploaded = $request->file('grade_file');
        $tmpPath = $uploaded->getRealPath() ?: $uploaded->getPathname();

        if (! is_string($tmpPath) || $tmpPath === '' || ! is_readable($tmpPath)) {
            return response()->json([
                'message' => 'ไม่สามารถอัปโหลดไฟล์ได้ กรุณาอัปโหลดไฟล์ใหม่ หรือกรอกข้อมูลเอง',
            ], 422);
        }

        try {
            $parsed = $this->pdfParser->parse(
                $tmpPath,
                $uploaded->getClientOriginalName(),
                (int) $data['term'],
                (int) $data['year'],
            );
        } catch (RegistrarPdfParseException $e) {
            return response()->json([
                'message' => $e->getMessage().' หรือกรอกข้อมูลเอง',
            ], 422);
        }

        $mismatch = $this->registrarMismatchMessages($parsed, $data);
        if ($mismatch !== []) {
            return response()->json([
                'message' => 'ไม่สามารถอัปโหลดไฟล์ได้ — ข้อมูลในไฟล์ไม่ตรงกับที่กรอกด้านบน กรุณาตรวจสอบก่อนอัปโหลดไฟล์ใหม่ หรือกรอกข้อมูลเอง',
                'errors' => ['grade_file' => $mismatch],
                'mismatch' => $mismatch,
            ], 422);
        }

        $section = (int) ($parsed['grade_stds'][0]['sec'] ?? 0);
        $canonicalName = $this->pdfParser->canonicalFilename(
            (string) ($parsed['subject_code'] ?? 'SUBJECT'),
            $section > 0 ? $section : 1,
        );
        $path = $uploaded->store('grade-uploads/'.auth()->id(), UploadStorage::diskName());

        $this->pendingRegistrar->remember([
            'path' => $path,
            'name' => $canonicalName,
            'term' => (int) $parsed['term'],
            'year' => (int) $parsed['year'],
            'subject_code' => (string) $parsed['subject_code'],
            'section' => $parsed['grade_stds'][0]['sec'] ?? null,
            'owner' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'อ่านไฟล์สำเร็จ — กรอกจำนวนนักศึกษาให้แล้ว เมื่อบันทึกรายงาน ระบบจะแนบเป็นใบส่งผลการศึกษา (REG) อัตโนมัติ',
            'parsed' => $parsed,
            'file_name' => $canonicalName,
        ]);
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  array{subject_code: string, term: int, year: int}  $expected
     * @return list<string>
     */
    private function registrarMismatchMessages(array $parsed, array $expected): array
    {
        $messages = [];
        $formSubject = Str::upper(trim((string) $expected['subject_code']));
        $pdfSubject = Str::upper(trim((string) ($parsed['subject_code'] ?? '')));

        if ($formSubject === '' || $pdfSubject === '' || $formSubject !== $pdfSubject) {
            $messages[] = 'รหัสวิชาในไฟล์ ('.($pdfSubject !== '' ? $pdfSubject : '-').') ไม่ตรงกับที่กรอก ('.($formSubject !== '' ? $formSubject : '-').')';
        }

        if ((int) ($parsed['term'] ?? 0) !== (int) $expected['term']) {
            $messages[] = 'ภาคการศึกษาในไฟล์ ('.($parsed['term'] ?? '-').') ไม่ตรงกับที่กรอก ('.$expected['term'].')';
        }

        if ((int) ($parsed['year'] ?? 0) !== (int) $expected['year']) {
            $messages[] = 'ปีการศึกษาในไฟล์ ('.($parsed['year'] ?? '-').') ไม่ตรงกับที่กรอก ('.$expected['year'].')';
        }

        return $messages;
    }

    public function my(Request $request): View
    {
        $term = (int) $request->input('term', AcademicTerm::defaultTerm());
        $year = (int) $request->input('year', AcademicTerm::defaultYear());

        $reports = collect();
        $username = $this->resolveStaffUsername();
        if ($username) {
            $reports = GradeReport::query()
                ->with(['gradeStds', 'files', 'approvalLogs.approver'])
                ->where('username', $username)
                ->where('term', (string) $term)
                ->where('year', (string) $year)
                ->orderByDesc('created_stamp')
                ->orderByDesc('grade_id')
                ->get();
        }

        return view('grade-reports.my', [
            'reports' => $reports,
            'term' => $term,
            'year' => $year,
            'years' => AcademicTerm::yearOptions(),
        ]);
    }

    public function approve(): RedirectResponse
    {
        $role = session('scigrade_role', 'dept_admin');
        abort_if(! in_array($role, ['dept_admin', 'faculty_admin', 'super_admin'], true), 403);

        $params = request()->only(['term', 'year', 'status', 'department_id']);

        return redirect()->route(
            SciGradeRole::isFacultyCapable($role) ? 'faculty-admin.reviews.index' : 'dept-admin.reviews.index',
            $params,
        );
    }

    public function reports(FacultyReportController $facultyReports): View
    {
        $role = session('scigrade_role', 'instructor');

        if (SciGradeRole::isFacultyCapable($role)) {
            return $facultyReports->form();
        }

        return view('grade-reports.reports', compact('role'));
    }

    public function printSummary(Request $request): View
    {
        $role = session('scigrade_role', 'dept_admin');
        abort_if(! in_array($role, ['dept_admin', 'faculty_admin', 'super_admin'], true), 403);

        $query = GradeReport::query()->with('gradeStds')->orderBy('subject_code');

        if ($role === 'dept_admin') {
            $query->whereIn('approv', [0, 1, 2, 3, -1]);
        } else {
            if ($request->filled('fac')) {
                $query->whereHas('gradeStds', fn ($q) => $q->where('fac', 'like', '%'.$request->fac.'%'));
            }
            if ($request->filled('approv')) {
                $query->where('approv', $request->integer('approv'));
            } else {
                $query->where('approv', 2);
            }
        }

        $reports = $query->get();

        return view('grade-reports.print-summary', [
            'reports' => $reports,
            'role' => $role,
            'fac' => $request->get('fac'),
        ]);
    }

    public function print(GradeReport $gradeReport): View
    {
        if (session('scigrade_role', 'instructor') === 'instructor') {
            abort_unless($gradeReport->username === session('staff_username'), 403);
        }

        $gradeReport->load('gradeStds');

        $staff = \App\Models\TblUser::query()
            ->with('titleRelation')
            ->find($gradeReport->username);

        return view('grade-reports.print', [
            'gradeReport' => $gradeReport,
            'teacherSignName' => $staff?->displayName() ?? $gradeReport->teacher,
            'printedAt' => ThaiDateTime::formatPrintFooter(),
        ]);
    }

    private function resolveStaffUsername(): ?string
    {
        $username = session('staff_username');

        if (empty($username) && auth()->user()) {
            $staff = $this->staffAuth->findByEmail(auth()->user()->email);
            if ($staff) {
                $this->staffAuth->storeInSession($staff);
                $username = $staff->username;
            }
        }

        return $username ? (string) $username : null;
    }
}
