<?php

namespace App\Http\Controllers;

use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Services\AuditLogService;
use App\Services\GradeReportAttachmentNameService;
use App\Services\Instructor\InstructorPendingRegistrarService;
use App\Services\Instructor\RegistrarSectionRequirement;
use App\Services\StaffAuthService;
use App\Support\SciGradeRole;
use App\Support\UploadStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradeReportFileController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly GradeReportAttachmentNameService $attachmentNames,
        private readonly AuditLogService $auditLog,
        private readonly InstructorPendingRegistrarService $pendingRegistrar,
    ) {}

    public function store(Request $request, GradeReport $gradeReport): JsonResponse
    {
        abort_unless($this->ownsReport($gradeReport) || $this->canContribute($gradeReport), 403);

        if (! $gradeReport->canUploadFiles()) {
            return response()->json([
                'message' => 'ไม่สามารถอัปโหลดไฟล์ได้ เนื่องจากรายงานผ่านการอนุมัติแล้ว กรุณารอเจ้าหน้าที่คืนสถานะเป็นรออนุมัติ',
            ], 422);
        }

        $request->validate([
            'attachment' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'file_type' => ['nullable', 'string', Rule::in(GradeReportFile::allowedTypes())],
        ], [
            'attachment.mimes' => 'รองรับเฉพาะไฟล์ PDF',
            'attachment.required' => 'กรุณาเลือกไฟล์ PDF',
            'file_type.in' => 'ประเภทไฟล์ไม่ถูกต้อง',
        ]);

        $fileType = (string) ($request->input('file_type') ?: GradeReportFile::TYPE_EXAM_REPORT);

        $uploaded = $request->file('attachment');
        $displayName = $this->attachmentNames->generateDisplayName($gradeReport, $fileType);
        $storedPath = $this->attachmentNames->storeUploadedFile($gradeReport, $uploaded, $fileType);

        $file = GradeReportFile::query()->create([
            'grade_id' => $gradeReport->grade_id,
            'file_type' => $fileType,
            'original_name' => basename($storedPath) ?: $displayName,
            'stored_path' => $storedPath,
            'uploaded_at' => now(),
            'username' => $this->staffUsername(),
        ]);

        $this->auditLog->record(
            'grade_report_file.upload',
            subjectType: 'grade_report_file',
            subjectId: $file->file_id,
            metadata: [
                'grade_id' => $gradeReport->grade_id,
                'file_type' => $fileType,
                'original_name' => $file->original_name,
            ],
        );

        return response()->json($this->formatFile($file), 201);
    }

    /**
     * แนบไฟล์ REG ที่ค้างใน session + ใบขวาง เฉพาะตอนทำ wizard ครบทุกขั้นตอน
     */
    public function finalizeWizard(Request $request, GradeReport $gradeReport): JsonResponse
    {
        abort_unless($this->ownsReport($gradeReport) || $this->canContribute($gradeReport), 403);

        if (! $gradeReport->canUploadFiles()) {
            return response()->json([
                'message' => 'ไม่สามารถแนบไฟล์ได้ เนื่องจากรายงานผ่านการอนุมัติแล้ว',
            ], 422);
        }

        $request->validate([
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'file_type' => ['nullable', 'string', Rule::in(GradeReportFile::allowedTypes())],
            'required_sections' => ['nullable', 'array'],
            'required_sections.*' => ['integer', 'min:1', 'max:50'],
        ], [
            'attachment.mimes' => 'รองรับเฉพาะไฟล์ PDF',
        ]);

        $username = $this->staffUsername();
        $pendingSections = array_keys($this->pendingRegistrar->pendingBySection(
            (string) $gradeReport->subject_code,
            (int) $gradeReport->term,
            (int) $gradeReport->year,
        ));
        $requestedSections = $request->input('required_sections', []);
        if (! is_array($requestedSections)) {
            $requestedSections = $requestedSections !== null && $requestedSections !== ''
                ? [$requestedSections]
                : [];
        }
        $registrarFiles = $this->pendingRegistrar->attachFromSession($gradeReport, $username);

        $gradeReport->loadMissing('gradeStds');
        $mySectionNums = [];
        foreach ($gradeReport->gradeStds as $std) {
            $sec = (int) $std->sec;
            if ($sec <= 0) {
                continue;
            }
            $stdUsername = trim((string) ($std->getAttributes()['username'] ?? $std->username ?? ''));
            if ($stdUsername === '') {
                $stdUsername = trim((string) $gradeReport->username);
            }
            if ($stdUsername !== '' && $stdUsername === $username) {
                $mySectionNums[$sec] = $sec;
            }
        }
        $mySectionNums = array_values($mySectionNums);
        sort($mySectionNums);

        $examFile = null;
        if ($request->hasFile('attachment')) {
            $fileType = (string) ($request->input('file_type') ?: GradeReportFile::TYPE_EXAM_REPORT);
            $uploaded = $request->file('attachment');
            $sectionOverride = $mySectionNums[0] ?? null;

            $displayName = $this->attachmentNames->generateDisplayName($gradeReport, $fileType, $sectionOverride);
            $storedPath = $this->attachmentNames->storeUploadedFile($gradeReport, $uploaded, $fileType, $sectionOverride);

            $examFile = GradeReportFile::query()->create([
                'grade_id' => $gradeReport->grade_id,
                'file_type' => $fileType,
                'original_name' => basename($storedPath) ?: $displayName,
                'stored_path' => $storedPath,
                'uploaded_at' => now(),
                'username' => $username,
            ]);

            $this->auditLog->record(
                'grade_report_file.upload',
                subjectType: 'grade_report_file',
                subjectId: $examFile->file_id,
                metadata: [
                    'grade_id' => $gradeReport->grade_id,
                    'file_type' => $fileType,
                    'original_name' => $examFile->original_name,
                    'source' => 'wizard_finalize',
                    'covers_sections' => $mySectionNums,
                ],
            );
        }

        $gradeReport->load(['gradeStds', 'files']);
        $hasRegistrar = $gradeReport->files->contains(
            fn (GradeReportFile $file) => $file->resolvedType() === GradeReportFile::TYPE_REGISTRAR
        ) || $registrarFiles !== [];
        $hasExam = $gradeReport->files->contains(
            fn (GradeReportFile $file) => $file->resolvedType() === GradeReportFile::TYPE_EXAM_REPORT
        ) || $examFile !== null;

        // Section ของผู้ใช้ปัจจุบันที่ยังไม่มีใบขวางครอบคลุม
        $examCoveredByUploader = [];
        foreach ($gradeReport->files as $file) {
            if ($file->resolvedType() !== GradeReportFile::TYPE_EXAM_REPORT) {
                continue;
            }
            $uploader = trim((string) ($file->username ?? ''));
            if ($uploader === '' || $uploader !== $username) {
                continue;
            }
            foreach ($mySectionNums as $sec) {
                $examCoveredByUploader[$sec] = true;
            }
        }
        if ($examFile !== null) {
            foreach ($mySectionNums as $sec) {
                $examCoveredByUploader[$sec] = true;
            }
        }
        $myMissingExam = array_values(array_filter(
            $mySectionNums,
            fn (int $sec) => ! isset($examCoveredByUploader[$sec]),
        ));

        $reportSections = $gradeReport->gradeStds
            ->map(fn ($row) => (int) $row->sec)
            ->filter(fn ($sec) => $sec > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $neededSections = RegistrarSectionRequirement::needed(
            $reportSections,
            is_array($requestedSections) ? $requestedSections : [],
            $pendingSections,
        );

        $haveSections = [];
        foreach ($gradeReport->files as $file) {
            if ($file->resolvedType() !== GradeReportFile::TYPE_REGISTRAR) {
                continue;
            }
            $sec = $file->resolvedSection($gradeReport);
            if ($sec !== null && (int) $sec > 0) {
                $haveSections[(int) $sec] = true;
            }
        }
        foreach ($registrarFiles as $file) {
            $sec = $file->resolvedSection($gradeReport);
            if ($sec !== null && (int) $sec > 0) {
                $haveSections[(int) $sec] = true;
            }
        }

        $missingSections = array_values(array_filter(
            $neededSections,
            fn (int $sec) => ! isset($haveSections[$sec]),
        ));

        if ($missingSections !== []) {
            $label = implode(', ', $missingSections);

            return response()->json([
                'message' => "ยังแนบแบบฟอร์ม มข.11 ไม่ครบทุก Section (ขาด Section {$label})",
                'hint' => 'กรุณาย้อนกลับไปขั้นตอนที่ 6 อัปโหลดไฟล์ มข.11 ของ Section ที่กำลังกรอกในรอบนี้ แล้วกดเสร็จสิ้นอีกครั้ง',
                'missing_sections' => $missingSections,
                'required_sections' => $neededSections,
                'has_registrar' => $hasRegistrar,
                'has_exam' => $hasExam,
            ], 422);
        }

        $needsMyExam = $mySectionNums !== [] ? $myMissingExam !== [] : ! $hasExam;
        if (! $hasRegistrar || $needsMyExam) {
            $missing = [];
            if (! $hasRegistrar) {
                $missing[] = 'แบบฟอร์ม มข.11 (ใบส่งผลการศึกษา)';
            }
            if ($needsMyExam) {
                $missing[] = $myMissingExam !== []
                    ? 'ใบรายงานผลการสอบไล่ (ใบขวาง) สำหรับ Section '.implode(', ', $myMissingExam)
                    : 'ใบรายงานผลการสอบไล่ (ใบขวาง)';
            }

            return response()->json([
                'message' => 'ยังแนบไฟล์ไม่ครบ: '.implode(' และ ', $missing),
                'hint' => 'เลือกแบบฟอร์ม มข.11 ในขั้นตอนที่ 6 และใบขวางในขั้นตอนที่ 8 สำหรับ Section ที่คุณกรอก แล้วกดเสร็จสิ้น',
                'registrar_attached' => count($registrarFiles),
                'exam_attached' => $examFile !== null,
                'has_registrar' => $hasRegistrar,
                'has_exam' => $hasExam,
                'missing_exam_sections' => $myMissingExam,
            ], 422);
        }

        $this->auditLog->record(
            'grade_report.wizard_finalize',
            subjectType: 'grade_report',
            subjectId: $gradeReport->grade_id,
            metadata: [
                'registrar_attached' => count($registrarFiles),
                'exam_attached' => $examFile !== null,
            ],
        );

        $examForResponse = $examFile ?? $gradeReport->files->first(
            function (GradeReportFile $file) use ($username) {
                if ($file->resolvedType() !== GradeReportFile::TYPE_EXAM_REPORT) {
                    return false;
                }
                $uploader = trim((string) ($file->username ?? ''));

                return $uploader === '' || $uploader === $username;
            }
        );
        $registrarForResponse = $registrarFiles !== []
            ? $registrarFiles
            : $gradeReport->files
                ->filter(fn (GradeReportFile $file) => $file->resolvedType() === GradeReportFile::TYPE_REGISTRAR)
                ->values()
                ->all();

        return response()->json([
            'message' => 'อัปโหลดแบบฟอร์ม มข.11 และใบรายงานผลการสอบไล่ (ใบขวาง) เข้าสู่ระบบเรียบร้อยแล้ว',
            'registrar_attached' => count($registrarFiles),
            'exam_attached' => $examFile !== null,
            'has_registrar' => true,
            'has_exam' => true,
            'exam_file' => $examForResponse ? $this->formatFile($examForResponse) : null,
            'registrar_files' => array_map(fn (GradeReportFile $f) => $this->formatFile($f), $registrarForResponse),
        ]);
    }

    public function show(Request $request, GradeReport $gradeReport, GradeReportFile $file): StreamedResponse
    {
        abort_unless($this->canViewFiles($gradeReport), 403);
        abort_unless((int) $file->grade_id === (int) $gradeReport->grade_id, 404);

        $gradeReport->loadMissing('gradeStds');
        $file->setRelation('gradeReport', $gradeReport);
        $downloadName = $file->downloadBasename($gradeReport);

        $this->auditLog->record(
            'grade_report_file.view',
            subjectType: 'grade_report_file',
            subjectId: $file->file_id,
            metadata: [
                'grade_id' => $gradeReport->grade_id,
                'file_type' => $file->resolvedType(),
                'original_name' => $file->original_name,
                'download_name' => $downloadName,
            ],
        );

        return UploadStorage::inlineResponse($file->stored_path, $downloadName, 'application/pdf');
    }

    public function destroy(Request $request, GradeReport $gradeReport, GradeReportFile $file): JsonResponse
    {
        abort_unless(
            $this->ownsReport($gradeReport)
            || trim((string) ($file->username ?? '')) === $this->staffUsername(),
            403,
            'ไม่มีสิทธิ์ลบไฟล์ของผู้อื่น'
        );
        abort_unless((int) $file->grade_id === (int) $gradeReport->grade_id, 404);

        if (! $gradeReport->canUploadFiles()) {
            return response()->json([
                'message' => 'ไม่สามารถลบไฟล์ได้ เนื่องจากรายงานผ่านการอนุมัติแล้ว',
            ], 422);
        }

        $meta = [
            'grade_id' => $gradeReport->grade_id,
            'file_type' => $file->resolvedType(),
            'original_name' => $file->original_name,
        ];
        $fileId = $file->file_id;

        $file->delete();

        $this->auditLog->record(
            'grade_report_file.delete',
            subjectType: 'grade_report_file',
            subjectId: $fileId,
            metadata: $meta,
        );

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatFile(GradeReportFile $file): array
    {
        $file->loadMissing('gradeReport.gradeStds');

        $displayName = $file->isRegistrar()
            ? $file->registrarDisplayName($file->gradeReport)
            : (string) $file->original_name;

        return [
            'file_id' => $file->file_id,
            'grade_id' => $file->grade_id,
            'file_type' => $file->resolvedType(),
            'type_label' => $file->typeLabel(),
            'original_name' => $file->original_name,
            'display_name' => $displayName,
            'uploaded_at' => $file->uploaded_at?->format('Y-m-d H:i'),
            'view_url' => route('grade-reports.files.show', [
                'gradeReport' => $file->grade_id,
                'file' => $file->file_id,
            ]),
        ];
    }

    private function ownsReport(GradeReport $gradeReport): bool
    {
        return $gradeReport->username === $this->staffUsername();
    }

    private function canContribute(GradeReport $gradeReport): bool
    {
        return (int) $gradeReport->approv <= 0 && ! $gradeReport->awaitingDeptResubmit();
    }

    private function canViewFiles(GradeReport $gradeReport): bool
    {
        if ($this->ownsReport($gradeReport)) {
            return true;
        }

        $role = session('scigrade_role', 'instructor');

        return $role === 'dept_admin' || SciGradeRole::isFacultyCapable($role);
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

        abort_unless($username, 403, 'ไม่พบข้อมูลผู้ใช้งาน');

        return (string) $username;
    }
}
