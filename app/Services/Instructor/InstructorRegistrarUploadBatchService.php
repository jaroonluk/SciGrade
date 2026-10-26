<?php

namespace App\Services\Instructor;

use App\Models\GradeReport;
use App\Models\GradeReportReg;
use App\Models\GradReport2;
use App\Services\RegistrarGradePdfParser;
use App\Services\RegistrarPdfParseException;
use App\Support\UploadStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Throwable;

class InstructorRegistrarUploadBatchService
{
    public const MAX_FILES = 20;

    public function __construct(
        private readonly RegistrarGradePdfParser $pdfParser,
        private readonly InstructorPendingRegistrarService $pendingRegistrar,
    ) {}

    /**
     * อ่านหลายไฟล์ PDF ใบส่งผล — ต้องเป็นวิชาเดียวกัน
     * ไฟล์ซ้ำ (เนื้อหาหรือกลุ่มเรียนเดียวกัน) อ่านเพียงไฟล์แรก
     * Section ที่มีข้อมูลในระบบแล้วจะข้ามและแจ้งผู้ใช้
     * ไฟล์ของวิชา/ภาค/ปีเดิมจะสะสมต่อใน session (ไม่ล้างของเดิม)
     *
     * @param  list<UploadedFile>  $files
     * @return array{
     *     merged: array<string, mixed>|null,
     *     accepted: list<array{name: string, section: int|null, subject_code: string}>,
     *     duplicates: list<array{name: string, duplicate_of: string, reason: string}>,
     *     already_exists: list<array{name: string, section: int, subject_code: string, reason: string}>,
     * }
     *
     * @throws RegistrarPdfParseException
     * @throws \InvalidArgumentException
     */
    public function process(array $files, int $term, int $year, ?int $ownerId): array
    {
        if ($files === []) {
            throw new \InvalidArgumentException('กรุณาเลือกไฟล์ PDF อย่างน้อย 1 ไฟล์');
        }

        if (count($files) > self::MAX_FILES) {
            throw new \InvalidArgumentException('อัปโหลดได้สูงสุด '.self::MAX_FILES.' ไฟล์ต่อครั้ง');
        }

        /** @var list<array{file: UploadedFile, parsed: array<string, mixed>, hash: string, name: string, section: int, subject: string}> $parsedRows */
        $parsedRows = [];
        $subjectCodes = [];
        /** @var list<string> $termYearMismatches */
        $termYearMismatches = [];

        foreach ($files as $uploaded) {
            $name = $uploaded->getClientOriginalName();
            $tmpPath = $uploaded->getRealPath() ?: $uploaded->getPathname();

            if (! is_string($tmpPath) || $tmpPath === '' || ! is_readable($tmpPath)) {
                throw new RegistrarPdfParseException($this->pdfParser->invalidFormatMessage().' (ไฟล์: '.$name.')');
            }

            try {
                $parsed = $this->pdfParser->parse($tmpPath, $name, $term, $year);
            } catch (RegistrarPdfParseException $e) {
                $message = $e->getMessage();
                if (! str_contains($message, $name)) {
                    $message .= ' (ไฟล์: '.$name.')';
                }
                throw new RegistrarPdfParseException($message, previous: $e);
            }

            $subject = Str::upper(trim((string) ($parsed['subject_code'] ?? '')));
            if ($subject === '') {
                throw new RegistrarPdfParseException(
                    'ไม่พบรหัสวิชาในไฟล์ «'.$name.'» — ตรวจสอบว่าเป็นใบส่งผลการศึกษาจากสำนักทะเบียน'
                );
            }

            $fileTerm = (int) ($parsed['term'] ?? $term);
            $fileYear = (int) ($parsed['year'] ?? $year);
            if ($fileTerm !== $term || $fileYear !== $year) {
                $termYearMismatches[] = '• '.$name.' → '.$this->termYearLabel($fileTerm, $fileYear);
            }

            $section = (int) ($parsed['grade_stds'][0]['sec'] ?? 0);
            $hash = hash_file('sha256', $tmpPath) ?: (uniqid('file_', true));

            $parsedRows[] = [
                'file' => $uploaded,
                'parsed' => $parsed,
                'hash' => $hash,
                'name' => $name,
                'section' => $section,
                'subject' => $subject,
            ];
            $subjectCodes[$subject] = true;
        }

        if ($termYearMismatches !== []) {
            throw new \InvalidArgumentException(
                "ไฟล์ มข.11 ไม่ตรงกับภาคการศึกษาและปีการศึกษาที่เลือกในหน้าจอ\n"
                ."กรุณาเลือกไฟล์ มข.11 ให้ตรงกับภาคการศึกษาและปีการศึกษาที่เลือก\n\n"
                .'ที่เลือกบนหน้าจอ: '.$this->termYearLabel($term, $year)."\n"
                ."พบในไฟล์:\n"
                .implode("\n", $termYearMismatches)
            );
        }

        if (count($subjectCodes) > 1) {
            $lines = [];
            foreach ($parsedRows as $row) {
                $lines[] = '• '.$row['name'].' → รหัสวิชา '.$row['subject'];
            }
            throw new \InvalidArgumentException(
                "ไฟล์ที่เลือกไม่ใช่วิชาเดียวกัน กรุณาอัปโหลดเฉพาะไฟล์ของรายวิชาเดียว\n\n"
                ."พบรหัสวิชาจากไฟล์ มข.11 ดังนี้:\n"
                .implode("\n", $lines)
                ."\n\nกรุณาตรวจสอบไฟล์ มข.11 ของท่าน แล้วอัปโหลดเฉพาะไฟล์ที่เป็นรหัสวิชาเดียวกันอีกครั้ง"
            );
        }

        $accepted = [];
        $duplicates = [];
        $alreadyExists = [];
        $seenHashes = [];
        $seenSections = [];

        $subjectCode = (string) array_key_first($subjectCodes);
        $existingSections = $this->existingSectionsForCourse($subjectCode, $term, $year);

        foreach ($parsedRows as $row) {
            if (isset($seenHashes[$row['hash']])) {
                $duplicates[] = [
                    'name' => $row['name'],
                    'duplicate_of' => $seenHashes[$row['hash']],
                    'reason' => 'เนื้อหาไฟล์ซ้ำกับ «'.$seenHashes[$row['hash']].'»',
                ];
                continue;
            }

            if ($row['section'] > 0 && isset($seenSections[$row['section']])) {
                $duplicates[] = [
                    'name' => $row['name'],
                    'duplicate_of' => $seenSections[$row['section']],
                    'reason' => 'กลุ่มเรียน Sec '.str_pad((string) $row['section'], 2, '0', STR_PAD_LEFT)
                        .' ซ้ำกับ «'.$seenSections[$row['section']].'»',
                ];
                continue;
            }

            if ($row['section'] > 0 && isset($existingSections[$row['section']])) {
                $secLabel = str_pad((string) $row['section'], 2, '0', STR_PAD_LEFT);
                $alreadyExists[] = [
                    'name' => $row['name'],
                    'section' => $row['section'],
                    'subject_code' => $row['subject'],
                    'reason' => "Section {$secLabel} มีข้อมูลในระบบแล้ว ไม่ต้องอัปโหลดหรือกรอกเพิ่มสำหรับกลุ่มเรียนนี้",
                ];
                continue;
            }

            $seenHashes[$row['hash']] = $row['name'];
            if ($row['section'] > 0) {
                $seenSections[$row['section']] = $row['name'];
            }
            $accepted[] = $row;
        }

        if ($accepted === [] && $alreadyExists !== []) {
            return [
                'merged' => null,
                'accepted' => [],
                'duplicates' => $duplicates,
                'already_exists' => $alreadyExists,
            ];
        }

        if ($accepted === []) {
            throw new \InvalidArgumentException('ไฟล์ทั้งหมดซ้ำกัน กรุณาเลือกไฟล์ที่แตกต่างกัน');
        }

        $this->resetPendingIfCourseChanged($subjectCode, $term, $year);

        $merged = null;
        $gradeStds = [];
        $acceptedMeta = [];

        foreach ($accepted as $row) {
            $parsed = $row['parsed'];
            $section = $row['section'] > 0 ? $row['section'] : 1;
            $canonicalName = $this->pdfParser->canonicalFilename(
                (string) ($parsed['subject_code'] ?? 'SUBJECT'),
                $section,
            );

            $path = $row['file']->store('grade-uploads/'.$ownerId, UploadStorage::diskName());

            $this->pendingRegistrar->remember([
                'path' => $path,
                'name' => $canonicalName,
                'term' => (int) ($parsed['term'] ?? $term),
                'year' => (int) ($parsed['year'] ?? $year),
                'subject_code' => (string) ($parsed['subject_code'] ?? ''),
                'section' => $row['section'] > 0 ? $row['section'] : null,
                'owner' => $ownerId,
            ]);

            if ($merged === null) {
                $merged = $parsed;
            }

            foreach ($parsed['grade_stds'] ?? [] as $std) {
                $gradeStds[] = $std;
            }

            $acceptedMeta[] = [
                'name' => $row['name'],
                'section' => $row['section'] > 0 ? $row['section'] : null,
                'subject_code' => $row['subject'],
            ];
        }

        usort($gradeStds, fn ($a, $b) => ((int) ($a['sec'] ?? 0)) <=> ((int) ($b['sec'] ?? 0)));
        $merged['grade_stds'] = $gradeStds;

        $iStudents = [];
        $seenStudentCodes = [];
        foreach ($accepted as $row) {
            foreach ($row['parsed']['grade_i_students'] ?? [] as $student) {
                $code = trim((string) ($student['student_code'] ?? ''));
                $key = $code !== '' ? $code : (string) ($student['name'] ?? '').'|'.($student['section'] ?? '');
                if ($key === '' || isset($seenStudentCodes[$key])) {
                    continue;
                }
                $seenStudentCodes[$key] = true;
                $iStudents[] = [
                    'name' => (string) ($student['name'] ?? ''),
                    'student_code' => $code,
                    'section' => isset($student['section']) ? (int) $student['section'] : null,
                ];
            }
        }
        $merged['grade_i_students'] = $iStudents;

        return [
            'merged' => $merged,
            'accepted' => $acceptedMeta,
            'duplicates' => $duplicates,
            'already_exists' => $alreadyExists,
        ];
    }

