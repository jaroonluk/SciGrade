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

        if ($subject === null) {
            $subject = 'THESIS';
            $warnings[] = 'ระบบจับชนิดวิชาจากไฟล์ไม่ได้ จึงตั้งเป็น THESIS ชั่วคราว — กรุณาเลือกชื่อวิชาให้ถูกต้อง';
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
                $code = $this->normalizeStudentCode($m[2][0]);
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
        if ($students === [] && preg_match_all('/([ก-๙A-Za-z][ก-๙A-Za-z. \t\'-]{1,80}?)\s*([SUIW])\s*(\d{9,11})(?:-\d{1,2})?/u', $text, $matches, PREG_SET_ORDER)) {
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

                $code = $this->normalizeStudentCode($m[3]) ?? $m[3];
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
            $students[$code]['student_name'] = $fromFile;
        }

        return array_values($students);
    }

    private function normalizeStudentCode(string $digits): ?string
    {
        $digits = preg_replace('/\D/', '', $digits) ?? '';
        if ($digits === '') {
            return null;
        }

        // Foxit มักแทรกตัวเลขเกิน — รหัส มข. ทั่วไป 10–11 หลัก ใช้ท้ายสุด
        if (strlen($digits) > 11) {
            $digits = substr($digits, -11);
        }

        if (strlen($digits) < 9 || strlen($digits) > 11) {
            return null;
        }

        return $digits;
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
     * @return array<string, mixed>
     */
    private function studentRow(string $code, string $name, string $grade, string $text): array
    {
        $name = trim($name);
        if ($name === '' || $this->isGarbledName($name)) {
            $name = 'นักศึกษา '.$code;
        }

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
