<?php

namespace App\Http\Controllers;

use App\Http\Requests\ThesisGrade\SaveThesisGradeRequest;
use App\Models\ThesisGrade;
use App\Models\ThesisGradeFile;
use App\Services\StaffAuthService;
use App\Services\ThesisGrade\PdfSignatureInspector;
use App\Services\ThesisGrade\ThesisGradeAttachmentNameService;
use App\Services\ThesisGrade\ThesisGradePdfParseException;
use App\Services\ThesisGrade\ThesisGradePdfParser;
use App\Services\ThesisGrade\ThesisGradeService;
use App\Services\ThesisGrade\ThesisGradeZipService;
use App\Support\AcademicTerm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ThesisGradePageController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly ThesisGradeService $thesisGrades,
        private readonly ThesisGradeZipService $zipService,
        private readonly ThesisGradePdfParser $pdfParser,
        private readonly ThesisGradeAttachmentNameService $names,
        private readonly PdfSignatureInspector $signatures,
    ) {}

    public function index(Request $request): View
    {
        $username = $this->staffUsername();
        $term = (int) $request->input('term', AcademicTerm::defaultTerm());
        $year = (int) $request->input('year', AcademicTerm::defaultYear());

        $reports = ThesisGrade::query()
            ->with(['students', 'files'])
            ->ownedBy($username)
            ->where('term', $term)
            ->where('year', $year)
            ->orderByDesc('updated_at')
            ->orderByDesc('thesis_grade_id')
            ->get();

        return view('thesis-grades.index', [
            'reports' => $reports,
            'term' => $term,
            'year' => $year,
            'years' => AcademicTerm::yearOptions(),
            'staffDisplayName' => $this->staffAuth->displayNameFor(
                auth()->user()->email,
                auth()->user()->name,
            ),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formView(null, [
            'term' => (int) $request->input('term', AcademicTerm::defaultTerm()),
            'year' => (int) $request->input('year', AcademicTerm::defaultYear()),
        ]);
    }

    /**
     * อัปโหลดใบ TS แล้วอ่าน PDF → สร้างร่าง + เก็บไฟล์บน MinIO/S3
     */
    public function quickUpload(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:15360'],
            'term' => ['nullable', 'integer', 'in:1,2,3'],
            'year' => ['nullable', 'integer', 'min:2500', 'max:2700'],
        ], [
            'file.mimes' => 'อัปโหลดได้เฉพาะไฟล์ PDF',
            'file.max' => 'ขนาดไฟล์ต้องไม่เกิน 15 MB',
        ]);

        /** @var UploadedFile $uploaded */
        $uploaded = $validated['file'];
        $termFallback = (int) ($validated['term'] ?? AcademicTerm::defaultTerm());
        $yearFallback = (int) ($validated['year'] ?? AcademicTerm::defaultYear());

        try {
            $parsed = $this->pdfParser->parse(
                $uploaded->getRealPath() ?: $uploaded->getPathname(),
                $uploaded->getClientOriginalName(),
                $termFallback,
                $yearFallback,
            );
        } catch (ThesisGradePdfParseException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);
            $message = 'อ่านไฟล์ PDF ไม่สำเร็จ';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $signature = $this->signatures->inspectUploaded($uploaded);
        $username = $this->staffUsername();
        $teacher = $parsed['teacher']
            ?: $this->staffAuth->teacherNameFor(auth()->user()->email, auth()->user()->name);

        try {
            $report = $this->thesisGrades->save(
                [
                    'term' => $parsed['term'],
                    'year' => $parsed['year'],
                    'subject_code' => $parsed['subject_code'],
                    'subject' => $parsed['subject'],
                    'section' => $parsed['section'],
                    'students' => $parsed['students'],
                    'checked_proposal' => false,
                    'checked_signed' => $signature['signed'],
                    'intent' => 'draft',
                ],
                $username,
                $teacher,
            );
        } catch (InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        try {
            $storedPath = $this->names->storeUploadedFile(
                $report,
                $uploaded,
                ThesisGradeFile::TYPE_TS_REPORT,
            );
        } catch (Throwable $e) {
            report($e);
            $message = 'อัปโหลดไฟล์ไปยัง S3 ไม่สำเร็จ — ตรวจค่า MINIO_* ใน .env';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 500);
            }

            return redirect()
                ->route('thesis-grades.edit', ['thesisGrade' => $report, 'step' => 1])
                ->with('error', $message);
        }

        ThesisGradeFile::query()->create([
            'thesis_grade_id' => $report->thesis_grade_id,
            'student_id' => null,
            'file_type' => ThesisGradeFile::TYPE_TS_REPORT,
            'original_name' => basename($storedPath),
            'stored_path' => $storedPath,
            'uploaded_at' => now(),
            'username' => $username,
        ]);

        $editUrl = route('thesis-grades.edit', [
            'thesisGrade' => $report,
            'step' => 2,
        ]);

        $payload = [
            'ok' => true,
            'message' => 'อัปโหลดและอ่านข้อมูลจาก PDF แล้ว',
            'edit_url' => $editUrl,
            'report_id' => $report->thesis_grade_id,
            'parsed' => [
                'subject_code' => $parsed['subject_code'],
                'subject' => $parsed['subject'],
                'term' => $parsed['term'],
                'year' => $parsed['year'],
                'section' => $parsed['section'],
                'student_count' => count($parsed['students']),
            ],
            'warnings' => $parsed['warnings'],
            'signature_signed' => $signature['signed'],
            'signature_message' => $signature['message'],
            'stored_name' => basename($storedPath),
            'disk' => \App\Support\UploadStorage::diskName(),
        ];

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return redirect($editUrl)
            ->with('status', $payload['message'])
            ->with('pdf_warnings', $parsed['warnings'])
            ->with('signature_message', $signature['message']);
    }

    public function store(SaveThesisGradeRequest $request): RedirectResponse
    {
        try {
            $report = $this->thesisGrades->save(
                $request->validated(),
                $this->staffUsername(),
                $this->staffAuth->teacherNameFor(auth()->user()->email, auth()->user()->name),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return $this->afterSave($request, $report, created: true);
    }

    public function edit(ThesisGrade $thesisGrade): View
    {
        $this->authorize('view', $thesisGrade);
        $thesisGrade->load(['students', 'files']);

        return $this->formView($thesisGrade);
    }

    public function update(SaveThesisGradeRequest $request, ThesisGrade $thesisGrade): RedirectResponse
    {
        $this->authorize('update', $thesisGrade);

        try {
            $report = $this->thesisGrades->save(
                $request->validated(),
                $this->staffUsername(),
                $this->staffAuth->teacherNameFor(auth()->user()->email, auth()->user()->name),
                $thesisGrade,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return $this->afterSave($request, $report, created: false);
    }

    public function submit(Request $request, ThesisGrade $thesisGrade): RedirectResponse
    {
        $this->authorize('submit', $thesisGrade);

        $errors = $this->thesisGrades->submit($thesisGrade);
        if ($errors !== []) {
            return redirect()
                ->route('thesis-grades.edit', $thesisGrade)
                ->with('error', implode("\n", $errors))
                ->with('submit_errors', $errors);
        }

        return redirect()
            ->route('thesis-grades.index', ['term' => $thesisGrade->term, 'year' => $thesisGrade->year])
            ->with('status', 'ส่งผลการเรียนเข้าสาขาแล้ว');
    }

    public function destroy(ThesisGrade $thesisGrade): RedirectResponse
    {
        $this->authorize('delete', $thesisGrade);

        $term = $thesisGrade->term;
        $year = $thesisGrade->year;
        $thesisGrade->delete();

        return redirect()
            ->route('thesis-grades.index', ['term' => $term, 'year' => $year])
            ->with('status', 'ลบร่างเรียบร้อย');
    }

    public function downloadZip(ThesisGrade $thesisGrade): BinaryFileResponse
    {
        $this->authorize('view', $thesisGrade);
        $thesisGrade->load('files');

        try {
            return $this->zipService->downloadReports(
                collect([$thesisGrade]),
                $thesisGrade->displayCode().'-'.$thesisGrade->paddedSection().'-files.zip',
            );
        } catch (RuntimeException $e) {
            abort(404, $e->getMessage());
        }
    }

    private function afterSave(SaveThesisGradeRequest $request, ThesisGrade $report, bool $created): RedirectResponse
    {
        if ($request->input('intent') === 'submit') {
            $errors = $this->thesisGrades->submit($report);
            if ($errors !== []) {
                return redirect()
                    ->route('thesis-grades.edit', ['thesisGrade' => $report, 'step' => 3])
                    ->with('error', implode("\n", $errors))
                    ->with('submit_errors', $errors);
            }

            return redirect()
                ->route('thesis-grades.index', ['term' => $report->term, 'year' => $report->year])
                ->with('status', 'ส่งผลการเรียนเข้าสาขาแล้ว');
        }

        $step = (int) $request->input('step', $created ? 2 : 1);

        return redirect()
            ->route('thesis-grades.edit', ['thesisGrade' => $report, 'step' => max(1, min(3, $step))])
            ->with('status', $created ? 'บันทึกร่างแล้ว — อัปโหลดไฟล์ได้ที่ขั้นที่ 3' : 'บันทึกแล้ว');
    }

    private function formView(?ThesisGrade $report, array $defaults = []): View
    {
        $term = (int) ($report?->term ?? $defaults['term'] ?? AcademicTerm::defaultTerm());
        $year = (int) ($report?->year ?? $defaults['year'] ?? AcademicTerm::defaultYear());

        return view('thesis-grades.form', [
            'report' => $report,
            'term' => $term,
            'year' => $year,
            'years' => AcademicTerm::yearOptions(),
            'subjectChoices' => ThesisGradePdfParser::SUBJECT_CHOICES,
            'staffDisplayName' => $this->staffAuth->displayNameFor(
                auth()->user()->email,
                auth()->user()->name,
            ),
            'regUrl' => (string) config('scigrade.reg_url'),
            's0FormUrl' => (string) config('scigrade.s0_letter_form_url'),
            'step' => max(1, min(3, (int) request('step', $defaults['step'] ?? 1))),
            'editable' => $report === null || $report->isEditable(),
        ]);
    }

    private function staffUsername(): string
    {
        $username = session('staff_username');

        if (empty($username) && auth()->user()) {
            $staff = $this->staffAuth->findByEmail(auth()->user()->email);
            if ($staff) {
                $this->staffAuth->storeInSession($staff);
                $username = $staff->username;
            }
        }

        abort_if(empty($username), 403, 'ไม่พบข้อมูลบุคลากรในระบบ');

        return (string) $username;
    }
}
