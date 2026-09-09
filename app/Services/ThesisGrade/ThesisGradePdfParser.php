<?php

namespace App\Services\ThesisGrade;

use App\Models\PdCourse;
use App\Support\ThesisCourse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Smalot\PdfParser\Parser;
use Throwable;

class ThesisGradePdfParseException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $reason = 'unknown',
        public readonly string $hint = '',
    ) {
        parent::__construct($message);
    }

    /**
     * @return array{ok: false, message: string, reason: string, hint: string, can_manual: true}
     */
    public function toUserPayload(): array
    {
        return [
            'ok' => false,
            'message' => $this->getMessage(),
            'reason' => $this->reason,
            'hint' => $this->hint !== ''
                ? $this->hint
                : 'กรุณากรอกรหัสวิชา ชื่อวิชา ภาคการศึกษา ปีการศึกษา กลุ่มเรียน และรายชื่อนักศึกษาด้วยตนเองในแบบฟอร์มด้านล่างแทน',
            'can_manual' => true,
        ];
    }
}

class ThesisGradePdfParser
{
    public const SUBJECT_CHOICES = [
        'THESIS',
        'INDEPENDENT STUDY',
        'DISSERTATION',
    ];

    private const MANUAL_HINT = 'กรุณากรอกรหัสวิชา ชื่อวิชา ภาคการศึกษา ปีการศึกษา กลุ่มเรียน และรายชื่อนักศึกษาด้วยตนเองในแบบฟอร์มด้านล่างแทน';

    public function __construct(
        private readonly Parser $parser = new Parser,
        private readonly ?RegStudentDirectory $students = null,
    ) {}

    /**
     * @return array{
     *     subject_code: string,
     *     subject: string,
     *     term: int,
     *     year: int,
     *     section: string,
     *     teacher: ?string,
     *     students: list<array<string, mixed>>,
     *     warnings: list<string>
     * }
     */
    public function parse(string $absolutePath, string $originalFilename, int $termFallback, int $yearFallback): array
    {
        if ($absolutePath === '' || ! is_readable($absolutePath)) {
            throw new ThesisGradePdfParseException(
                'อัปโหลดไม่สำเร็จ เพราะเปิดไฟล์ PDF ไม่ได้ ไฟล์อาจเสียหายหรืออัปโหลดไม่สมบูรณ์',
                'unreadable_file',
                self::MANUAL_HINT,
            );
        }

        $previousMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            try {
                $document = $this->parser->parseFile($absolutePath);
                $text = $document->getText();
                $tableRows = $this->extractStudentTableRows($document);
            } catch (\Throwable $e) {
                Log::warning('Thesis TS PDF extract failed', [
                    'filename' => $originalFilename,
                    'error' => $e->getMessage(),
                ]);
                throw new ThesisGradePdfParseException(
                    'อัปโหลดได้แล้ว แต่ระบบอ่านข้อความจากไฟล์ไม่ได้ อาจเป็นไฟล์เสียหาย หรือเป็นไฟล์สแกนภาพที่ไม่มีข้อความฝังอยู่',
                    'extract_failed',
                    self::MANUAL_HINT,
                );
            }
        } finally {
            if ($previousMemoryLimit !== false) {
                ini_set('memory_limit', (string) $previousMemoryLimit);
            }
        }

        $parsed = $this->parseText((string) $text, $originalFilename, $termFallback, $yearFallback);

