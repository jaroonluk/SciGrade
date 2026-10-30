<?php

namespace App\Services\DeptAdmin;

use App\Enums\GradeApprovalStatus;
use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Services\AuditLogService;
use App\Services\GradeReportAttachmentNameService;
use App\Services\RegistrarGradePdfParser;
use App\Services\RegistrarPdfParseException;
use App\Support\UploadStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Throwable;

class DeptRegistrarBulkUploadService
{
    public function __construct(
        private readonly DepartmentSubjectFilter $subjectFilter,
        private readonly GradeReportAttachmentNameService $attachmentNames,
        private readonly AuditLogService $auditLog,
        private readonly RegistrarGradePdfParser $pdfParser,
    ) {}

    /**
     * @deprecated ใช้การอ่านเนื้อหา PDF แทน — คงไว้เพื่อความเข้ากันได้กับเทสต์/โค้ดเก่า
     *
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
     * พรีวิวจากไฟล์จริง — อ่านรหัสวิชา / Section / ภาคปี จากเนื้อหา PDF
     *
     * @param  list<UploadedFile>  $files
     * @param  list<int>  $departmentIds
     * @return list<array<string, mixed>>
     */
    public function preview(array $files, int $term, int $year, array $departmentIds): array
    {
        $results = [];

        foreach ($files as $file) {
            $results[] = $this->describeUploadedFile($file, $term, $year, $departmentIds);
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
    public function describeUploadedFile(UploadedFile $file, int $term, int $year, array $departmentIds): array
    {
        $originalName = $file->getClientOriginalName();
        $base = $this->emptyResult($originalName);
        $base['size'] = $file->getSize();

        $tmpPath = $file->getRealPath() ?: $file->getPathname();
        if (! is_string($tmpPath) || $tmpPath === '' || ! is_readable($tmpPath)) {
            $base['reason'] = 'อ่านไฟล์ไม่สำเร็จ — กรุณาเลือกไฟล์ PDF อีกครั้ง';
            $base['error_code'] = 'unreadable_file';

            return $base;
        }

        try {
            $parsed = $this->pdfParser->parse($tmpPath, $originalName, $term, $year);
        } catch (RegistrarPdfParseException $e) {
            $base['reason'] = $this->formatParseError($e->getMessage(), $originalName);
            $base['error_code'] = 'parse_failed';

            return $base;
        } catch (Throwable $e) {
            $base['reason'] = 'อ่านไฟล์ PDF ไม่สำเร็จ: '.$e->getMessage();
            $base['error_code'] = 'parse_failed';

            return $base;
        }

        return $this->describeFromParsed($parsed, $originalName, $term, $year, $departmentIds, $base);
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  list<int>  $departmentIds
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    private function describeFromParsed(
        array $parsed,
        string $originalName,
        int $term,
        int $year,
        array $departmentIds,
        array $base,
    ): array {
        $courseCode = Str::upper(trim((string) ($parsed['subject_code'] ?? '')));
        $subjectName = trim((string) ($parsed['subject'] ?? ''));
        $sectionInt = (int) ($parsed['grade_stds'][0]['sec'] ?? 0);
        $fileTerm = (int) ($parsed['term'] ?? $term);
        $fileYear = (int) ($parsed['year'] ?? $year);

        $base['course_code'] = $courseCode !== '' ? $courseCode : null;
        $base['section'] = $sectionInt > 0 ? str_pad((string) $sectionInt, 2, '0', STR_PAD_LEFT) : null;
        $base['section_int'] = $sectionInt > 0 ? $sectionInt : null;
        $base['file_term'] = $fileTerm;
        $base['file_year'] = $fileYear;
        $base['file_subject'] = $subjectName !== '' ? $subjectName : null;
        $base['parsed_from'] = 'pdf_content';

        if ($courseCode === '') {
            $base['reason'] = 'ไม่พบรหัสวิชาในไฟล์ PDF — ตรวจสอบว่าเป็นใบส่งผลการศึกษา (มข.11) จากสำนักทะเบียน';
            $base['error_code'] = 'missing_subject_code';

            return $base;
        }

        if ($sectionInt <= 0) {
            $base['reason'] = 'ไม่พบกลุ่มเรียน (Section) ในไฟล์ PDF ของรหัสวิชา '.$courseCode
                .' — เปิดไฟล์ตรวจว่ามีเลขกลุ่มเรียนครบถ้วน';
            $base['error_code'] = 'missing_section';

            return $base;
        }

        if ($fileTerm !== $term || $fileYear !== $year) {
            $base['reason'] = "ภาค/ปีการศึกษาในไฟล์ไม่ตรงกับที่เลือกบนหน้าจอ\n"
                .'ที่เลือกบนหน้าจอ: '.$this->termYearLabel($term, $year)."\n"
                .'ที่พบในไฟล์: '.$this->termYearLabel($fileTerm, $fileYear)."\n"
                .'รหัสวิชา '.$courseCode.' Section '.$base['section']
                ."\nกรุณาเปลี่ยนภาค/ปีบนหน้าจอให้ตรงกับไฟล์ หรือเลือกไฟล์ของภาค/ปีที่เลือก";
            $base['error_code'] = 'term_year_mismatch';

            return $base;
        }

        $match = $this->diagnoseMatch($courseCode, $sectionInt, $term, $year, $departmentIds);
        $report = $match['report'];

        if ($report === null) {
            $base['reason'] = $match['reason'];
            $base['error_code'] = $match['error_code'];
            $base['available_sections'] = $match['available_sections'];

            return $base;
        }

        $base['matched'] = true;
        $base['grade_id'] = $report->grade_id;
        $base['subject_code'] = $report->subject_code;
        $base['subject'] = $report->subject;
        $base['section_label'] = 'Sec'.$sectionInt;

        if (! $report->canDeptAttachRegistrar()) {
            $base['reason'] = $this->blockedReason($report);
            $base['error_code'] = 'status_blocked';

            return $base;
        }

        $base['ok'] = true;
        $base['reason'] = null;
        $base['error_code'] = null;

        return $base;
    }

    /**
     * @param  list<int>  $departmentIds
     * @return array<string, mixed>
     */
    private function uploadOne(UploadedFile $file, string $username, int $term, int $year, array $departmentIds): array
    {
        $described = $this->describeUploadedFile($file, $term, $year, $departmentIds);
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
            $described['error_code'] = $report ? 'status_blocked' : 'report_missing';

            return $described;
        }

        $storedPath = null;
        $sectionInt = (int) ($described['section_int'] ?? 0);
        $originalName = (string) ($described['original_name'] ?? $file->getClientOriginalName());

        try {
            if ($sectionInt > 0) {
                $this->deleteDeptRegistrarForSection($report, $sectionInt);
            }

            $displayName = $this->attachmentNames->generateDisplayName(
                $report,
                GradeReportFile::TYPE_REGISTRAR,
                $sectionInt > 0 ? $sectionInt : null,
            );
            $storedPath = $this->attachmentNames->storeUploadedFile(
                $report,
                $file,
                GradeReportFile::TYPE_REGISTRAR,
                $sectionInt > 0 ? $sectionInt : null,
            );

            if (! UploadStorage::disk()->exists($storedPath)) {
                $described['ok'] = false;
                $described['reason'] = 'อัปโหลดไป MinIO ไม่สำเร็จ (ไม่พบไฟล์บน disk หลังบันทึก)';
                $described['error_code'] = 'storage_missing';

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
                    'parsed_subject_code' => $described['course_code'] ?? null,
                    'parsed_section' => $sectionInt > 0 ? $sectionInt : null,
                ],
                actorUsername: $username,
                actorRole: 'dept_admin',
            );

            $described['ok'] = true;
            $described['reason'] = null;
            $described['error_code'] = null;
            $described['stored_name'] = $record->original_name;
            $described['file_id'] = $record->file_id;
            $described['section_label'] = $described['section_label'] ?? ($sectionInt > 0 ? 'Sec'.$sectionInt : null);
            $described['download_name'] = $record->deptRegistrarDownloadName($report);
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
            $described['error_code'] = 'storage_error';

            return $described;
        }
    }

    /**
     * @param  list<int>  $departmentIds
     * @return array{
     *     report: ?GradeReport,
     *     reason: string,
     *     error_code: string,
     *     available_sections: list<int>
     * }
     */
    private function diagnoseMatch(
        string $courseCode,
        int $section,
        int $term,
        int $year,
        array $departmentIds,
    ): array {
        $empty = [
            'report' => null,
            'reason' => '',
            'error_code' => 'not_found',
            'available_sections' => [],
        ];

        if ($departmentIds === []) {
            $empty['reason'] = 'ไม่มีสิทธิ์สาขาวิชาสำหรับอัปโหลด';
            $empty['error_code'] = 'no_department';

            return $empty;
        }

        $courseReports = $this->findCourseReports($courseCode, $term, $year, $departmentIds);
        if ($courseReports->isEmpty()) {
            $empty['reason'] = 'ไม่พบรายวิชา '.$courseCode.' ในสาขา/ภาคการศึกษาที่เลือก ('
                .$this->termYearLabel($term, $year).')'
                ."\nตรวจสอบว่าอาจารย์ส่งรายงานวิชานี้แล้ว และรหัสวิชาอยู่ในความดูแลของสาขาที่เลือก";
            $empty['error_code'] = 'course_not_found';

            return $empty;
        }

        $availableSections = [];
        foreach ($courseReports as $candidate) {
            foreach ($candidate->gradeStds as $std) {
                $sec = (int) $std->sec;
                if ($sec > 0) {
                    $availableSections[$sec] = $sec;
                }
            }
        }
        $availableSections = array_values($availableSections);
        sort($availableSections, SORT_NUMERIC);

        $report = $this->findMatchingReport($courseCode, $section, $term, $year, $departmentIds);
        if ($report !== null) {
            return [
                'report' => $report,
                'reason' => '',
                'error_code' => '',
                'available_sections' => $availableSections,
            ];
        }

        $secLabel = str_pad((string) $section, 2, '0', STR_PAD_LEFT);
        $availableLabel = $availableSections === []
            ? 'ไม่มี'
            : collect($availableSections)
                ->map(fn (int $sec) => str_pad((string) $sec, 2, '0', STR_PAD_LEFT))
                ->implode(', ');

        $empty['available_sections'] = $availableSections;
        $empty['reason'] = 'พบรหัสวิชา '.$courseCode.' แล้ว แต่ไม่พบ Section '.$secLabel
            .' ในรายงานของสาขา/ภาคการศึกษาที่เลือก'
            ."\nSection ที่มีในระบบ: ".$availableLabel
            ."\nกรุณาเลือกไฟล์ มข.11 ของ Section ที่ถูกต้อง";
        $empty['error_code'] = 'section_not_found';

        return $empty;
    }

    /**
     * @param  list<int>  $departmentIds
     * @return \Illuminate\Support\Collection<int, GradeReport>
     */
    private function findCourseReports(string $courseCode, int $term, int $year, array $departmentIds)
    {
        $query = GradeReport::query()
            ->examReportable()
            ->with('gradeStds')
            ->whereHas('gradeStds')
            ->where('term', (string) $term)
            ->where('year', (string) $year)
            ->where(function ($q) use ($courseCode): void {
                $q->whereRaw('UPPER(TRIM(subject_code)) = ?', [$courseCode])
                    ->orWhereRaw('UPPER(TRIM(IFNULL(subject_code2, ""))) = ?', [$courseCode]);
            });

        $this->subjectFilter->applyDepartmentsToQuery($query, $departmentIds);

        return $query->orderByDesc('grade_id')->get();
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
            ->examReportable()
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

    /**
     * @return array<string, mixed>
     */
    private function emptyResult(string $originalName): array
    {
        return [
            'original_name' => $originalName,
            'ok' => false,
            'matched' => false,
            'course_code' => null,
            'section' => null,
            'section_int' => null,
            'grade_id' => null,
            'subject_code' => null,
            'subject' => null,
            'reason' => null,
            'error_code' => null,
            'file_term' => null,
            'file_year' => null,
            'file_subject' => null,
            'parsed_from' => null,
            'available_sections' => [],
        ];
    }

    private function formatParseError(string $message, string $originalName): string
    {
        $message = trim($message);
        if ($message === '') {
            return 'อ่านไฟล์ «'.$originalName.'» ไม่สำเร็จ — ตรวจสอบว่าเป็นใบ มข.11 จากสำนักทะเบียน';
        }

        if (! str_contains($message, $originalName)) {
            $message .= ' (ไฟล์: '.$originalName.')';
        }

        return $message;
    }

    private function termYearLabel(int $term, int $year): string
    {
        $termLabel = match ($term) {
            1 => 'ภาคต้น',
            2 => 'ภาคปลาย',
            3 => 'ภาคการศึกษาพิเศษ',
            default => 'ภาคการศึกษาที่ '.$term,
        };

        return $termLabel.' ปีการศึกษา '.$year;
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
            return 'รายวิชาถูกส่งกลับแก้ไข ไม่สามารถอัปโหลดใบ มข.11 จากหน้านี้';
        }

        return 'ไม่สามารถอัปโหลดได้ในสถานะนี้';
    }

    /**
     * ลบไฟล์ มข.11 ของ Admin สาขาใน Section เดียวกันก่อนอัปโหลดใหม่
     */
    private function deleteDeptRegistrarForSection(GradeReport $report, int $section): int
    {
        if ($section <= 0) {
            return 0;
        }

        $report->loadMissing('gradeStds');
        $deleted = 0;

        $files = GradeReportFile::query()
            ->where('grade_id', $report->grade_id)
            ->ofType(GradeReportFile::TYPE_REGISTRAR)
            ->orderByDesc('file_id')
            ->get();

        foreach ($files as $file) {
            if (! $file->isDeptAdminUpload($report)) {
                continue;
            }
            $sec = $file->resolvedSection($report);
            if ($sec === null || (int) $sec !== $section) {
                continue;
            }
            $file->delete();
            $deleted++;
        }

        $report->unsetRelation('files');

        return $deleted;
    }

    /**
     * ลบไฟล์ มข.11 ของ Admin สาขาที่ซ้ำ Section เดียวกัน เหลือเฉพาะ file_id ล่าสุด
     */
    public function purgeDuplicateDeptRegistrarFiles(GradeReport $report): int
    {
        $report->loadMissing('gradeStds');
        $deleted = 0;

        $files = GradeReportFile::query()
            ->where('grade_id', $report->grade_id)
            ->ofType(GradeReportFile::TYPE_REGISTRAR)
            ->orderByDesc('file_id')
            ->get()
            ->filter(fn (GradeReportFile $file) => $file->isDeptAdminUpload($report));

        $seenSections = [];
        foreach ($files as $file) {
            $sec = $file->resolvedSection($report);
            if ($sec === null || $sec <= 0) {
                continue;
            }
            $key = (int) $sec;
            if (isset($seenSections[$key])) {
                $file->delete();
                $deleted++;
                continue;
            }
            $seenSections[$key] = true;
        }

        $report->unsetRelation('files');

        return $deleted;
    }
}
