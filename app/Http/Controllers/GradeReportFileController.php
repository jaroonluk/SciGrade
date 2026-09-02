<?php

namespace App\Http\Controllers;

use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Services\AuditLogService;
use App\Services\GradeReportAttachmentNameService;
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
    ) {}

    public function store(Request $request, GradeReport $gradeReport): JsonResponse
    {
        abort_unless($this->ownsReport($gradeReport), 403);

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

    public function show(Request $request, GradeReport $gradeReport, GradeReportFile $file): StreamedResponse
    {
        abort_unless($this->canViewFiles($gradeReport), 403);
        abort_unless((int) $file->grade_id === (int) $gradeReport->grade_id, 404);

        $downloadName = $file->original_name;
        if ($file->isDeptAdminUpload($gradeReport)) {
            $gradeReport->loadMissing('gradeStds');
            $downloadName = $file->deptRegistrarDownloadName($gradeReport);
        }

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
        abort_unless($this->ownsReport($gradeReport), 403);
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
