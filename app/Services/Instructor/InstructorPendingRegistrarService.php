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

    public const SESSION_QUEUE = 'grade_upload_queue';

    public function __construct(
        private readonly GradeReportAttachmentNameService $attachmentNames,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * เก็บไฟล์ PDF ที่ parse แล้วไว้ใน session เพื่อแนบเป็น REG ตอนบันทึกรายงาน
     * (แทนที่รายการเดิมถ้ากลุ่มเรียนซ้ำ)
     *
     * @param  array{path: string, name: string, term: int, year: int, subject_code: string, section: int|null, owner: int|null}  $item
     */
    public function remember(array $item): void
    {
        $queue = session(self::SESSION_QUEUE, []);
        if (! is_array($queue)) {
            $queue = [];
        }

        $section = $item['section'] ?? null;
        if ($section !== null) {
            $queue = array_values(array_filter(
                $queue,
                fn ($row) => (int) ($row['section'] ?? -1) !== (int) $section
            ));
        }

        $queue[] = [
            'path' => $item['path'],
            'name' => $item['name'],
            'term' => (int) $item['term'],
            'year' => (int) $item['year'],
            'subject_code' => Str::upper(trim((string) $item['subject_code'])),
            'section' => $section !== null ? (int) $section : null,
            'owner' => $item['owner'],
        ];

        session([
            self::SESSION_QUEUE => $queue,
            self::SESSION_PATH => $item['path'],
            self::SESSION_NAME => $item['name'],
            self::SESSION_TERM => (int) $item['term'],
            self::SESSION_YEAR => (int) $item['year'],
            self::SESSION_SUBJECT => Str::upper(trim((string) $item['subject_code'])),
            self::SESSION_SECTION => $section !== null ? (int) $section : null,
            self::SESSION_OWNER => $item['owner'],
        ]);
    }

    /**
     * แนบไฟล์ PDF ที่อัปโหลดไว้ใน session เป็น REG ของอาจารย์
     *
     * @return list<GradeReportFile>
     */
    public function attachFromSession(GradeReport $report, string $username, ?int $ownerId = null): array
    {
        $ownerId ??= auth()->id();
        if ($ownerId !== null && session(self::SESSION_OWNER) !== null && session(self::SESSION_OWNER) !== $ownerId) {
            return [];
        }

        $items = $this->pendingItems();
        if ($items === []) {
            return [];
        }

        $attached = [];
        $remaining = [];
        $disk = UploadStorage::disk();

        foreach ($items as $item) {
            if (! $this->itemMatchesReport($item, $report)) {
                $remaining[] = $item;
                continue;
            }

            $path = (string) ($item['path'] ?? '');
            if ($path === '' || ! $disk->exists($path)) {
                continue;
            }

            try {
                $section = isset($item['section']) && is_numeric($item['section'])
                    ? (int) $item['section']
                    : $this->sectionFromName((string) ($item['name'] ?? ''));

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
                        'client_name' => $item['name'] ?? null,
                    ],
                    actorUsername: $username,
                    actorRole: 'instructor',
                );

                $disk->delete($path);
                $attached[] = $record;
            } catch (Throwable) {
                $remaining[] = $item;
            }
        }

        if ($remaining === []) {
            $this->forgetSession();
        } else {
            $first = $remaining[0];
            session([
                self::SESSION_QUEUE => array_values($remaining),
                self::SESSION_PATH => $first['path'] ?? null,
                self::SESSION_NAME => $first['name'] ?? null,
                self::SESSION_TERM => $first['term'] ?? null,
                self::SESSION_YEAR => $first['year'] ?? null,
                self::SESSION_SUBJECT => $first['subject_code'] ?? null,
                self::SESSION_SECTION => $first['section'] ?? null,
                self::SESSION_OWNER => $first['owner'] ?? session(self::SESSION_OWNER),
            ]);
        }

        return $attached;
    }

    public function hasPending(): bool
    {
        return $this->pendingItems() !== [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pendingItems(): array
    {
        $queue = session(self::SESSION_QUEUE, []);
        if (is_array($queue) && $queue !== []) {
            return array_values($queue);
        }

        $path = session(self::SESSION_PATH);
        $name = session(self::SESSION_NAME);
        if (! is_string($path) || $path === '' || ! is_string($name) || $name === '') {
            return [];
        }

        return [[
            'path' => $path,
            'name' => $name,
            'term' => (int) session(self::SESSION_TERM),
            'year' => (int) session(self::SESSION_YEAR),
            'subject_code' => (string) session(self::SESSION_SUBJECT, ''),
            'section' => session(self::SESSION_SECTION),
            'owner' => session(self::SESSION_OWNER),
        ]];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function itemMatchesReport(array $item, GradeReport $report): bool
    {
        $sessionSubject = Str::upper(trim((string) ($item['subject_code'] ?? '')));
        $reportSubject = Str::upper(trim((string) $report->subject_code));

        if ($sessionSubject === '' || $reportSubject === '' || $sessionSubject !== $reportSubject) {
            return false;
        }

        if ((int) ($item['term'] ?? 0) !== (int) $report->term) {
            return false;
        }

        if ((int) ($item['year'] ?? 0) !== (int) $report->year) {
            return false;
        }

        return true;
    }

    private function sectionFromName(string $originalName): ?int
    {
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
            self::SESSION_QUEUE,
        ]);
    }
}
