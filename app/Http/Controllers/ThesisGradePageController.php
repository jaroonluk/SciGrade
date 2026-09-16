<?php

namespace App\Http\Controllers;

use App\Http\Requests\ThesisGrade\SaveThesisGradeRequest;
use App\Models\ThesisGrade;
use App\Models\ThesisGradeFile;
use App\Models\ThesisGradeStudent;
use App\Services\StaffAuthService;
use App\Services\ThesisGrade\PdfSignatureInspector;
use App\Services\ThesisGrade\ThesisGradeAttachmentNameService;
use App\Services\ThesisGrade\ThesisGradeDocxExportService;
use App\Services\ThesisGrade\ThesisGradePdfParseException;
use App\Services\ThesisGrade\ThesisGradePdfParser;
use App\Services\ThesisGrade\ThesisGradeService;
use App\Services\ThesisGrade\ThesisGradeZipService;
use App\Support\AcademicTerm;
use App\Support\ImageOnlyPdfMessage;
use App\Support\ThesisGradeS0Letter;
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
        private readonly ThesisGradeDocxExportService $docxExport,
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
            'file.required' => 'กรุณาเลือกไฟล์ PDF ก่อนอัปโหลด',
            'file.mimes' => 'อัปโหลดไม่ได้ เพราะรองรับเฉพาะไฟล์ PDF เท่านั้น',
            'file.max' => 'อัปโหลดไม่ได้ เพราะขนาดไฟล์เกิน 15 MB',
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
            $payload = $e->toUserPayload();
            if (ImageOnlyPdfMessage::matches((string) ($payload['message'] ?? ''))) {
                $payload = array_merge($payload, ImageOnlyPdfMessage::payload(), [
                    'index_url' => route('thesis-grades.index', [
                        'term' => $termFallback,
                        'year' => $yearFallback,
                    ]),
                    'draft_created' => false,
                ]);
            }
            if ($request->expectsJson()) {
                return response()->json($payload, 422);
            }

            if (! empty($payload['image_pdf'])) {
                return redirect()
                    ->route('thesis-grades.index', [
                        'term' => $termFallback,
                        'year' => $yearFallback,
                    ])
                    ->with('image_pdf_guide', true)
                    ->with('error', $payload['message']);
            }

            return back()
                ->with('error', $payload['message'])
                ->with('error_hint', $payload['hint']);
        } catch (Throwable $e) {
            report($e);
            $payload = [
                'ok' => false,
                'message' => 'อัปโหลดไม่สำเร็จ เพราะระบบประมวลผลไฟล์ PDF ไม่ได้ในขณะนี้',
                'reason' => 'unexpected_error',
                'hint' => 'กรุณาลองใหม่อีกครั้ง หรือกรอกข้อมูลด้วยตนเองในแบบฟอร์มด้านล่างแทน',
                'can_manual' => true,
            ];
            if ($request->expectsJson()) {
                return response()->json($payload, 422);
            }

            return back()
                ->with('error', $payload['message'])
                ->with('error_hint', $payload['hint']);
        }

        $signature = $this->signatures->inspectUploaded($uploaded);
        $username = $this->staffUsername();
        $teacher = $parsed['teacher']
            ?: $this->staffAuth->teacherNameFor(auth()->user()->email, auth()->user()->name);

        $isImagePdf = collect($parsed['warnings'] ?? [])->contains(
            fn ($w) => ImageOnlyPdfMessage::matches((string) $w)
        );

        // PDF แบบภาพ — ไม่สร้างฉบับร่าง ให้ผู้ใช้กลับไปอัปโหลดใบ มข.11 ที่ถูกต้อง
        if ($isImagePdf) {
            $indexUrl = route('thesis-grades.index', [
                'term' => (int) ($parsed['term'] ?? $termFallback),
                'year' => (int) ($parsed['year'] ?? $yearFallback),
            ]);
            $payload = array_merge(ImageOnlyPdfMessage::payload(), [
                'ok' => false,
                'draft_created' => false,
                'message' => ImageOnlyPdfMessage::TEXT,
                'index_url' => $indexUrl,
                'warnings' => $parsed['warnings'] ?? [],
            ]);

            if ($request->expectsJson()) {
                return response()->json($payload, 422);
            }

            return redirect($indexUrl)
                ->with('image_pdf_guide', true)
                ->with('error', ImageOnlyPdfMessage::TEXT);
        }

        // อ่านชนิดวิชาได้แล้ว แต่ยังไม่มีรหัส — ไม่สร้างร่าง ให้ผู้ใช้กรอกรหัสเองในฟอร์ม
        if (! empty($parsed['requires_manual_code']) || trim((string) $parsed['subject_code']) === '') {
            $payload = [
                'ok' => true,
                'draft_created' => false,
                'message' => 'อ่านชื่อวิชาเป็น '.$parsed['subject'].' จาก PDF แล้ว — กรุณากรอกรหัสวิชาเองแล้วบันทึกร่าง',
                'warnings' => $parsed['warnings'],
                'prefill' => [
                    'subject_code' => $parsed['subject_code'],
                    'subject' => $parsed['subject'],
                    'term' => $parsed['term'],
                    'year' => $parsed['year'],
                    'section' => $parsed['section'],
                    'students' => $parsed['students'],
                    'uncertain_fields' => $parsed['uncertain_fields'] ?? [],
                ],
                'uncertain_fields' => $parsed['uncertain_fields'] ?? [],
                'subject_in_catalog' => (bool) ($parsed['subject_in_catalog'] ?? false),
                'signature_signed' => $signature['signed'],
                'signature_message' => $signature['message'],
            ];

            if ($request->expectsJson()) {
                return response()->json($payload);
            }

            return back()
                ->withInput([
                    'term' => $parsed['term'],
                    'year' => $parsed['year'],
                    'subject_code' => $parsed['subject_code'],
                    'subject' => $parsed['subject'],
                    'section' => $parsed['section'],
                    'students' => $parsed['students'],
                ])
                ->with('status', $payload['message'])
                ->with('pdf_warnings', $parsed['warnings'])
                ->with('pdf_uncertain', [
                    'course' => $parsed['uncertain_fields'] ?? [],
                    'students' => array_map(
                        fn (array $s) => $s['uncertain_fields'] ?? [],
                        $parsed['students'] ?? []
                    ),
                ]);
        }

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
                return response()->json([
                    'ok' => false,
                    'message' => $e->getMessage(),
                    'hint' => 'กรุณาตรวจสอบรหัสวิชา ชื่อวิชา และข้อมูลอื่น แล้วกรอกเองในแบบฟอร์มด้านล่าง',
                    'can_manual' => true,
                    'prefill' => [
                        'subject_code' => $parsed['subject_code'],
                        'subject' => $parsed['subject'],
                        'term' => $parsed['term'],
                        'year' => $parsed['year'],
                        'section' => $parsed['section'],
                        'students' => $parsed['students'],
                        'uncertain_fields' => $parsed['uncertain_fields'] ?? [],
                    ],
                    'uncertain_fields' => $parsed['uncertain_fields'] ?? [],
                ], 422);
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
            $message = 'อ่านข้อมูลจากไฟล์สำเร็จแล้ว แต่เก็บไฟล์บนระบบจัดเก็บไม่สำเร็จ กรุณาลองใหม่อีกครั้ง หรือบันทึกร่างแล้วอัปโหลดไฟล์ในขั้นที่ 3';
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                    'reason' => 'storage_failed',
                    'hint' => 'ข้อมูลที่อ่านได้ยังอยู่ในร่างแล้ว — สามารถกรอก/แก้ไขต่อได้ แล้วลองอัปโหลดไฟล์อีกครั้งในขั้นที่ 3',
                    'can_manual' => true,
                    'edit_url' => route('thesis-grades.edit', ['thesisGrade' => $report, 'step' => 1]),
                ], 500);
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
            'draft_created' => true,
            'message' => ($parsed['subject_in_catalog'] ?? false)
                ? 'อัปโหลดและอ่านข้อมูลจาก PDF แล้ว (พบรหัสวิชาในฐานข้อมูล)'
                : 'อัปโหลดและอ่านข้อมูลจาก PDF แล้ว (ไม่พบรหัสวิชาในฐานข้อมูล — ใช้ค่าจากไฟล์ คุณแก้ไขได้)',
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
            'uncertain_fields' => $parsed['uncertain_fields'] ?? [],
            'subject_in_catalog' => (bool) ($parsed['subject_in_catalog'] ?? false),
            'signature_signed' => $signature['signed'],
            'signature_message' => $signature['message'],
            'stored_name' => basename($storedPath),
            'disk' => \App\Support\UploadStorage::diskName(),
            'image_pdf' => false,
        ];

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return redirect($editUrl)
            ->with('status', $payload['message'])
            ->with('pdf_warnings', $parsed['warnings'])
            ->with('pdf_uncertain', [
                'course' => $parsed['uncertain_fields'] ?? [],
                'students' => array_map(
                    fn (array $s) => $s['uncertain_fields'] ?? [],
                    $parsed['students'] ?? []
                ),
            ])
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

    /**
     * อ่านคอลัมน์ ลง / ผ่าน / หมายเหตุ จากไฟล์ใบส่งเกรดที่แนบไว้แล้ว แล้วเติมลงรายชื่อ
     */
    public function reparseTs(ThesisGrade $thesisGrade): RedirectResponse
    {
        $this->authorize('update', $thesisGrade);
        abort_unless($thesisGrade->isEditable(), 403);
        $thesisGrade->loadMissing('files', 'students');

        $file = $thesisGrade->files
            ->first(fn (ThesisGradeFile $f) => $f->resolvedType() === ThesisGradeFile::TYPE_TS_REPORT)
            ?? $thesisGrade->files()->where('file_type', ThesisGradeFile::TYPE_TS_REPORT)->orderByDesc('file_id')->first();

        if (! $file) {
            return redirect()
                ->route('thesis-grades.edit', ['thesisGrade' => $thesisGrade, 'step' => 2])
                ->with('error', 'ยังไม่มีไฟล์ใบส่งเกรดแนบไว้ — กรุณาอัปโหลดในขั้นที่ 1 หรือขั้นที่ 3 ก่อน');
        }

        $tmp = null;
        try {
            $disk = \App\Support\UploadStorage::diskFor($file->stored_path);
            if (! $disk->exists($file->stored_path)) {
                throw new RuntimeException('ไม่พบไฟล์ในระบบจัดเก็บ');
            }
            $tmp = tempnam(sys_get_temp_dir(), 'tspdf_');
            if ($tmp === false) {
                throw new RuntimeException('สร้างไฟล์ชั่วคราวไม่สำเร็จ');
            }
            file_put_contents($tmp, $disk->get($file->stored_path));

            $parsed = $this->pdfParser->parse(
                $tmp,
                (string) $file->original_name,
                (int) $thesisGrade->term,
                (int) $thesisGrade->year,
            );
            $updated = $this->thesisGrades->applyParsedCreditsToStudents($thesisGrade, $parsed['students'] ?? []);
        } catch (ThesisGradePdfParseException $e) {
            return redirect()
                ->route('thesis-grades.edit', ['thesisGrade' => $thesisGrade, 'step' => 2])
                ->with('error', $e->getMessage())
                ->with('error_hint', $e->hint !== '' ? $e->hint : null);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('thesis-grades.edit', ['thesisGrade' => $thesisGrade, 'step' => 2])
                ->with('error', 'อ่านไฟล์ใบส่งเกรดไม่สำเร็จ กรุณาลองอัปโหลดใหม่');
        } finally {
            if (is_string($tmp) && is_file($tmp)) {
                @unlink($tmp);
            }
        }

        return redirect()
            ->route('thesis-grades.edit', ['thesisGrade' => $thesisGrade, 'step' => 2])
            ->with('status', $updated > 0
                ? "อ่านจากใบส่งเกรดแล้ว — อัปเดตหน่วยกิต/หมายเหตุ {$updated} คน"
                : 'อ่านจากใบส่งเกรดแล้ว แต่ไม่พบหน่วยกิตที่จับคู่รหัสนักศึกษาได้')
            ->with('pdf_warnings', $parsed['warnings'] ?? [])
            ->with('pdf_uncertain', [
                'course' => $parsed['uncertain_fields'] ?? [],
                'students' => array_map(
                    fn (array $s) => $s['uncertain_fields'] ?? [],
                    $parsed['students'] ?? []
                ),
            ]);
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

        return $this->afterSubmitRedirect($thesisGrade);
    }

    public function destroy(ThesisGrade $thesisGrade): RedirectResponse
    {
        $this->authorize('delete', $thesisGrade);

        $term = $thesisGrade->term;
        $year = $thesisGrade->year;
        $thesisGrade->delete();

        return redirect()
            ->route('thesis-grades.index', ['term' => $term, 'year' => $year])
            ->with('status', 'ลบรายการเรียบร้อย');
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

    public function s0Letter(Request $request, ThesisGrade $thesisGrade, ?ThesisGradeStudent $student = null): View
    {
        $this->authorize('view', $thesisGrade);
        if ($student !== null) {
            abort_unless((int) $student->thesis_grade_id === (int) $thesisGrade->thesis_grade_id, 404);
        }

        $back = (string) $request->headers->get('referer', '');
        $appUrl = rtrim((string) config('app.url'), '/');
        $backUrl = $back !== '' && str_starts_with($back, $appUrl)
            ? $back
            : route('thesis-grades.edit', ['thesisGrade' => $thesisGrade, 'step' => 2]);

        return view('thesis-grades.s0-letter', [
            'fields' => ThesisGradeS0Letter::fields($thesisGrade, $student),
            'backUrl' => $backUrl,
            'officialFormUrl' => (string) config('scigrade.s0_letter_form_url'),
            'docxUrl' => $student
                ? route('thesis-grades.s0.docx.student', [$thesisGrade, $student])
                : route('thesis-grades.s0.docx', $thesisGrade),
        ]);
    }

    public function downloadS0Letter(ThesisGrade $thesisGrade, ?ThesisGradeStudent $student = null): BinaryFileResponse
    {
        $this->authorize('view', $thesisGrade);
        if ($student !== null) {
            abort_unless((int) $student->thesis_grade_id === (int) $thesisGrade->thesis_grade_id, 404);
        }

        return $this->docxExport->downloadS0Letter($thesisGrade, $student);
    }

    private function afterSubmitRedirect(ThesisGrade $report): RedirectResponse
    {
        return redirect()
            ->route('thesis-grades.index', ['term' => $report->term, 'year' => $report->year])
            ->with('status', 'ส่งผลการเรียนเข้าสาขาแล้ว — ติดตามสถานะจากรายการด้านล่าง หรือส่งผลวิชาต่อไปได้')
            ->with('thesis_submitted', [
                'code' => $report->displayCode(),
                'section' => $report->paddedSection(),
                'subject' => (string) $report->subject,
            ]);
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

            return $this->afterSubmitRedirect($report);
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
