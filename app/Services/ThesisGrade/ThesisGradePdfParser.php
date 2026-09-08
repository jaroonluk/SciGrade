<?php

namespace App\Services\ThesisGrade;

use App\Models\PdCourse;
use App\Support\ThesisCourse;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Smalot\PdfParser\Parser;

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
                $text = $this->parser->parseFile($absolutePath)->getText();
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

        return $this->parseText((string) $text, $originalFilename, $termFallback, $yearFallback);
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

        $subjectCode = '';
        $subjectRaw = '';
        if (preg_match('/\b([A-Z]{2}\d{5,8})\s*:\s*([^\n\r]+)/iu', $text, $m)) {
            $subjectCode = strtoupper(trim($m[1]));
            $subjectRaw = trim($m[2]);
        } elseif (preg_match('/\b([A-Z]{2}\d{5,8})\b/iu', $text, $m)) {
            $subjectCode = strtoupper(trim($m[1]));
        } elseif (! empty($fromName['subject_code'])) {
            $subjectCode = $fromName['subject_code'];
            $warnings[] = 'ใช้รหัสวิชาจากชื่อไฟล์ เพราะไม่พบรูปแบบรหัสในเนื้อหา PDF — กรุณาตรวจสอบอีกครั้ง';
        }

        $subject = $this->normalizeSubjectChoice($subjectRaw !== '' ? $subjectRaw : null);
        if ($subject === null) {
            $subject = $this->normalizeSubjectChoice($text);
        }

        $requiresManualCode = false;
        if ($subjectCode === '') {
            // ถ้าอย่างน้อยจับชนิดวิชา THESIS / IS / DISSERTATION ได้ ให้ไปกรอกรหัสเองได้ ไม่บล็อกทั้งหมด
            if ($subject !== null) {
                $requiresManualCode = true;
                $warnings[] = 'อ่านชื่อวิชาเป็น '.$subject.' ได้แล้ว แต่ไม่พบรหัสวิชาในไฟล์ — กรุณากรอกรหัสวิชาเองด้านล่าง';
            } else {
                throw new ThesisGradePdfParseException(
                    'ระบบอ่านข้อความจากไฟล์ได้ แต่ไม่พบรหัสวิชาและชนิดวิชาในใบส่งผลการเรียน กรุณาตรวจสอบว่าเป็นใบ มข.11 / TS จากระบบ REG',
                    'missing_subject_code',
                    self::MANUAL_HINT,
                );
            }
        }

        if ($subject === null) {
            $subject = 'THESIS';
            $warnings[] = 'ระบบจับชนิดวิชาจากไฟล์ไม่ได้ จึงตั้งเป็น THESIS ชั่วคราว — กรุณาเลือกชื่อวิชาให้ถูกต้อง';
        }

        $subjectInCatalog = false;
        if ($subjectCode !== '') {
            $catalog = $this->lookupCatalog($subjectCode);
            if ($catalog !== null) {
                $subjectInCatalog = true;
                if ($catalog['subject_choice'] !== null) {
                    $subject = $catalog['subject_choice'];
                }
            } else {
                $warnings[] = 'ไม่พบรหัสวิชา '.$subjectCode.' ในฐานข้อมูลรายวิชา — ใช้ค่าที่อ่านจาก PDF แล้ว คุณสามารถแก้ไขรหัสหรือชื่อวิชาได้เอง';
            }
        }

        $term = $termFallback;
        $year = $yearFallback;
        if (preg_match('/ภาคการศึกษาที่\s*(\d+)\s*\/\s*(\d{4})/u', $text, $tm)) {
            $term = (int) $tm[1];
            $year = (int) $tm[2];
        } elseif (! empty($fromName['term']) && ! empty($fromName['year'])) {
            $term = (int) $fromName['term'];
            $year = (int) $fromName['year'];
            $warnings[] = 'ใช้ภาค/ปีจากชื่อไฟล์ เพราะไม่พบในเนื้อหา PDF — กรุณาตรวจสอบอีกครั้ง';
        } else {
            $warnings[] = 'ไม่พบภาคการศึกษา/ปีการศึกษาในไฟล์ จึงใช้ค่าที่เลือกไว้ในแบบฟอร์ม — กรุณาตรวจสอบอีกครั้ง';
        }

        $section = null;
        if (preg_match('/กลุ่ม(?:เรียน)?\s*[:：]?\s*(\d{1,2})/u', $text, $sm)) {
            $section = str_pad((string) ((int) $sm[1]), 2, '0', STR_PAD_LEFT);
        } elseif (preg_match('/Sec(?:tion)?\s*[:：]?\s*(\d{1,2})/iu', $text, $sm)) {
            $section = str_pad((string) ((int) $sm[1]), 2, '0', STR_PAD_LEFT);
        } elseif (! empty($fromName['section'])) {
            $section = $fromName['section'];
            $warnings[] = 'ใช้กลุ่มเรียนจากชื่อไฟล์ เพราะไม่พบในเนื้อหา PDF — กรุณาตรวจสอบอีกครั้ง';
        }

        if ($section === null) {
            $section = '01';
            $warnings[] = 'ไม่พบกลุ่มเรียนในไฟล์ จึงตั้งเป็น 01 ชั่วคราว — กรุณาแก้ไขหากไม่ถูกต้อง';
        } else {
            $section = str_pad((string) ((int) preg_replace('/\D/', '', (string) $section) ?: 1), 2, '0', STR_PAD_LEFT);
        }

        $teacher = null;
        if (preg_match('/(?:รศ\.|ผศ\.|ศ\.|อ\.|ดร\.|Asst\.|Assoc\.|Prof\.)[^\n\t]+/u', $text, $teach)) {
            $teacher = trim(preg_replace('/\s+/', ' ', $teach[0]) ?? '');
            $teacher = trim(preg_replace('/\s*กลุ่ม\s*\d+.*$/u', '', $teacher) ?? '');
            if (str_contains($teacher, '(')) {
                $teacher = trim(explode('(', $teacher, 2)[0]);
            }
        }

        $students = $this->parseStudents($text);
        if ($students === []) {
            $warnings[] = 'ไม่พบรายชื่อนักศึกษาในไฟล์ — กรุณาเพิ่มรายชื่อในขั้นตอนถัดไปด้วยตนเอง';
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

        if ($row === null) {
            return null;
        }

        $name = trim((string) ($row->subjname ?? ''));

        return [
            'subject_code' => trim((string) $row->subjcode),
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
     * ชื่อไฟล์เป็นข้อมูลเสริมเท่านั้น — ตั้งชื่ออะไรก็ได้
     *
     * @return array{subject_code?: string, section?: string, term?: int, year?: int}
     */
    private function parseFilename(string $filename): array
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        if (preg_match('/(?:TS-)?([A-Z]{2}\d{5,8})-(\d{1,2})-(\d)-(\d{4})/i', $base, $m)) {
            return [
                'subject_code' => strtoupper($m[1]),
                'section' => str_pad((string) ((int) $m[2]), 2, '0', STR_PAD_LEFT),
                'term' => (int) $m[3],
                'year' => (int) $m[4],
            ];
        }

        if (preg_match('/([A-Z]{2}\d{5,8})/i', $base, $m)) {
            return ['subject_code' => strtoupper($m[1])];
        }

        return [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseStudents(string $text): array
    {
        $students = [];

        // ไทย/อังกฤษ ติดกับเกรด เช่น "นางสาว...ภาณุมาศS11655020091-41" หรือ "Mr.ARDIYAS...SAPUTRAS22667020009-01"
        // ไม่ใช้ \s ที่กินขึ้นบรรทัดใหม่ เพื่อไม่ดึงคำจากบรรทัดก่อนหน้า
        if (preg_match_all('/([ก-๙A-Za-z][ก-๙A-Za-z. \t\'-]{1,80}?)\s*([SUIW])\s*(\d{9,11})(?:-\d{1,2})?/u', $text, $matches, PREG_SET_ORDER)) {
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

                $code = $m[3];
                $grade = strtoupper($m[2]);
                $students[$code] = $this->studentRow($code, $name, $grade, $text);
            }
        }

        if ($students !== []) {
            return array_values($students);
        }

        $lines = preg_split("/\n+/u", $text) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || mb_strlen($line) < 8) {
                continue;
            }

            if (! preg_match('/(?<!\d)(\d{9,11})(?!\d)/', $line, $codeMatch)) {
                continue;
            }

            $code = $codeMatch[1];
            $grade = 'S';
            if (preg_match('/([SUIW])\s*'.preg_quote($code, '/').'/', $line, $g)) {
                $grade = strtoupper($g[1]);
            } elseif (preg_match('/\b(S|U|I|W)\b/u', $line, $g)) {
                $grade = strtoupper($g[1]);
            }

            $name = trim(preg_replace('/\s+/', ' ', $line) ?? '');
            $name = preg_replace('/'.preg_quote($code, '/').'(?:-\d{1,2})?/', '', $name, 1) ?? $name;
            $name = preg_replace('/\b(S|U|I|W)\b/u', '', $name) ?? $name;
            $name = preg_replace('/[<>%]+/', ' ', $name) ?? $name;
            $name = trim(preg_replace('/[\d.]+/', ' ', $name) ?? '');
            $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
            $name = preg_replace('/(หมายเหตุ|ชื่อ-สกุล|เกรด|ผ่าน|ลง|รหัสประจำตัว|ลำดับ)/u', '', $name) ?? $name;
            $name = trim($name);

            if ($name === '' || mb_strlen($name) < 2) {
                $name = 'นักศึกษา '.$code;
            }

            $students[$code] = $this->studentRow($code, $name, $grade, $text);
        }

        return array_values($students);
    }

    /**
     * @return array<string, mixed>
     */
    private function studentRow(string $code, string $name, string $grade, string $text): array
    {
        return [
            'student_code' => $code,
            'student_name' => $name,
            'degree' => $this->guessDegree($text, $code),
            'thesis_terms_count' => 1,
            'proposal_approved' => false,
            'grade' => $grade,
            'progress_credits' => $grade === 'S' ? 0 : null,
            'completed' => false,
            'defense_date' => null,
            'note' => null,
        ];
    }

    private function guessDegree(string $text, string $code): string
    {
        if (str_contains($text, 'ปริญญาเอก') || str_contains(mb_strtolower($text), 'doctoral')) {
            return 'doctoral';
        }
        if (str_contains($text, 'ปริญญาโท') || str_contains(mb_strtolower($text), 'master')) {
            return 'master';
        }

        return str_starts_with($code, '6') ? 'doctoral' : 'master';
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/[ \t]+/u", ' ', $text) ?? $text;

        return trim($text);
    }
}