        return $this->applyStudentTableRows($parsed, $tableRows ?? []);
    }

    /**
     * @return array{
     *     subject_code: string,
     *     subject: string,
     *     term: int,
     *     year: int,
     *     section: string,
     *     teacher: ?string,
     *     students: list<array<string, mixed>>,
     *     warnings: list<string>,
     *     subject_in_catalog: bool,
     *     requires_manual_code: bool
     * }
     */
    public function parseText(string $rawText, string $originalFilename, int $termFallback, int $yearFallback): array
    {
        $text = $this->normalizeText($rawText);
        $warnings = [];

        if ($text === '' || mb_strlen(preg_replace('/\s+/u', '', $text) ?? '') < 20) {
            throw new ThesisGradePdfParseException(
                'อัปโหลดได้แล้ว แต่ในไฟล์ไม่มีข้อความให้อ่าน อาจเป็นไฟล์สแกนภาพหรือไฟล์ที่พิมพ์เป็นรูปภาพ',
                'empty_text',
                self::MANUAL_HINT,
            );
        }

        // อ่านจากเนื้อหาในไฟล์เป็นหลัก ชื่อไฟล์เป็นเพียงข้อมูลเสริม (ตั้งชื่ออะไรก็ได้)
        $fromName = $this->parseFilename($originalFilename);
        $uncertain = [];

        $subjectCode = '';
        $subjectRaw = '';
        $subjectCodeFromContent = false;
        if (preg_match('/\b([A-Z]{2}\d{5,8})\s*:\s*([^\n\r]+)/iu', $text, $m)) {
            $subjectCode = strtoupper(trim($m[1]));
            $subjectRaw = trim($m[2]);
            $subjectCodeFromContent = true;
        } elseif (preg_match('/\b([A-Z]{2}\d{5,8})\b/iu', $text, $m)) {
            $subjectCode = strtoupper(trim($m[1]));
            $subjectCodeFromContent = true;
        } elseif (! empty($fromName['subject_code'])) {
            $subjectCode = $fromName['subject_code'];
            $uncertain['subject_code'] = 'ใช้รหัสจากชื่อไฟล์ — กรุณาตรวจสอบหรือกรอกเอง';
            $warnings[] = 'ใช้รหัสวิชาจากชื่อไฟล์ เพราะไม่พบรูปแบบรหัสในเนื้อหา PDF — กรุณาตรวจสอบอีกครั้ง';
        }

        $subject = $this->normalizeSubjectChoice($subjectRaw !== '' ? $subjectRaw : null);
        $subjectFromContent = $subject !== null;
        if ($subject === null) {
            $subject = $this->normalizeSubjectChoice($text);
            $subjectFromContent = $subject !== null;
        }

        $requiresManualCode = false;
        if ($subjectCode === '') {
            if ($subject !== null) {
                $requiresManualCode = true;
                $uncertain['subject_code'] = 'ไม่พบรหัสวิชาในไฟล์ — กรุณากรอกเอง';
                $warnings[] = 'อ่านชื่อวิชาเป็น '.$subject.' ได้แล้ว แต่ไม่พบรหัสวิชาในไฟล์ — กรุณากรอกรหัสวิชาเองด้านล่าง';
            } else {
                throw new ThesisGradePdfParseException(
                    'ระบบอ่านข้อความจากไฟล์ได้ แต่ไม่พบรหัสวิชาและชนิดวิชาในใบส่งผลการเรียน กรุณาตรวจสอบว่าเป็นใบ มข.11 / TS จากระบบ REG',
                    'missing_subject_code',
                    self::MANUAL_HINT,
                );
            }
        }

        $subjectInCatalog = false;
        if ($subjectCode !== '') {
            $catalog = $this->lookupCatalog($subjectCode);
            if ($catalog !== null) {
                $subjectInCatalog = true;
                if ($catalog['subject_choice'] !== null) {
                    $subject = $catalog['subject_choice'];
                    $subjectFromContent = true;
                }
            } else {
                $uncertain['subject_code'] = $uncertain['subject_code']
                    ?? 'ไม่พบรหัสวิชานี้ในฐานข้อมูล — กรุณาตรวจสอบ';
                $warnings[] = 'ไม่พบรหัสวิชา '.$subjectCode.' ในฐานข้อมูลรายวิชา — ใช้ค่าที่อ่านจาก PDF แล้ว คุณสามารถแก้ไขรหัสหรือชื่อวิชาได้เอง';
            }
        }

        if ($subject === null) {
            $subject = 'THESIS';
            $uncertain['subject'] = 'ระบบเดาชื่อวิชาเป็น THESIS — กรุณาเลือกเองให้ถูกต้อง';
            $warnings[] = 'ระบบจับชนิดวิชาจากไฟล์ไม่ได้ จึงตั้งเป็น THESIS ชั่วคราว — กรุณาเลือกชื่อวิชาให้ถูกต้อง';
        } elseif (! $subjectFromContent && ! $subjectInCatalog) {
            $uncertain['subject'] = 'ชื่อวิชาอาจไม่ถูกต้อง — กรุณาตรวจสอบ';
        }

        $term = $termFallback;
        $year = $yearFallback;
        if (preg_match('/ภาคการศึกษาที่\s*(\d+)\s*\/\s*(\d{4})/u', $text, $tm)) {
            $term = (int) $tm[1];
            $year = (int) $tm[2];
        } elseif (! empty($fromName['term']) && ! empty($fromName['year'])) {
            $term = (int) $fromName['term'];
            $year = (int) $fromName['year'];
            $uncertain['term'] = 'ใช้ภาค/ปีจากชื่อไฟล์ — กรุณาตรวจสอบ';
            $uncertain['year'] = 'ใช้ภาค/ปีจากชื่อไฟล์ — กรุณาตรวจสอบ';
            $warnings[] = 'ใช้ภาค/ปีจากชื่อไฟล์ เพราะไม่พบในเนื้อหา PDF — กรุณาตรวจสอบอีกครั้ง';
        } else {
            $uncertain['term'] = 'ไม่พบภาคในไฟล์ — กรุณาเลือกเอง';
            $uncertain['year'] = 'ไม่พบปีการศึกษาในไฟล์ — กรุณาเลือกเอง';
            $warnings[] = 'ไม่พบภาคการศึกษา/ปีการศึกษาในไฟล์ จึงใช้ค่าที่เลือกไว้ในแบบฟอร์ม — กรุณาตรวจสอบอีกครั้ง';
        }

        $section = null;
        $sectionFromContent = false;
        if (preg_match('/กลุ่ม(?:เรียน)?\s*[:：]?\s*(\d{1,2})/u', $text, $sm)) {
            $section = str_pad((string) ((int) $sm[1]), 2, '0', STR_PAD_LEFT);
            $sectionFromContent = true;
        } elseif (preg_match('/Sec(?:tion)?\s*[:：]?\s*(\d{1,2})/iu', $text, $sm)) {
            $section = str_pad((string) ((int) $sm[1]), 2, '0', STR_PAD_LEFT);
            $sectionFromContent = true;
        } elseif (! empty($fromName['section'])) {
            $section = $fromName['section'];
            $uncertain['section'] = 'ใช้กลุ่มจากชื่อไฟล์ — กรุณาตรวจสอบ';
            $warnings[] = 'ใช้กลุ่มเรียนจากชื่อไฟล์ เพราะไม่พบในเนื้อหา PDF — กรุณาตรวจสอบอีกครั้ง';
        }

        if ($section === null) {
            $section = '01';
            $uncertain['section'] = 'ไม่พบกลุ่มเรียน — กรุณากรอกเอง';
            $warnings[] = 'ไม่พบกลุ่มเรียนในไฟล์ จึงตั้งเป็น 01 ชั่วคราว — กรุณาแก้ไขหากไม่ถูกต้อง';
        } else {
            $section = str_pad((string) ((int) preg_replace('/\D/', '', (string) $section) ?: 1), 2, '0', STR_PAD_LEFT);
            if (! $sectionFromContent && empty($uncertain['section'])) {
                $uncertain['section'] = 'กลุ่มเรียนอาจไม่ถูกต้อง — กรุณาตรวจสอบ';
            }
        }

        $teacher = null;
        if (preg_match('/(?:รศ\.|ผศ\.|ศ\.|อ\.|ดร\.|Asst\.|Assoc\.|Prof\.)[^\n\t]+/u', $text, $teach)) {
            $teacher = trim(preg_replace('/\s+/', ' ', $teach[0]) ?? '');
            $teacher = trim(preg_replace('/\s*กลุ่ม\s*\d+.*$/u', '', $teacher) ?? '');
            if (str_contains($teacher, '(')) {
                $teacher = trim(explode('(', $teacher, 2)[0]);
            }
        }

        $students = $this->parseStudents($text, $fromName['student_names'] ?? []);
        if ($students === []) {
            $warnings[] = 'ไม่พบรายชื่อนักศึกษาในไฟล์ — กรุณาเพิ่มรายชื่อในขั้นตอนถัดไปด้วยตนเอง';
        } elseif ($this->looksGarbled($text)) {
            $warnings[] = 'ข้อความใน PDF อ่านได้ไม่สมบูรณ์ (มักเกิดจากไฟล์ที่พิมพ์ผ่าน PDF printer) — ระบบดึงรหัสนักศึกษาจากเอกสารแล้ว กรุณาตรวจชื่อ-สกุลให้ถูกต้อง';
        }

        // ปรับระดับตามชนิดวิชาที่สรุปได้
        if ($subject === 'DISSERTATION') {
            foreach ($students as &$student) {
                $student['degree'] = 'doctoral';
            }
            unset($student);
        }

        // หน่วยกิตที่ยังอ่านจาก PDF ไม่ได้ — ให้ผู้ใช้กรอก
        foreach ($students as &$student) {
            $studentUncertain = is_array($student['uncertain_fields'] ?? null) ? $student['uncertain_fields'] : [];
            if ($student['credits_registered'] === null || $student['credits_registered'] === '') {
                $studentUncertain['credits_registered'] = 'ไม่พบหน่วยกิตที่ลงในไฟล์ — กรุณากรอกเอง';
            }
            if ($student['credits_passed'] === null || $student['credits_passed'] === '') {
                $studentUncertain['credits_passed'] = 'ไม่พบหน่วยกิตที่ผ่านในไฟล์ — กรุณากรอกเอง';
            }
            $student['uncertain_fields'] = $studentUncertain;
        }
        unset($student);

        if (! $subjectCodeFromContent && $subjectCode !== '' && empty($uncertain['subject_code'])) {
            $uncertain['subject_code'] = 'รหัสวิชาไม่ได้มาจากเนื้อหา PDF โดยตรง — กรุณาตรวจสอบ';
        }

        return [
            'subject_code' => $subjectCode,
            'subject' => $subject,
            'term' => $term,
            'year' => $year,
            'section' => $section,
            'teacher' => $teacher !== '' ? $teacher : null,
            'students' => $students,
            'warnings' => array_values(array_unique($warnings)),
            'subject_in_catalog' => $subjectInCatalog,
            'requires_manual_code' => $requiresManualCode,
            'uncertain_fields' => $uncertain,
        ];
    }

    /**
     * @return array{subject_code: string, subject: string, subject_choice: ?string}|null
     */
    public function lookupCatalog(string $subjectCode): ?array
    {
        $code = strtoupper(preg_replace('/\s+/', '', $subjectCode) ?? '');
        if ($code === '') {
            return null;
        }

        $row = PdCourse::query()
            ->whereRaw('UPPER(TRIM(subjcode)) = ?', [$code])
            ->orderBy('subjcode')
            ->first(['subjcode', 'subjname']);

        if ($row !== null) {
            $name = trim((string) ($row->subjname ?? ''));

            return [
                'subject_code' => trim((string) $row->subjcode),
                'subject' => $name,
                'subject_choice' => $this->normalizeSubjectChoice($name),
            ];
        }

        try {
            $reg = DB::connection('reg')
                ->table('course')
                ->whereRaw('UPPER(TRIM(COURSECODE)) = ?', [$code])
                ->orderByDesc('CREATEDATETIME')
                ->first(['COURSECODE', 'COURSENAMEENG']);
        } catch (Throwable $e) {
            Log::debug('REG course lookup skipped', ['code' => $code, 'error' => $e->getMessage()]);

            return null;
        }

        if ($reg === null) {
            return null;
        }

        $name = trim((string) ($reg->COURSENAMEENG ?? ''));

        return [
            'subject_code' => trim((string) $reg->COURSECODE),
            'subject' => $name,
            'subject_choice' => $this->normalizeSubjectChoice($name),
        ];
    }

    public function normalizeSubjectChoice(?string $name): ?string
    {
        $upper = strtoupper(preg_replace('/\s+/', ' ', trim((string) $name)) ?? '');
        if ($upper === '') {
            return null;
        }

        if (str_contains($upper, 'INDEPENDENT STUDY') || str_contains($upper, 'INDEPENDENT')) {
            return 'INDEPENDENT STUDY';
        }
        if (str_contains($upper, 'DISSERTATION')) {
            return 'DISSERTATION';
        }
        if (preg_match('/(?<![A-Z])THESIS(?![A-Z])/', $upper) || $upper === 'THESIS') {
            return 'THESIS';
        }

        if (ThesisCourse::isThesisTitle($name)) {
            return self::SUBJECT_CHOICES[0];
        }

        return null;
    }

    /**
     * ชื่อไฟล์เป็นข้อมูลเสริม — รองรับทั้ง
     * TS-SC069998-01-2-2568-... และ TS-SC069998-01-ชื่อ1,ชื่อ2.pdf
     *
     * @return array{
     *     subject_code?: string,
     *     section?: string,
     *     term?: int,
     *     year?: int,
     *     student_names?: list<string>
     * }
     */
    private function parseFilename(string $filename): array
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);

        if (preg_match('/(?:TS-)?([A-Z]{2}\d{5,8})-(\d{1,2})-(\d)-(\d{4})/i', $base, $m)) {
            $out = [
                'subject_code' => strtoupper($m[1]),
                'section' => str_pad((string) ((int) $m[2]), 2, '0', STR_PAD_LEFT),
                'term' => (int) $m[3],
                'year' => (int) $m[4],
            ];

            // ส่วนท้ายหลัง ปี อาจเป็นชื่อนักศึกษา
            if (preg_match('/(?:TS-)?[A-Z]{2}\d{5,8}-\d{1,2}-\d-\d{4}[-_\s]*(.+)$/iu', $base, $rest)
                && ! preg_match('/^\d/', $rest[1])
            ) {
                $names = $this->splitFilenameNames($rest[1]);
                if ($names !== []) {
                    $out['student_names'] = $names;
                }
            }

            return $out;
        }

        if (preg_match('/(?:TS-)?([A-Z]{2}\d{5,8})-(\d{1,2})-(.+)$/iu', $base, $m)) {
            $out = [
                'subject_code' => strtoupper($m[1]),
                'section' => str_pad((string) ((int) $m[2]), 2, '0', STR_PAD_LEFT),
            ];
            $names = $this->splitFilenameNames($m[3]);
            if ($names !== []) {
                $out['student_names'] = $names;
            }

            return $out;
        }

        if (preg_match('/([A-Z]{2}\d{5,8})/i', $base, $m)) {
            return ['subject_code' => strtoupper($m[1])];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function splitFilenameNames(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '' || preg_match('/^\d+([._-]\d+)*$/', $raw)) {
            return [];
        }

        $parts = preg_split('/[,،、;|+\/]+/u', $raw) ?: [];
        $names = [];
        foreach ($parts as $part) {
            $part = trim(preg_replace('/\s+/u', ' ', $part) ?? '');
            $part = trim($part, " \t.-_");
            if ($part === '' || mb_strlen($part) < 2) {
                continue;
            }
            if (preg_match('/^(Mr|Mrs|Ms|Miss)\b/i', $part) || preg_match('/[\x{0E00}-\x{0E7F}A-Za-z]/u', $part)) {
                $names[] = $part;
            }
        }

        return $names;
    }

    /**
     * อ่านตารางนักศึกษาจากตำแหน่งข้อความใน PDF (คอลัมน์ ลง / ผ่าน / เกรด / หมายเหตุ)
     * ใช้เมื่อ getText() รวมข้อความจนคอลัมน์หาย — โดยเฉพาะไฟล์จาก PDF printer
     *
     * @return list<array{
     *     student_code: string,
     *     grade: string,
     *     credits_registered: float|null,
     *     credits_passed: float|null,
     *     note: ?string,
     *     student_name: string
     * }>
     */
    private function extractStudentTableRows(object $document): array
    {
        if (! method_exists($document, 'getPages')) {
            return [];
        }

        $rows = [];
        try {
            foreach ($document->getPages() as $page) {
                if (! method_exists($page, 'getDataTm')) {
                    continue;
                }
                $items = [];
                foreach ($page->getDataTm() as $item) {
                    $tm = $item[0] ?? null;
                    $text = trim(preg_replace('/\s+/u', ' ', (string) ($item[1] ?? '')) ?? '');
                    $text = str_replace("\xC2\xA0", ' ', $text);
                    $text = trim($text);
                    if ($text === '' || ! is_array($tm)) {
                        continue;
                    }
                    $items[] = [
                        'x' => (float) ($tm[4] ?? 0),
                        'y' => (float) ($tm[5] ?? 0),
                        'text' => $text,
                    ];
                }
                if ($items === []) {
                    continue;
                }

                usort($items, function (array $a, array $b): int {
                    if (abs($a['y'] - $b['y']) > 2.5) {
                        return $b['y'] <=> $a['y'];
                    }

                    return $a['x'] <=> $b['x'];
                });

                $lines = $this->clusterItemsIntoLines($items);

                $columns = null;
                $pageRows = [];
                $bodyLines = [];
                foreach ($lines as $line) {
                    $joined = implode('', array_map(fn ($c) => $c['text'], $line['cells']));
                    if ($columns === null) {
                        if (
                            preg_match('/ลง/u', $joined)
                            && preg_match('/ผ่าน/u', $joined)
                            && (preg_match('/เกรด/u', $joined) || preg_match('/รหัส/u', $joined))
                        ) {
                            $columns = $this->mapTableHeaderColumns($line['cells']);
                        }

                        continue;
                    }

                    if (preg_match('/MANUAL|รวมทั้งหมด|อาจารย์ประจำวิชา|ประธานหลักสูตร/u', $joined)) {
                        break;
                    }

                    $bodyLines[] = $line;
                    $parsed = $this->parseTableDataLine($line['cells'], $columns);
                    if ($parsed !== null) {
                        $parsed['_y'] = $this->studentRowAnchorY($line['cells'], $columns, $line['y']);
                        $pageRows[] = $parsed;
                    }
                }

                if ($columns !== null && $pageRows !== []) {
                    $pageRows = $this->attachNotesByProximity($pageRows, $bodyLines, $columns);
                }

                foreach ($pageRows as $pageRow) {
                    unset($pageRow['_y']);
                    $rows[] = $pageRow;
                }
            }
        } catch (Throwable $e) {
            Log::debug('Thesis PDF table extract failed', ['error' => $e->getMessage()]);

            return [];
        }

        return $rows;
    }

    /**
     * รวมชิ้นข้อความที่อยู่ใกล้กันในแนวตั้งเป็นบรรทัด — ใช้ single-linkage
     * เพราะคอลัมน์หมายเหตุ (ชื่ออาจารย์ / วันที่สอบ) มักเลื่อนจากแถวรหัสนักศึกษา 4–6pt
     *
     * @param  list<array{x: float, y: float, text: string}>  $items
     * @return list<array{y: float, cells: list<array{x: float, y: float, text: string}>}>
     */
    private function clusterItemsIntoLines(array $items): array
    {
        $lines = [];
        $buffer = [];
        $flush = function () use (&$buffer, &$lines): void {
            if ($buffer === []) {
                return;
            }
            $ys = array_map(fn (array $c) => $c['y'], $buffer);
            $lines[] = [
                'y' => array_sum($ys) / count($ys),
                'cells' => $buffer,
            ];
            $buffer = [];
        };

        foreach ($items as $item) {
            $joins = false;
            foreach ($buffer as $existing) {
                if (abs($item['y'] - $existing['y']) <= 8.0) {
                    $joins = true;
                    break;
                }
            }
            if ($buffer === [] || $joins) {
                $buffer[] = $item;
            } else {
                $flush();
                $buffer[] = $item;
            }
        }
        $flush();

        return $lines;
    }

    /**
     * @param  list<array{x: float, y: float, text: string}>  $cells
     * @param  array{code: float, registered: float, passed: float, grade: float, name: float, note: float}  $columns
     */
    private function studentRowAnchorY(array $cells, array $columns, float $fallbackY): float
    {
        foreach ($cells as $cell) {
            if ($this->nearestTableColumn($cell['x'], $columns) === 'code'
                && $this->normalizeStudentCodeFromTable($cell['text']) !== null
            ) {
                return $cell['y'];
            }
        }

        return $fallbackY;
    }

    /**
     * คอลัมน์หมายเหตุมักไม่ได้อยู่พิกัด Y เดียวกับรหัสนักศึกษา (โดยเฉพาะไฟล์ที่พิมพ์ผ่าน PDF printer)
     * จึงเก็บชิ้นข้อความฝั่งขวาของตาราง แล้วแปะให้แถวนักศึกษาที่ใกล้ที่สุด
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array{y: float, cells: list<array{x: float, y: float, text: string}>}>  $bodyLines
     * @param  array{code: float, registered: float, passed: float, grade: float, name: float, note: float}  $columns
     * @return list<array<string, mixed>>
     */
    private function attachNotesByProximity(array $rows, array $bodyLines, array $columns): array
    {
        $noteItems = [];
        foreach ($bodyLines as $line) {
            foreach ($line['cells'] as $cell) {
                if (! $this->isNoteColumnX($cell['x'], $columns)) {
                    continue;
                }
                if ($this->isNoteHeaderOrPlaceholder($cell['text'])) {
                    continue;
                }
                $noteItems[] = $cell;
            }
        }

        foreach ($rows as $i => $row) {
            $mine = [];
            foreach ($noteItems as $item) {
                if ($this->nearestStudentRowIndex($item['y'], $rows) === $i) {
                    $mine[] = $item;
                }
            }
            $rows[$i]['note'] = $this->composeNote($mine);
            unset($rows[$i]['_y']);
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function nearestStudentRowIndex(float $y, array $rows): ?int
    {
        $best = null;
        $bestDist = PHP_FLOAT_MAX;
        foreach ($rows as $i => $row) {
            $rowY = (float) ($row['_y'] ?? 0);
            $dist = abs($rowY - $y);
            if ($dist < $bestDist) {
                $best = $i;
                $bestDist = $dist;
            }
        }

        // ระยะห่างแถวนักศึกษาในใบ REG ประมาณ 22pt — ใช้ไม่เกินครึ่งแถว
        return $best !== null && $bestDist <= 12.0 ? $best : null;
    }

    /**
     * @param  array{code: float, registered: float, passed: float, grade: float, name: float, note: float}  $columns
     */
    private function isNoteColumnX(float $x, array $columns): bool
    {
        $nameX = (float) ($columns['name'] ?? 276.0);
        $noteX = (float) ($columns['note'] ?? 480.0);
        $boundary = ($nameX + $noteX) / 2;

        return $x >= ($boundary - 8.0);
    }

    /**
     * @param  list<array{x: float, y: float, text: string}>  $cells
     */
    private function composeNote(array $cells): ?string
    {
        if ($cells === []) {
            return null;
        }

        usort($cells, function (array $a, array $b): int {
            if (abs($a['y'] - $b['y']) > 2.5) {
                return $b['y'] <=> $a['y'];
            }

            return $a['x'] <=> $b['x'];
        });

        $lines = [];
        $buffer = [];
        $flush = function () use (&$buffer, &$lines): void {
            if ($buffer === []) {
                return;
            }
            $lines[] = $this->joinNoteFragments($buffer);
            $buffer = [];
        };

        foreach ($cells as $cell) {
            if ($buffer === [] || abs($cell['y'] - $buffer[array_key_last($buffer)]['y']) <= 2.8) {
                $buffer[] = $cell;
            } else {
                $flush();
                $buffer[] = $cell;
            }
        }
        $flush();

        $note = trim(preg_replace('/\s+/u', ' ', implode(' ', array_filter($lines))) ?? '');

        return $this->normalizeNoteText($note);
    }

    /**
     * @param  list<array{x: float, y: float, text: string}>  $cells
     */
    private function joinNoteFragments(array $cells): string
    {
        usort($cells, fn (array $a, array $b): int => $a['x'] <=> $b['x']);
        $out = '';
        $prevX = null;
        foreach ($cells as $cell) {
            $text = $cell['text'];
            if ($out === '') {
                $out = $text;
                $prevX = $cell['x'];
                continue;
            }
            $gap = $cell['x'] - (float) $prevX;
            if ($text === '.' || str_ends_with($out, '.') || $gap < 9.0) {
                $out .= $text;
            } else {
                $out .= ' '.$text;
            }
            $prevX = $cell['x'];
        }

        return $out;
    }

    private function normalizeNoteText(string $note): ?string
    {
        $note = trim(preg_replace('/\s+/u', ' ', $note) ?? '');
        $note = preg_replace('/\s*\.\s*/u', '.', $note) ?? $note;
        $note = preg_replace('/\.(\d{4})\b/u', '. $1', $note) ?? $note;
        $note = trim(preg_replace('/\s+/u', ' ', $note) ?? '');

        if ($note === '' || $this->isNoteHeaderOrPlaceholder($note) || preg_match('/^[.\s_-]+$/u', $note) === 1) {
            return null;
        }

        if (preg_match('/CONTROL|CคOณNTRL|คณะวิทยาศสตร์/u', $note)) {
            return null;
        }

        return $note;
    }

    private function isNoteHeaderOrPlaceholder(string $text): bool
    {
        $text = trim($text);

        return $text === ''
            || preg_match('/^(หมายเหตุ|<>|&lt;&gt;|< >)$/u', $text) === 1
            || preg_match('/^[-_]{2,}$/u', $text) === 1;
    }

    /**
     * @param  list<array{x: float, y: float, text: string}>  $cells
     * @return array{code: float, registered: float, passed: float, grade: float, name: float, note: float}
     */
    private function mapTableHeaderColumns(array $cells): array
    {
        $columns = [
            'code' => 76.0,
            'registered' => 162.0,
            'passed' => 189.0,
            'grade' => 238.0,
            'name' => 276.0,
            'note' => 480.0,
        ];

        foreach ($cells as $cell) {
            $t = $cell['text'];
            $x = $cell['x'];
            if (preg_match('/รหัส/u', $t)) {
                $columns['code'] = $x;
            } elseif ($t === 'ลง' || preg_match('/^ลง$/u', $t)) {
                $columns['registered'] = $x;
            } elseif (preg_match('/ผ่าน/u', $t)) {
                $columns['passed'] = $x;
            } elseif (preg_match('/เกรด/u', $t)) {
                $columns['grade'] = $x;
            } elseif (preg_match('/ชื่อ|ชื/u', $t)) {
                $columns['name'] = $x;
            } elseif (preg_match('/หมายเหตุ/u', $t)) {
                $columns['note'] = $x;
            }
        }

        return $columns;
    }

    /**
     * @param  list<array{x: float, y: float, text: string}>  $cells
     * @param  array{code: float, registered: float, passed: float, grade: float, name: float, note: float}  $columns
     * @return array{
     *     student_code: string,
     *     grade: string,
     *     credits_registered: float|null,
     *     credits_passed: float|null,
     *     note: ?string,
     *     student_name: string
     * }|null
     */
    private function parseTableDataLine(array $cells, array $columns): ?array
    {
        $buckets = [
            'code' => [],
            'registered' => [],
            'passed' => [],
            'grade' => [],
            'name' => [],
            'note' => [],
        ];

        foreach ($cells as $cell) {
            $col = $this->nearestTableColumn($cell['x'], $columns);
            if ($col === null) {
                continue;
            }
            $buckets[$col][] = $cell['text'];
        }

        $codeRaw = trim(implode('', $buckets['code']));
        $code = $this->normalizeStudentCodeFromTable($codeRaw);
        if ($code === null) {
            return null;
        }

        $grade = '';
        foreach ($buckets['grade'] as $g) {
            if (preg_match('/^[SUIW]$/i', trim($g))) {
                $grade = strtoupper(trim($g));
                break;
            }
        }

        $registered = $this->firstNumericToken($buckets['registered']);
        $passed = $this->firstNumericToken($buckets['passed']);
        $name = trim(preg_replace('/\s+/u', ' ', implode(' ', $buckets['name'])) ?? '');
        $note = $this->normalizeNoteText(trim(preg_replace('/\s+/u', ' ', implode(' ', $buckets['note'])) ?? ''));

        return [
            'student_code' => $code,
            'grade' => $grade !== '' ? $grade : 'S',
            'credits_registered' => $registered,
            'credits_passed' => $passed,
            'note' => $note,
            'student_name' => $name,
        ];
    }

    /**
     * @param  array{code: float, registered: float, passed: float, grade: float, name: float, note: float}  $columns
     */
    private function nearestTableColumn(float $x, array $columns): ?string
    {
        if ($this->isNoteColumnX($x, $columns)) {
            return 'note';
        }

        $best = null;
        $bestDist = PHP_FLOAT_MAX;
        foreach ($columns as $name => $cx) {
            if ($name === 'note') {
                continue;
            }
            $dist = abs($x - (float) $cx);
            $limit = $name === 'name' ? 90.0 : 28.0;
            if ($dist <= $limit && $dist < $bestDist) {
                $best = $name;
                $bestDist = $dist;
            }
        }

        return $best;
    }

    /**
     * @param  list<string>  $tokens
     */
    private function firstNumericToken(array $tokens): ?float
    {
        foreach ($tokens as $token) {
            $token = trim(str_replace([',', ' '], '', $token));
            if (preg_match('/^\d+(?:\.\d+)?$/', $token)) {
                return (float) $token;
            }
        }

        return null;
    }

    private function normalizeStudentCodeFromTable(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/(\d{9,10}-\d)/', $raw, $m)) {
            return $m[1];
        }

        return $this->normalizeStudentCode(
            preg_replace('/\D/', '', $raw) ?? '',
            '0'
        );
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  list<array{
     *     student_code: string,
     *     grade: string,
     *     credits_registered: float|null,
     *     credits_passed: float|null,
     *     note: ?string,
     *     student_name: string
     * }>  $tableRows
     * @return array<string, mixed>
     */
    private function applyStudentTableRows(array $parsed, array $tableRows): array
    {
        if ($tableRows === []) {
            return $parsed;
        }

        /** @var array<string, array<string, mixed>> $byCode */
        $byCode = [];
        foreach ($parsed['students'] as $student) {
            $code = (string) ($student['student_code'] ?? '');
            if ($code !== '') {
                $byCode[$code] = $student;
            }
        }

        $ordered = [];
        foreach ($tableRows as $row) {
            $code = (string) $row['student_code'];
            if ($code === '') {
                continue;
            }

            if (! isset($byCode[$code])) {
                $byCode[$code] = $this->studentRow(
                    $code,
                    (string) ($row['student_name'] ?? ''),
                    (string) ($row['grade'] ?? 'S'),
                    ''
                );
                $byCode[$code] = $this->enrichStudentFromReg($byCode[$code]);
            }

            $student = $byCode[$code];
            $uncertain = is_array($student['uncertain_fields'] ?? null) ? $student['uncertain_fields'] : [];

            if ($row['credits_registered'] !== null) {
                $student['credits_registered'] = $row['credits_registered'];
                unset($uncertain['credits_registered']);
            }
            if ($row['credits_passed'] !== null) {
                $student['credits_passed'] = $row['credits_passed'];
                $student['progress_credits'] = $row['credits_passed'];
                unset($uncertain['credits_passed']);
            }
            if (($row['note'] ?? null) !== null && trim((string) $row['note']) !== '') {
                $student['note'] = trim((string) $row['note']);
                unset($uncertain['note']);
            }
            if (($row['grade'] ?? '') !== '') {
                $student['grade'] = strtoupper((string) $row['grade']);
            }

            // ชื่อจากตารางใช้เฉพาะเมื่อยังไม่มีชื่อดีจาก REG
            $tableName = trim((string) ($row['student_name'] ?? ''));
            if (
                $tableName !== ''
                && ! $this->isGarbledName($tableName)
                && empty($student['from_reg'])
            ) {
                $parts = $this->splitDisplayName($tableName);
                $student['name_prefix'] = $parts['name_prefix'] ?: $student['name_prefix'];
                $student['first_name'] = $parts['first_name'] ?: $student['first_name'];
                $student['last_name'] = $parts['last_name'] ?: $student['last_name'];
                $student['student_name'] = $this->composeDisplayName(
                    (string) $student['name_prefix'],
                    (string) $student['first_name'],
                    (string) $student['last_name'],
                ) ?: $tableName;
            }

            $student['uncertain_fields'] = $uncertain;
            $byCode[$code] = $student;
            $ordered[] = $code;
        }

        // คงคนที่อ่านจากข้อความได้แต่ไม่อยู่ในตาราง
        foreach (array_keys($byCode) as $code) {
            if (! in_array($code, $ordered, true)) {
                $ordered[] = $code;
            }
        }

        $parsed['students'] = array_values(array_map(fn (string $code) => $byCode[$code], $ordered));

        return $parsed;
    }

    /**
     * @param  list<string>  $filenameNames
     * @return list<array<string, mixed>>
     */
    private function parseStudents(string $text, array $filenameNames = []): array
    {
        $ordered = [];
        $students = [];

        // รูปแบบ REG ที่ยังเหลือแม้ข้อความไทยจะเพี้ยน: S88677020018-02
        if (preg_match_all('/([SUIW])(\d{9,14})-(\d{1,2})(?!\d)/u', $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $m) {
                $grade = strtoupper($m[1][0]);
                $code = $this->normalizeStudentCode($m[2][0], $m[3][0]);
                if ($code === null || isset($students[$code])) {
                    continue;
                }

                $offset = (int) $m[0][1];
                // offset เป็น byte — ตัด prefix ถึงจุดเริ่มรหัส (ASCII) แล้วค่อย mb_substr ท้าย
                $prefix = substr($text, 0, $offset);
                $before = mb_substr($prefix, max(0, mb_strlen($prefix) - 80));
                $name = $this->extractNameNearCode($before);

                $ordered[] = $code;
                $students[$code] = $this->studentRow($code, $name, $grade, $text);
            }
        }

        // PDF ที่ข้อความสมบูรณ์: ชื่อ + เกรด + รหัส
        if ($students === [] && preg_match_all('/([ก-๙A-Za-z][ก-๙A-Za-z. \t\'-]{1,80}?)\s*([SUIW])\s*(\d{9,14})(?:-(\d{1,2}))?/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $name = trim(preg_replace('/[ \t]+/', ' ', $m[1]) ?? '');
                $name = preg_replace('/^(?:<>|%|#)+/', '', $name) ?? $name;
                $name = trim($name);
                if ($name === '' || mb_strlen($name) < 2) {
                    continue;
                }
                if (preg_match('/(หมายเหตุ|รหัสประจำตัว|ลำดับ|ผู้สอน|รายวิชา|CONTROL|T-SCORE|MANUAL|รวมทั้งหมด|คณะ|วิทยาเขต|หน่วยกิต)/iu', $name)) {
                    continue;
                }

                $code = $this->normalizeStudentCode($m[3], $m[4] ?? '0') ?? $m[3];
                if (isset($students[$code])) {
                    continue;
                }
                $ordered[] = $code;
                $students[$code] = $this->studentRow($code, $name, strtoupper($m[2]), $text);
            }
        }

        // จับคู่ชื่อจากชื่อไฟล์ตามลำดับ (ช่วยกรณี PDF printer ทำให้ชื่อในเนื้อหาเพี้ยน)
        foreach ($ordered as $index => $code) {
            if (! isset($filenameNames[$index])) {
                continue;
            }
            $fromFile = trim($filenameNames[$index]);
            if ($fromFile === '') {
                continue;
            }
            $current = (string) ($students[$code]['student_name'] ?? '');
            // คงชื่อจากเนื้อหาถ้าอ่านได้ชัดแล้ว (เช่น นางสาวฟาติก๊ะ ...)
            if (! $this->isGarbledName($current) && ! str_starts_with($current, 'นักศึกษา ')) {
                continue;
            }
            $students[$code]['first_name'] = $fromFile;
            $students[$code]['student_name'] = $fromFile;
        }

        // เติมคำนำหน้า/ชื่อ/สกุล จาก REG ตามรหัสนักศึกษา (ดึงครั้งเดียว)
        $regMap = $this->studentDirectory()->findManyByStudentCodes($ordered);
        foreach ($ordered as $code) {
            $students[$code] = $this->enrichStudentFromReg($students[$code], $regMap[$code] ?? null);
        }

        return array_values($students);
    }

    private function studentDirectory(): RegStudentDirectory
    {
        return $this->students ?? app(RegStudentDirectory::class);
    }

    private function normalizeStudentCode(string $digits, string $suffix = '0'): ?string
    {
        $digits = preg_replace('/\D/', '', $digits) ?? '';
        $suffix = preg_replace('/\D/', '', $suffix) ?? '0';
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) > 11) {
            $digits = substr($digits, -11);
        }

        $normalized = $this->studentDirectory()->normalizeCode($digits.'-'.($suffix !== '' ? $suffix : '0'));

        return preg_match('/^\d{9,10}-\d$/', $normalized) ? $normalized : null;
    }

    private function extractNameNearCode(string $before): string
    {
        $before = preg_replace('/[\x00-\x1F<>%#]+/u', ' ', $before) ?? $before;
        $before = str_replace(["\n", "\r", "\t"], ' ', $before);
        $before = trim(preg_replace('/\s+/u', ' ', $before) ?? '');

        if (preg_match('/((?:นาย|นางสาว|นาง|Mr\.|Mrs\.|Ms\.|Miss)\s*[ก-๙A-Za-z. \']{2,60})$/iu', $before, $m)) {
            $name = trim(preg_replace('/\s+/u', ' ', $m[1]) ?? '');
            if (! $this->isGarbledName($name)) {
                return $name;
            }
        }

        if (preg_match('/([ก-๙]{2,}(?:\s+[ก-๙.]{2,}){0,4})$/u', $before, $m)) {
            $name = trim(preg_replace('/\s+/u', ' ', $m[1]) ?? '');
            if (! $this->isGarbledName($name) && mb_strlen($name) >= 4) {
                return $name;
            }
        }

        return '';
    }

    private function isGarbledName(string $name): bool
    {
        $name = trim($name);
        if ($name === '' || str_starts_with($name, 'นักศึกษา ')) {
            return true;
        }
        if (preg_match('/[ÉÊÍáàãõø]|คคค|หหห|ญญญ|ffฟ|ใ4ฟ/u', $name)) {
            return true;
        }

        $letters = preg_match_all('/[\x{0E00}-\x{0E7F}A-Za-z]/u', $name) ?: 0;
        $junk = preg_match_all('/[^\x{0E00}-\x{0E7F}A-Za-z.\s\'-]/u', $name) ?: 0;

        return $letters < 3 || $junk > 2;
    }

    private function looksGarbled(string $text): bool
    {
        if (preg_match('/คคคคณะ|หหหหม|ffฟ|É|Ê|ใ4ฟ/u', $text)) {
            return true;
        }

        // มีใบส่งผล แต่ไม่เจอรหัสวิชา/ภาคในรูปแบบมาตรฐาน
        return str_contains($text, 'ใบส่งผล')
            && ! preg_match('/\b[A-Z]{2}\d{5,8}\s*:/u', $text);
    }

    /**
     * @param  array<string, mixed>  $student
     * @param  array{
     *     student_code: string,
     *     name_prefix: string,
     *     first_name: string,
     *     last_name: string,
     *     student_name: string
     * }|null  $reg
     * @return array<string, mixed>
     */
    private function enrichStudentFromReg(array $student, ?array $reg = null): array
    {
        $uncertain = is_array($student['uncertain_fields'] ?? null) ? $student['uncertain_fields'] : [];
        $code = (string) ($student['student_code'] ?? '');
        $reg ??= $this->studentDirectory()->findByStudentCode($code);

        if ($reg === null) {
            $parsed = $this->splitDisplayName((string) ($student['student_name'] ?? ''));
            $student['name_prefix'] = $student['name_prefix'] ?: $parsed['name_prefix'];
            $student['first_name'] = $student['first_name'] ?: $parsed['first_name'];
            $student['last_name'] = $student['last_name'] ?: $parsed['last_name'];
            $student['student_name'] = $this->composeDisplayName(
                (string) $student['name_prefix'],
                (string) $student['first_name'],
                (string) $student['last_name'],
            ) ?: ($student['student_name'] ?? '');

            if ($student['name_prefix'] === '' || $student['name_prefix'] === null) {
                $uncertain['name_prefix'] = 'ไม่พบคำนำหน้าในฐานข้อมูล REG — กรุณากรอกเอง';
            }
            if ($student['first_name'] === '' || $student['first_name'] === null || str_starts_with((string) $student['student_name'], 'นักศึกษา ')) {
                $uncertain['first_name'] = 'ชื่ออาจไม่ถูกต้อง — กรุณากรอกเอง';
            }
            if ($student['last_name'] === '' || $student['last_name'] === null) {
                $uncertain['last_name'] = 'นามสกุลอาจไม่ถูกต้อง — กรุณากรอกเอง';
            }
            if (! preg_match('/^\d{9,10}-\d$/', $code)) {
                $uncertain['student_code'] = 'รูปแบบรหัสนักศึกษาอาจไม่ถูกต้อง — กรุณาตรวจสอบ';
            }

            $student['uncertain_fields'] = $uncertain;
            $student['from_reg'] = false;

            return $student;
        }

        $student['student_code'] = $reg['student_code'];
        $student['name_prefix'] = $reg['name_prefix'];
        $student['first_name'] = $reg['first_name'];
        $student['last_name'] = $reg['last_name'];
        $student['student_name'] = $reg['student_name'];
        $student['from_reg'] = true;
        // ชื่อจาก REG ชัดแล้ว — ไม่ต้องไฮไลต์ชื่อ
        unset($uncertain['name_prefix'], $uncertain['first_name'], $uncertain['last_name'], $uncertain['student_code']);
        $student['uncertain_fields'] = $uncertain;

        return $student;
    }

    /**
     * @return array{name_prefix: string, first_name: string, last_name: string}
     */
    private function splitDisplayName(string $name): array
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $prefix = '';
        if (preg_match('/^(นางสาว|นาง|นาย|Mr\.|Mrs\.|Ms\.|Miss)\s+(.+)$/iu', $name, $m)) {
            $prefix = $m[1];
            $name = trim($m[2]);
        }

        $parts = preg_split('/\s+/u', $name) ?: [];
        $first = array_shift($parts) ?: '';
        $last = trim(implode(' ', $parts));

        return [
            'name_prefix' => $prefix,
            'first_name' => $first,
            'last_name' => $last,
        ];
    }

    private function composeDisplayName(string $prefix, string $first, string $last): string
    {
        return trim(preg_replace('/\s+/u', ' ', $prefix.' '.$first.' '.$last) ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function studentRow(string $code, string $name, string $grade, string $text): array
    {
        $name = trim($name);
        if ($name === '' || $this->isGarbledName($name)) {
            $name = '';
        }
        $parts = $this->splitDisplayName($name);

        return [
            'student_code' => $code,
            'name_prefix' => $parts['name_prefix'],
            'first_name' => $parts['first_name'],
            'last_name' => $parts['last_name'],
            'student_name' => $this->composeDisplayName($parts['name_prefix'], $parts['first_name'], $parts['last_name'])
                ?: ($name !== '' ? $name : 'นักศึกษา '.$code),
            'degree' => $this->guessDegree($text, $code),
            'thesis_terms_count' => 1,
            'proposal_approved' => false,
            'grade' => $grade,
            'credits_registered' => null,
            'credits_passed' => null,
            'progress_credits' => null,
            'completed' => false,
            'defense_date' => null,
            'note' => null,
            'uncertain_fields' => [],
            'from_reg' => false,
        ];
    }

    private function guessDegree(string $text, string $code): string
    {
        if (str_contains($text, 'ปริญญาเอก') || str_contains(mb_strtolower($text), 'doctoral') || str_contains(mb_strtolower($text), 'dissertation')) {
            return 'doctoral';
        }
        if (str_contains($text, 'ปริญญาโท') || str_contains(mb_strtolower($text), 'master')) {
            return 'master';
        }

        // รหัสบัณฑิตศึกษา มข. หลักนำ 6x / 8x / 9x มักเป็นป.เอกในคณะวิทย์บ่อย
        if (preg_match('/^[689]/', $code)) {
            return 'doctoral';
        }

        return 'master';
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        // Foxit PDF printer มักแทรก control chars ปนในชื่อนักศึกษา
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]+/', ' ', $text) ?? $text;
        $text = preg_replace("/[ \t]+/u", ' ', $text) ?? $text;

        return trim($text);
    }
}
