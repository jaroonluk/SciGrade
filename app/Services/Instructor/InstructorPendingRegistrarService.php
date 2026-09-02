<?php

namespace App\Services\Instructor;

use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Services\AuditLogService;
use App\Services\GradeReportAttachmentNameService;
use App\Support\UploadStorage;
use Illuminate\Support\Str;
use Throwable;

class InstructorPendingRegistrarService
{
    public const SESSION_PATH = 'grade_upload_path';

    public const SESSION_NAME = 'grade_upload_name';

    public const SESSION_TERM = 'grade_upload_term';

    public const SESSION_YEAR = 'grade_upload_year';

    public const SESSION_SUBJECT = 'grade_upload_subject_code';

    public const SESSION_SECTION = 'grade_upload_section';

    public const SESSION_OWNER = 'grade_upload_owner';

    public function __construct(
        private readonly GradeReportAttachmentNameService $attachmentNames,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * แนบไฟล์ PDF ที่อัปโหลดจากหน้า upload เป็น REG ของอาจารย์ (ครั้งเดียวต่อ session)
     */
    public function attachFromSession(GradeReport $report, string $username, ?int $ownerId = null): ?GradeReportFile
    {
        $path = session(self::SESSION_PATH);
        $originalName = session(self::SESSION_NAME);

        if (! is_string($path) || $path === '' || ! is_string($originalName) || $originalName === '') {
            return null;
        }

        $ownerId ??= auth()->id();
        if ($ownerId !== null && session(self::SESSION_OWNER) !== $ownerId) {
            return null;
        }

        if (! $this->matchesReport($report)) {
            return null;
        }

        $section = $this->resolveSection($originalName);
        $disk = UploadStorage::disk();

        if (! $disk->exists($path)) {
            $this->forgetSession();

            return null;
        }

        try {
            $storedPath = $this->attachmentNames->storeFromStoragePath(
                $report,
                $path,
                GradeReportFile::TYPE_REGISTRAR,
                $section,
            );

            $record = GradeReportFile::query()->create([
                'grade_id' => $report->grade_id,
                'file_type' => GradeReportFile::TYPE_REGISTRAR,
                'original_name' => basename($storedPath),
                'stored_path' => $storedPath,
                'uploaded_at' => now(),
                'username' => $username,
            ]);

            $this->auditLog->record(
                'grade_report_file.upload',
                subjectType: 'grade_report_file',
                subjectId: $record->file_id,
                metadata: [
                    'grade_id' => $report->grade_id,
                    'file_type' => GradeReportFile::TYPE_REGISTRAR,
                    'original_name' => $record->original_name,
                    'source' => 'instructor_registrar_upload',
                    'client_name' => $originalName,
                ],
                actorUsername: $username,
                actorRole: 'instructor',
            );

            $disk->delete($path);
            $this->forgetSession();

            return $record;
        } catch (Throwable) {
            return null;
        }
    }

    private function matchesReport(GradeReport $report): bool
    {
        $sessionSubject = Str::upper(trim((string) session(self::SESSION_SUBJECT, '')));
        $reportSubject = Str::upper(trim((string) $report->subject_code));

        if ($sessionSubject === '' || $reportSubject === '' || $sessionSubject !== $reportSubject) {
            return false;
        }

        if ((int) session(self::SESSION_TERM) !== (int) $report->term) {
            return false;
        }

        if ((int) session(self::SESSION_YEAR) !== (int) $report->year) {
            return false;
        }

        return true;
    }

    private function resolveSection(string $originalName): ?int
    {
        $section = session(self::SESSION_SECTION);

        if (is_numeric($section)) {
            return (int) $section;
        }

        if (preg_match('/^[A-Z0-9]+-(\d{2})\.pdf$/i', $originalName, $match)) {
            return (int) $match[1];
        }

        return null;
    }

    private function forgetSession(): void
    {
        session()->forget([
            self::SESSION_PATH,
            self::SESSION_NAME,
            self::SESSION_TERM,
            self::SESSION_YEAR,
            self::SESSION_SUBJECT,
            self::SESSION_SECTION,
            self::SESSION_OWNER,
        ]);
    }
}