    /**
     * รวมผล parse จากรอบก่อนกับรอบใหม่ (แทนที่ grade_std ตาม Section ที่อัปโหลดซ้ำ)
     *
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    public function mergeParsed(array $base, array $incoming): array
    {
        $merged = array_merge($base, $incoming);

        $stdBySec = [];
        foreach (array_merge($base['grade_stds'] ?? [], $incoming['grade_stds'] ?? []) as $std) {
            if (! is_array($std)) {
                continue;
            }
            $sec = (int) ($std['sec'] ?? 0);
            if ($sec <= 0) {
                continue;
            }
            $stdBySec[$sec] = $std;
        }
        ksort($stdBySec, SORT_NUMERIC);
        $merged['grade_stds'] = array_values($stdBySec);

        $iStudents = [];
        $seenStudentCodes = [];
        foreach (array_merge($base['grade_i_students'] ?? [], $incoming['grade_i_students'] ?? []) as $student) {
            if (! is_array($student)) {
                continue;
            }
            $code = trim((string) ($student['student_code'] ?? ''));
            $key = $code !== '' ? $code : (string) ($student['name'] ?? '').'|'.($student['section'] ?? '');
            if ($key === '' || isset($seenStudentCodes[$key])) {
                continue;
            }
            $seenStudentCodes[$key] = true;
            $iStudents[] = [
                'name' => (string) ($student['name'] ?? ''),
                'student_code' => $code,
                'section' => isset($student['section']) ? (int) $student['section'] : null,
            ];
        }
        $merged['grade_i_students'] = $iStudents;

        return $merged;
    }

    /**
     * Section ที่ต้องแนบ มข.11 จากรายการ REG หักกลุ่มที่มีในระบบแล้ว
     *
     * @return array{
     *     gated: bool,
     *     complete: bool,
     *     subject_code: string,
     *     subject_name: string,
     *     term: int,
     *     year: int,
     *     term_label: string,
     *     from_reg: bool,
     *     required_sections: list<int>,
     *     required_count: int,
     *     attached_sections: list<int>,
     *     attached_count: int,
     *     missing_sections: list<int>,
     *     missing_count: int,
     *     existing_sections: list<int>,
     *     pending_details: list<array{section: int, name: string, view_url: string}>,
     * }
     */
    public function sectionProgress(
        string $subjectCode,
        int $term,
        int $year,
        ?string $subjectName = null,
    ): array {
        $code = GradReport2::normalizeCode($subjectCode);
        $reg = $this->regSectionsForCourse($code, $term, $year);
        $existingMap = $this->existingSectionsForCourse($code, $term, $year);
        $existing = array_keys($existingMap);
        sort($existing, SORT_NUMERIC);

        $pendingMap = $this->pendingRegistrar->pendingBySection($code, $term, $year);
        $pendingDetails = [];
        foreach ($pendingMap as $sec => $row) {
            $pendingDetails[] = [
                'section' => (int) $sec,
                'name' => (string) ($row['name'] ?? ''),
                'view_url' => route('grade-reports.pending-registrar.show', ['section' => (int) $sec]),
            ];
        }

        $name = trim((string) ($subjectName ?: $reg['subject_name'] ?: ''));
        $termLabel = $this->termYearLabel($term, $year);

        if (! $reg['from_reg']) {
            $attached = array_keys($pendingMap);
            sort($attached, SORT_NUMERIC);

            return [
                'gated' => false,
                'complete' => $attached !== [],
                'subject_code' => $code,
                'subject_name' => $name,
                'term' => $term,
                'year' => $year,
                'term_label' => $termLabel,
                'from_reg' => false,
                'required_sections' => $attached,
                'required_count' => count($attached),
                'attached_sections' => $attached,
                'attached_count' => count($attached),
                'missing_sections' => [],
                'missing_count' => 0,
                'existing_sections' => $existing,
                'pending_details' => $pendingDetails,
            ];
        }

        $required = array_values(array_filter(
            $reg['sections'],
            fn (int $sec) => ! isset($existingMap[$sec]),
        ));
        $attached = array_values(array_filter(
            $required,
            fn (int $sec) => isset($pendingMap[$sec]),
        ));
        $missing = array_values(array_diff($required, $attached));

        return [
            'gated' => true,
            'complete' => $required === [] || $missing === [],
            'subject_code' => $code,
            'subject_name' => $name,
            'term' => $term,
            'year' => $year,
            'term_label' => $termLabel,
            'from_reg' => true,
            'required_sections' => $required,
            'required_count' => count($required),
            'attached_sections' => $attached,
            'attached_count' => count($attached),
            'missing_sections' => $missing,
            'missing_count' => count($missing),
            'existing_sections' => $existing,
            'pending_details' => $pendingDetails,
        ];
    }

