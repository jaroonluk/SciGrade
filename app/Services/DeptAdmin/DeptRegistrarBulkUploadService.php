<?php

namespace App\Services\DeptAdmin;

use App\Enums\GradeApprovalStatus;
use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Services\AuditLogService;
use App\Services\GradeReportAttachmentNameService;
use App\Support\UploadStorage;
use Illuminate\Http\UploadedFile;
use Throwable;

class DeptRegistrarBulkUploadService
{
    public function __construct(
        private readonly DepartmentSubjectFilter $subjectFilter,
        private readonly GradeReportAttachmentNameService $attachmentNames,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * @return array{course_code: string, section: string, section_int: int}|null
     */
    public function parseFilename(string $filename): ?array
    {
        $base = basename(str_replace('\\', '/', $filename));

        if (! preg_match('/^([A-Za-z0-9]+)-(\d{2})\.pdf$/i', $base, $match)) {
            return null;
        }

        return [
            'course_code' => strtoupper($match[1]),
            'section' => str_pad($match[2], 2, '0', STR_PAD_LEFT),
            'section_int' => (int) $match[2],
        ];
    }

    /**
     * @param  list<string>  $filenames
     * @param  list<int>  $departmentIds
     * @return list<array<string, mixed>>
     */
    public function preview(array $filenames, int $term, int $year, array $departmentIds): array
    {
        $results = [];

        foreach ($filenames as $filename) {
            $results[] = $this->describeMatch((string) $filename, $term, $year, $departmentIds);
        }

        return $results;
    }

    /**
     * @param  list<UploadedFile>  $files
     * @param  list<int>  $departmentIds
     * @return list<array<string, mixed>>
     */
    public function upload(array $files, string $username, int $term, int $year, array $departmentIds): array
    {
        $results = [];

        foreach ($files as $file) {
            $results[] = $this->uploadOne($file, $username, $term, $year, $departmentIds);
        }

        return $results;
    }

    /**
     * @param  list<int>  $departmentIds
     * @return array<string, mixed>
     */
    public function describeMatch(string $filename, int $term, int $year, array $departmentIds): array
    {
        $parsed = $this->parseFilename($filename);
        $base = [
            'original_name' => $filename,
            'ok' => false,
            'matched' => false,
            'course_code' => $parsed['course_code'] ?? null,
            'section' => $parsed['section'] ?? null,
            'grade_id' => null,
            'subject_code' => null,
            'subject' => null,
            'reason' => null,
        ];

        if ($parsed === null) {
            $base['reason'] = 'ชื่อไม่ตรงรูปแบบ (รหัสวิชา-กลุ่ม.pdf เช่น SC101011-01.pdf)';

            return $base;
        }

        $report = $this->findMatchingReport(
            $parsed['course_code'],
            $parsed['section_int'],
            $term,
            $year,
            $departmentIds,
        );

        if ($report === null) {
            $base['reason'] = 'ไม่พบรายวิชานี้ในสาขา/ภาคการศึกษาที่เลือก';

            return $base;
        }

        $base['matched'] = true;
        $base['grade_id'] = $report->grade_id;
        $base['subject_code'] = $report->subject_code;
        $base['subject'] = $report->subject;

        if (! $report->canDeptAttachRegistrar()) {
            $base['reason'] = $this->blockedReason($report);

            return $base;
        }

        $base['ok'] = true;

        return $base;
    }

    /**
     * @param  list<int>  $departmentIds
     * @return array<string, mixed>
     */
    private function uploadOne(UploadedFile $file, string $username, int $term, int $year, array $departmentIds): array
    {
        $originalName = $file->getClientOriginalName();
        $described = $this->describeMatch($originalName, $term, $year, $departmentIds);
        $described['size'] = $file->getSize();
        $described['view_url'] = null;
        $described['stored_name'] = null;

        if (! $described['ok'] || ! $described['grade_id']) {
            return $described;
        }

        $report = GradeReport::query()
            ->with('gradeStds')
            ->find($described['grade_id']);

        if ($report === null || ! $report->canDeptAttachRegistrar()) {
            $described['ok'] = false;
            $described['reason'] = $report ? $this->blockedReason($report) : 'ไม่พบรายวิชาที่จับคู่';

            return $described;
        }

        $storedPath = null;

        try {
            $displayName = $this->attachmentNames->generateDisplayName($report, GradeReportFile::TYPE_REGISTRAR);
            $storedPath = $this->attachmentNames->storeUploadedFile($report, $file, GradeReportFile::TYPE_REGISTRAR);

            if (! UploadStorage::disk()->exists($storedPath)) {
                $described['ok'] = false;
                $described['reason'] = 'อัปโหลดไป MinIO ไม่สำเร็จ (ไม่พบไฟล์บน disk หลังบันทึก)';

                return $described;
            }

            $record = GradeReportFile::query()->create([
                'grade_id' => $report->grade_id,
                'file_type' => GradeReportFile::TYPE_REGISTRAR,
                'original_name' => basename($storedPath) ?: $displayName,
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
                    'source' => 'dept_bulk_registrar',
                    'client_name' => $originalName,
                ],
                actorUsername: $username,
                actorRole: 'dept_admin',
            );

            $described['ok'] = true;
            $described['reason'] = null;
            $described['stored_name'] = $record->original_name;
            $described['view_url'] = route('grade-reports.files.show', [
                'gradeReport' => $report->grade_id,
                'file' => $record->file_id,
            ]);

            return $described;
        } catch (Throwable $e) {
            if (is_string($storedPath) && $storedPath !== '') {
                try {
                    UploadStorage::disk()->delete($storedPath);
                } catch (Throwable) {
                    // ignore cleanup failure
                }
            }

            $described['ok'] = false;
            $described['reason'] = 'MinIO/ระบบจัดเก็บไฟล์ผิดพลาด: '.$e->getMessage();

            return $described;
        }
    }

    /**
     * @param  list<int>  $departmentIds
     */
    public function findMatchingReport(
        string $courseCode,
        int $section,
        int $term,
        int $year,
        array $departmentIds,
    ): ?GradeReport {
        if ($departmentIds === []) {
            return null;
        }

        $paddedSection = str_pad((string) $section, 2, '0', STR_PAD_LEFT);

        $query = GradeReport::query()
            ->with('gradeStds')
            ->whereHas('gradeStds', function ($q) use ($section, $paddedSection): void {
                $q->where('sec', $section)
                    ->orWhere('sec', $paddedSection)
                    ->orWhere('sec', (string) $section);
            })
            ->where('term', (string) $term)
            ->where('year', (string) $year)
            ->where(function ($q) use ($courseCode): void {
                $q->whereRaw('UPPER(TRIM(subject_code)) = ?', [$courseCode])
                    ->orWhereRaw('UPPER(TRIM(IFNULL(subject_code2, ""))) = ?', [$courseCode]);
            });

        $this->subjectFilter->applyDepartmentsToQuery($query, $departmentIds);

        return $query->orderByDesc('grade_id')->first();
    }

    private function blockedReason(GradeReport $report): string
    {
        $status = (int) $report->approv;

        if (in_array($status, [
            GradeApprovalStatus::FacultyChecked->value,
            GradeApprovalStatus::CentralApproved->value,
        ], true)) {
            return 'คณะรับไปแล้ว ไม่สามารถอัปโหลดได้';
        }

        if ($status === GradeApprovalStatus::DepartmentRejected->value) {
            return 'รายวิชาถูกส่งกลับแก้ไข ไม่สามารถอัปโหลดใบ REG จากหน้านี้';
        }

        return 'ไม่สามารถอัปโหลดได้ในสถานะนี้';
    }
}
