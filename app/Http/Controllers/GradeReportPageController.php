<?php

namespace App\Http\Controllers;

use App\Http\Controllers\FacultyAdmin\FacultyReportController;
use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Models\GradeType;
use App\Models\TblUser;
use App\Services\Instructor\GradeReportSubmissionService;
use App\Services\Instructor\InstructorPendingRegistrarService;
use App\Services\RegistrarGradePdfParser;
use App\Services\RegistrarPdfParseException;
use App\Services\StaffAuthService;
use App\Support\AcademicTerm;
use App\Support\GradeReportPrintStds;
use App\Support\SciGradeRole;
use App\Support\ThaiDateTime;
use App\Support\ThesisCourse;
use App\Support\UploadStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradeReportPageController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly RegistrarGradePdfParser $pdfParser,
        private readonly GradeReportSubmissionService $submissionService,
        private readonly InstructorPendingRegistrarService $pendingRegistrar,
    ) {}

    /**
     * @param  list<array{section: int, file_id: int, name: string, view_url: string}>  $registrarFileDetails
     * @param  array{file_id: int, name: string, view_url: string}|null  $examFileDetail
     */
    private function formView(
        ?int $reportId,
        array $nav = [],
        ?array $uploadParsed = null,
        ?array $prefillReport = null,
        bool $hasRegistrarFile = false,
        bool $hasExamReportFile = false,
        array $registrarFileDetails = [],
        ?array $examFileDetail = null,
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

        $pendingReg = $this->pendingRegistrar->pendingBySection();
        $registrarFileSections = array_values(array_unique(array_map(
            fn ($row) => (int) ($row['section'] ?? 0),
            $registrarFileDetails,
        )));
        $registrarFileSections = array_values(array_filter($registrarFileSections, fn ($sec) => $sec > 0));

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
            'registrarFileSections' => $registrarFileSections,
            'registrarFileDetails' => array_values($registrarFileDetails),
            'examFileDetail' => $examFileDetail,
            'pendingRegistrarSections' => array_values(array_map(
                fn ($row) => [
                    'section' => (int) ($row['section'] ?? 0),
                    'name' => (string) ($row['name'] ?? ''),
                    'view_url' => route('grade-reports.pending-registrar.show', [
                        'section' => (int) ($row['section'] ?? 0),
                    ]),
                ],
                $pendingReg,
            )),
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
        abort_if(ThesisCourse::isThesisSubject((string) $gradeReport->subject_code, (string) $gradeReport->subject), 404);
        abort_unless($gradeReport->canEdit(), 403, 'ไม่สามารถแก้ไขรายการนี้ได้');

        $gradeReport->load(['gradeStds', 'files']);

        $registrarFileDetails = [];
        $examFileDetail = null;
        foreach ($gradeReport->files as $file) {
            $type = $file->resolvedType();
            if ($type === GradeReportFile::TYPE_REGISTRAR) {
                $sec = $file->resolvedSection($gradeReport);
                if ($sec === null || (int) $sec <= 0) {
                    continue;
                }
                $section = (int) $sec;
                $registrarFileDetails[$section] = [
                    'section' => $section,
                    'file_id' => (int) $file->file_id,
                    'name' => (string) ($file->registrarDisplayName($gradeReport) ?: $file->original_name),
                    'view_url' => route('grade-reports.files.show', [
                        'gradeReport' => $gradeReport->grade_id,
                        'file' => $file->file_id,
                    ]),
                ];
            } elseif ($type === GradeReportFile::TYPE_EXAM_REPORT && $examFileDetail === null) {
                $examFileDetail = [
                    'file_id' => (int) $file->file_id,
                    'name' => (string) $file->original_name,
                    'view_url' => route('grade-reports.files.show', [
                        'gradeReport' => $gradeReport->grade_id,
                        'file' => $file->file_id,
                    ]),
                ];
            }
        }

        $registrarFileDetails = array_values($registrarFileDetails);
        $hasRegistrarFile = $registrarFileDetails !== [];
        $hasExamReportFile = $examFileDetail !== null;

        return $this->formView(
            $gradeReport->grade_id,
            $this->buildReturnContext($request, $gradeReport),
            null,
            $gradeReports->formPayload($gradeReport),
            $hasRegistrarFile,
            $hasExamReportFile,
            $registrarFileDetails,
            $examFileDetail,
        );
    }

    public function showPendingRegistrar(int $section): StreamedResponse
    {
        abort_unless($section > 0, 404);

        $item = $this->pendingRegistrar->findPendingBySection($section);
        abort_unless($item !== null, 404);

        $owner = $item['owner'] ?? null;
        abort_unless($owner === null || (int) $owner === (int) auth()->id(), 403);

        $path = (string) ($item['path'] ?? '');
        abort_unless($path !== '' && UploadStorage::disk()->exists($path), 404);

        $name = (string) ($item['name'] ?? "มข.11-Section-{$section}.pdf");

        return UploadStorage::inlineResponse($path, $name, 'application/pdf');
    }

    public function destroyPendingRegistrar(int $section): JsonResponse
    {
        abort_unless($section > 0, 404);

        $item = $this->pendingRegistrar->findPendingBySection($section);
        if ($item === null) {
            return response()->json(['ok' => true, 'removed' => false]);
        }

        $owner = $item['owner'] ?? null;
        abort_unless($owner === null || (int) $owner === (int) auth()->id(), 403);

        $this->pendingRegistrar->forgetSection($section);

        return response()->json(['ok' => true, 'removed' => true]);
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
            ->with('status', 'อ่านไฟล์ PDF สำเร็จ — ไฟล์จะถูกอัปโหลดเข้าสู่ระบบเมื่อกดเสร็จสิ้นครบทุกขั้นตอน');
    }

    /**
     * อัปโหลด PDF ในหน้าฟอร์มกรอกจำนวนนักศึกษา — ตรวจรหัสวิชา/ภาค/ปีกับค่าในฟอร์ม
     */
    public function parseSectionPdf(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject_code' => ['required', 'string', 'max:32'],
            'term' => ['required', 'integer', 'in:1,2,3'],
            'year' => ['required', 'integer', 'min:2500', 'max:2700'],
            'expected_section' => ['nullable', 'integer', 'min:1', 'max:50'],
            'attach_only' => ['nullable', 'boolean'],
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
        $expectedSection = isset($data['expected_section']) ? (int) $data['expected_section'] : 0;
        if ($expectedSection > 0 && $section > 0 && $section !== $expectedSection) {
            return response()->json([
                'message' => "ไฟล์นี้เป็น Section {$section} แต่ช่องนี้ต้องการ Section {$expectedSection} — กรุณาอัปโหลดแบบฟอร์ม มข.11 ของ Section {$expectedSection}",
            ], 422);
        }
        if ($expectedSection > 0 && $section <= 0) {
            $section = $expectedSection;
        }

        $canonicalName = $this->pdfParser->canonicalFilename(
            (string) ($data['subject_code'] ?? $parsed['subject_code'] ?? 'SUBJECT'),
            $section > 0 ? $section : 1,
        );
        $path = $uploaded->store('grade-uploads/'.auth()->id(), UploadStorage::diskName());

        $this->pendingRegistrar->remember([
            'path' => $path,
            'name' => $canonicalName,
            'term' => (int) $parsed['term'],
            'year' => (int) $parsed['year'],
            'subject_code' => (string) ($data['subject_code'] ?? $parsed['subject_code']),
            'section' => $section > 0 ? $section : ($parsed['grade_stds'][0]['sec'] ?? null),
            'owner' => auth()->id(),
        ]);

        $attachOnly = $request->boolean('attach_only');

        return response()->json([
            'message' => $attachOnly
                ? "แนบแบบฟอร์ม มข.11 Section {$section} แล้ว (ตั้งชื่อเป็น {$canonicalName}) — จะอัปโหลดเข้าสู่ระบบเมื่อกดเสร็จสิ้น"
                : "อ่านไฟล์ มข.11 สำเร็จ — ตั้งชื่อเป็น {$canonicalName} ไฟล์จะถูกอัปโหลดเข้าสู่ระบบเมื่อแนบใบขวางครบและกดเสร็จสิ้น",
            'parsed' => $attachOnly ? null : $parsed,
            'file_name' => $canonicalName,
            'section' => $section > 0 ? $section : null,
            'attach_only' => $attachOnly,
            'view_url' => $section > 0
                ? route('grade-reports.pending-registrar.show', ['section' => $section])
                : null,
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
                ->examReportable()
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

        $query = GradeReport::query()->examReportable()->with('gradeStds')->orderBy('subject_code');

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

    public function print(Request $request, GradeReport $gradeReport): View
    {
        abort_if(ThesisCourse::isThesisSubject((string) $gradeReport->subject_code, (string) $gradeReport->subject), 404);

        if (session('scigrade_role', 'instructor') === 'instructor') {
            abort_unless($gradeReport->username === session('staff_username'), 403);
        }

        $gradeReport->load('gradeStds');

        $staff = TblUser::query()
            ->with('titleRelation')
            ->find($gradeReport->username);

        return view('grade-reports.print', [
            'gradeReport' => $gradeReport,
            'printStds' => GradeReportPrintStds::forRequest($request, $gradeReport),
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