    /**
     * Section ที่เปิดสอนจริงจาก grade_report_reg
     *
     * @return array{sections: list<int>, from_reg: bool, subject_name: string}
     */
    public function regSectionsForCourse(string $subjectCode, int $term, int $year): array
    {
        $code = GradReport2::normalizeCode($subjectCode);
        if ($code === '' || $term < 1 || $year < 1) {
            return ['sections' => [], 'from_reg' => false, 'subject_name' => ''];
        }

        try {
            $rows = GradeReportReg::query()
                ->whereRaw(
                    "UPPER(REPLACE(REPLACE(TRIM(`COURSECODE`), ' ', ''), UNHEX('C2A0'), '')) = ?",
                    [$code],
                )
                ->where(function ($query) use ($year) {
                    $query->where('ACADYEAR', (string) $year)
                        ->orWhere('ACADYEAR', $year)
                        ->orWhereRaw('CAST(ACADYEAR AS UNSIGNED) = ?', [$year]);
                })
                ->where(function ($query) use ($term) {
                    $query->where('SEMESTER', (string) $term)
                        ->orWhere('SEMESTER', $term)
                        ->orWhereRaw('CAST(SEMESTER AS UNSIGNED) = ?', [$term])
                        ->orWhere('SEMESTER', str_pad((string) $term, 2, '0', STR_PAD_LEFT));
                })
                ->get(['SECTION', 'COURSENAMEENG']);
        } catch (Throwable) {
            return ['sections' => [], 'from_reg' => false, 'subject_name' => ''];
        }

        if ($rows->isEmpty()) {
            return ['sections' => [], 'from_reg' => false, 'subject_name' => ''];
        }

        $sections = [];
        $subjectName = '';
        foreach ($rows as $row) {
            $digits = preg_replace('/\D+/', '', (string) ($row->SECTION ?? '')) ?? '';
            $sec = (int) $digits;
            if ($sec > 0) {
                $sections[$sec] = $sec;
            }
            if ($subjectName === '') {
                $subjectName = trim((string) ($row->COURSENAMEENG ?? ''));
            }
        }

        $values = array_values($sections);
        sort($values, SORT_NUMERIC);

        return [
            'sections' => $values,
            'from_reg' => $values !== [],
            'subject_name' => $subjectName,
        ];
    }

    /**
     * Section ที่มีข้อมูลจำนวนนักศึกษาในรายงานวิชานี้แล้ว (ภาค/ปีเดียวกัน)
     *
     * @return array<int, true>
     */
    public function existingSectionsForCourse(string $subjectCode, int $term, int $year): array
    {
        $code = GradReport2::normalizeCode($subjectCode);
        if ($code === '' || $term < 1 || $year < 1) {
            return [];
        }

        $report = GradeReport::query()
            ->examReportable()
            ->whereRaw(GradReport2::normalizedCodeSql('subject_code').' = ?', [$code])
            ->where('term', (string) $term)
            ->where('year', (string) $year)
            ->orderBy('created_stamp')
            ->orderBy('grade_id')
            ->first();

        if ($report === null) {
            return [];
        }

        $secs = [];
        foreach ($report->gradeStds()->pluck('sec') as $sec) {
            $n = (int) $sec;
            if ($n > 0) {
                $secs[$n] = true;
            }
        }

        return $secs;
    }

    public function termYearLabel(int $term, int $year): string
    {
        $termLabel = match ($term) {
            1 => 'ภาคต้น',
            2 => 'ภาคปลาย',
            3 => 'ภาคการศึกษาพิเศษ',
            default => 'ภาคการศึกษาที่ '.$term,
        };

        return $termLabel.' ปีการศึกษา '.$year;
    }

    /**
     * ถ้าคิว pending เป็นวิชา/ภาค/ปีอื่น ให้ล้างก่อนสะสมของใหม่
     */
    private function resetPendingIfCourseChanged(string $subjectCode, int $term, int $year): void
    {
        $items = $this->pendingRegistrar->pendingItems();
        if ($items === []) {
            return;
        }

        $code = GradReport2::normalizeCode($subjectCode);
        foreach ($items as $item) {
            $itemSubject = GradReport2::normalizeCode((string) ($item['subject_code'] ?? ''));
            $sameCourse = $itemSubject !== '' && $itemSubject === $code
                && (int) ($item['term'] ?? 0) === $term
                && (int) ($item['year'] ?? 0) === $year;
            if (! $sameCourse) {
                $this->pendingRegistrar->forgetAll();

                return;
            }
        }
    }
}
