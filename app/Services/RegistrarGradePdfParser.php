<?php

namespace App\Services;

use RuntimeException;
use Smalot\PdfParser\Parser;

class RegistrarPdfParseException extends RuntimeException {}

class RegistrarGradePdfParser
{
    private const GRADE_KEYS = [
        'A' => 'num_a',
        'B+' => 'num_bb',
        'B' => 'num_b',
        'C+' => 'num_cc',
        'C' => 'num_c',
        'D+' => 'num_dd',
        'D' => 'num_d',
        'F' => 'num_f',
        'I' => 'num_i',
        'S' => 'num_s',
        'W' => 'num_w',
        'V' => 'num_v',
    ];

    private const SCORE_KEYS = [
        'A' => 'score_a',
        'B+' => 'score_bb',
        'B' => 'score_b',
        'C+' => 'score_cc',
        'C' => 'score_c',
        'D+' => 'score_dd',
        'D' => 'score_d',
        'F' => 'score_f',
    ];

    /**
     * ชื่อคณะไทย → รหัส nameng ใน grade_type (เรียงชื่อยาวก่อนเพื่อจับคู่ถูกต้อง)
     *
     * @var array<string, string>
     */
    private const FACULTY_MAP = [
        'วิทยาลัยบัณฑิตศึกษาการจัดการ' => 'MBA',
        'มนุษยศาสตร์และสังคมศาสตร์' => 'HS',
        'บริหารธุรกิจและการบัญชี' => 'KKBS',
        'วิทยาลัยการปกครองท้องถิ่น' => 'COLA',
        'วิทยาลัยการคอมพิวเตอร์' => 'CP',
        'สำนักวิชาศึกษาทั่วไป' => 'GE',
        'สถาปัตยกรรมศาสตร์' => 'AR',
        'ทันตแพทย์ศาสตร์' => 'DT',
        'ทันตแพทยศาสตร์' => 'DT',
        'สัตวแพทย์ศาสตร์' => 'VM',
        'สัตวแพทยศาสตร์' => 'VM',
        'สาธารณสุขศาสตร์' => 'PH',
        'เทคนิคการแพทย์' => 'AM',
        'วิทยาลัยนานาชาติ' => 'IC',
        'บัณฑิตวิทยาลัย' => 'GS',
        'วิศวกรรมศาสตร์' => 'EN',
        'ศิลปกรรมศาสตร์' => 'FA',
        'วิทยาศาสตร์' => 'SC',
        'เกษตรศาสตร์' => 'AG',
        'ศึกษาศาสตร์' => 'ED',
        'พยาบาลศาสตร์' => 'NU',
        'แพทยศาสตร์' => 'MD',
        'เภสัชศาสตร์' => 'PS',
        'เศรษฐศาสตร์' => 'ECON',
        'นิติศาสตร์' => 'LAW',
        'สหวิทยาการ' => 'IN',
        'เทคโนโลยี' => 'TE',
        'สถาบันภาษา' => 'LI',
        'บริหารธุรกิจ' => 'KKBS',
    ];

    public function __construct(
        private readonly Parser $parser = new Parser,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function parse(string $absolutePath, string $originalFilename, int $termFallback, int $yearFallback): array
    {
        if (! is_readable($absolutePath)) {
            throw new RegistrarPdfParseException($this->invalidFormatMessage());
        }

        $previousMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            $text = $this->parser->parseFile($absolutePath)->getText();
        } catch (\Throwable) {
            throw new RegistrarPdfParseException($this->invalidFormatMessage());
        } finally {
            if ($previousMemoryLimit !== false) {
                ini_set('memory_limit', (string) $previousMemoryLimit);
            }
        }

        return $this->parseText($text, $originalFilename, $termFallback, $yearFallback);
    }

    /**
     * @return array<string, mixed>
     */
    public function parseText(string $rawText, string $originalFilename, int $termFallback, int $yearFallback): array
    {
        $text = $this->normalizeText($rawText);

        if (! str_contains($text, 'ใบส่งผลการศึกษา')) {
            throw new RegistrarPdfParseException($this->invalidFormatMessage());
        }

        if (! preg_match('/controlcode\s*:\s*(\d+)/iu', $text, $controlMatch)
            && ! preg_match('/CONTROL\s+CODE\s*:\s*(\d+)/iu', $text)) {
            throw new RegistrarPdfParseException($this->invalidFormatMessage());
        }

        if (! preg_match('/^([A-Z]{2}\d{6})\s*:\s*(.+)$/mu', $text, $subjectMatch)) {
            throw new RegistrarPdfParseException($this->invalidFormatMessage());
        }

        $subjectCode = strtoupper(trim($subjectMatch[1]));
        $subjectName = trim($subjectMatch[2]);

        $term = $termFallback;
        $year = $yearFallback;
        if (preg_match('/ภาคการศึกษาที่\s*(\d+)\s*\/\s*(\d{4})/u', $text, $termMatch)) {
            $term = (int) $termMatch[1];
            $year = (int) $termMatch[2];
        }

        // อ่านกลุ่มเรียนจากเนื้อหา PDF เป็นหลัก — ชื่อไฟล์ใช้เป็น fallback เท่านั้น
        $sectionFromFilename = $this->sectionFromFilename($originalFilename);
        [$teacher, $sectionFromPdf] = $this->parseTeacherAndSection($text, $sectionFromFilename);
        $section = $sectionFromPdf ?? $sectionFromFilename;
        if ($section === null) {
            throw new RegistrarPdfParseException($this->invalidFormatMessage());
        }

        if ($this->parseStudents($text) === []) {
            throw new RegistrarPdfParseException($this->invalidFormatMessage());
        }

        $summary = $this->parseGradeSummary($text);
        if (array_sum($summary['counts']) === 0 && $summary['ranges'] === []) {
            throw new RegistrarPdfParseException($this->invalidFormatMessage());
        }

        $faculties = $this->parseFaculties($text);
        $degree = $this->parseDegree($text);
        $typeCourse = $this->parseTypeCourse($text);

        $gradeStd = array_merge([
            'sec' => $section,
            'fac' => implode(',', $faculties),
            'type_course' => $typeCourse,
            'num_ff' => 0,
            'num_out' => 0,
            'numstdevz' => null,
            'evaluationscore' => null,
        ], $summary['counts']);

        return [
            'subject_code' => $subjectCode,
            'subject' => $subjectName,
            'term' => $term,
            'year' => $year,
            'degree' => $degree,
            'teacher' => $teacher,
            'type_course' => $typeCourse,
            'intflag' => $summary['uses_decimal'] ? 0 : 1,
            'statuseva' => 2,
            'reasonid' => null,
            'reason' => null,
            'mean' => null,
            'sd' => null,
            'score_a' => $summary['ranges']['score_a'] ?? null,
            'score_bb' => $summary['ranges']['score_bb'] ?? null,
            'score_b' => $summary['ranges']['score_b'] ?? null,
            'score_cc' => $summary['ranges']['score_cc'] ?? null,
            'score_c' => $summary['ranges']['score_c'] ?? null,
            'score_dd' => $summary['ranges']['score_dd'] ?? null,
            'score_d' => $summary['ranges']['score_d'] ?? null,
            'score_f' => $summary['ranges']['score_f'] ?? null,
            'grade_stds' => [$gradeStd],
        ];
    }

    public function invalidFormatMessage(): string
    {
        return 'ไฟล์ PDF ไม่ตรงรูปแบบใบส่งผลการศึกษาจากสำนักทะเบียน กรุณาดาวน์โหลดใบส่งผลการศึกษาจากระบบทะเบียน มข. ที่ https://reg.kku.ac.th/';
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/[ \t]+/u", ' ', $text) ?? $text;

        return trim($text);
    }

    private function sectionFromFilename(string $filename): ?int
    {
        if (preg_match('/^[A-Z0-9]+-(\d{2})\.pdf$/i', $filename, $match)) {
            return (int) $match[1];
        }

        return null;
    }

    /**
     * @return array{0: string, 1: ?int}
     */
    private function parseTeacherAndSection(string $text, ?int $sectionFallback): array
    {
        $teacher = '';
        $section = $sectionFallback;

        if (preg_match(
            '/ผู้สอน\s+มหาวิทยาลัยขอนแก่น\s+(.+?)\s+กลุ่ม\s*(\d+)/us',
            $text,
            $match
        )) {
            $teacher = $this->normalizeTeacherName($match[1]);
            $section = (int) $match[2];

            return [$teacher, $section];
        }

        $titlePattern = '(?:รศ\.ดร\.|ผศ\.ดร\.|อ\.ดร\.|รศ\.|ผศ\.|อ\.|ศ\.|ดร\.|นาย|นางสาว|นาง)';

        if (preg_match(
            '/('.$titlePattern.'[^\n]+?)\s+กลุ่ม\s*(\d+)/u',
            $text,
            $match
        )) {
            $teacher = $this->normalizeTeacherName($match[1]);
            $section = (int) $match[2];
        }

        return [$teacher, $section];
    }

    private function normalizeTeacherName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', str_replace("\t", ' ', $name)) ?? $name);

        if (str_contains($name, 'อาจารย์ประจำวิชา')) {
            return '';
        }

        return $name;
    }

    /**
     * @return list<array{grade: string}>
     */
    private function parseStudents(string $text): array
    {
        $students = [];
        $pattern = '/(?:<>\s+|\n\s+)(?:(?:พ้นสภาพ[^\n]{0,40}|ลาพักการเรียน)\s+)?((?:นาย|นาง|นางสาว|น\.ส\.|ด\.ช\.|ด\.ญ\.).+?)(A|B\+|B|C\+|C|D\+|D|F|I|S|W|V)(\d{9})-\d+/u';

        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $students[] = ['grade' => $match[2]];
            }
        }

        return $students;
    }

    /**
     * Parse ตารางสรุป % / รวม / MANUAL / เกรด ท้ายไฟล์
     *
     * @return array{counts: array<string, int>, ranges: array<string, string>, uses_decimal: bool}
     */
    private function parseGradeSummary(string $text): array
    {
        $summaryBody = $this->extractGradeSummaryBody($text);
        if ($summaryBody === null) {
            return ['counts' => [], 'ranges' => [], 'uses_decimal' => false];
        }

        $counts = array_fill_keys(array_values(self::GRADE_KEYS), 0);
        $ranges = [];
        $usesDecimal = false;
        $lines = array_filter(array_map('trim', explode("\n", trim($summaryBody))));
        $totalStudents = $this->parseSummaryTotalStudents($lines);

        foreach ($lines as $line) {
            if (str_contains($line, 'รวม')) {
                continue;
            }

            $row = $this->parseSummaryLine($line, $totalStudents);
            if ($row === null) {
                continue;
            }

            if ($row['uses_decimal']) {
                $usesDecimal = true;
            }

            $field = self::GRADE_KEYS[$row['grade']] ?? null;
            if ($field !== null) {
                $counts[$field] = $row['count'];
            }

            $scoreField = self::SCORE_KEYS[$row['grade']] ?? null;
            if ($scoreField && $row['min'] !== null && $row['max'] !== null) {
                $ranges[$scoreField] = $this->formatScoreRange($row['max'], $row['min'], $row['uses_decimal']);
            }
        }

        return ['counts' => $counts, 'ranges' => $ranges, 'uses_decimal' => $usesDecimal];
    }

    /**
     * @param  list<string>  $lines
     */
    private function parseSummaryTotalStudents(array $lines): ?int
    {
        foreach ($lines as $line) {
            if (preg_match('/(\d+\.\d{2})(\d+)รวม/u', $line, $totalMatch)) {
                return (int) $totalMatch[2];
            }
        }

        return null;
    }

    private function formatScoreRange(float $max, float $min, bool $decimal): string
    {
        if ($decimal) {
            return sprintf('%s-%s', $this->formatBoundary($max), $this->formatBoundary($min));
        }

        return sprintf('%d-%d', (int) $max, (int) $min);
    }

    private function formatBoundary(float $value): string
    {
        if (fmod($value, 1.0) === 0.0) {
            return number_format($value, 2, '.', '');
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /**
     * @return array{percent: float, count: int, min: ?float, max: ?float, grade: string, uses_decimal: bool}|null
     */
    private function parseSummaryLine(string $line, ?int $totalStudents): ?array
    {
        if (str_contains($line, '<<->>')) {
            if (preg_match('/^(\d+\.\d{2})(\d+)<<->>W$/u', $line, $match)) {
                return [
                    'percent' => (float) $match[1],
                    'count' => (int) $match[2],
                    'min' => null,
                    'max' => null,
                    'grade' => 'W',
                    'uses_decimal' => false,
                ];
            }

            return null;
        }

        if (preg_match('/^(.+?)\s*>>\s*A$/u', $line, $match)) {
            $split = $this->splitCountAndMin($match[1], 100.0, $totalStudents, 'A');
            if ($split === null) {
                return null;
            }

            return [
                'percent' => $split['percent'],
                'count' => $split['count'],
                'min' => $split['min'],
                'max' => 100.0,
                'grade' => 'A',
                'uses_decimal' => $split['min'] != floor($split['min']),
            ];
        }

        if (preg_match('/^(.+)\s+-\s+(\d+(?:\.\d+)?)\s*(A)$/u', $line, $match)) {
            $split = $this->splitCountAndMin($match[1], (float) $match[2], $totalStudents, 'A');
            if ($split === null) {
                return null;
            }

            return [
                'percent' => $split['percent'],
                'count' => $split['count'],
                'min' => $split['min'],
                'max' => (float) $match[2],
                'grade' => 'A',
                'uses_decimal' => $split['min'] != floor($split['min']),
            ];
        }

        if (preg_match('/^(.+)\s+-\s+(\d+(?:\.\d+)?)(A|B\+|B|C\+|C|D\+|D|F)$/u', $line, $match)) {
            $split = $this->splitCountAndMin($match[1], (float) $match[2], $totalStudents, $match[3]);
            if ($split === null) {
                return null;
            }

            $usesDecimal = str_contains($match[1], '.') && (
                str_contains($match[2], '.') || $split['min'] != floor($split['min'])
            );

            return [
                'percent' => $split['percent'],
                'count' => $split['count'],
                'min' => $split['min'],
                'max' => (float) $match[2],
                'grade' => $match[3],
                'uses_decimal' => $usesDecimal || str_contains($match[2], '.'),
            ];
        }

        return null;
    }

    /**
     * @return array{percent: float, count: int, min: float}|null
     */
    private function extractGradeSummaryBody(string $text): ?string
    {
        if (! preg_match_all('/%รวมMANUALเกรด(.+?)(?:controlcode|CONTROL CODE)/isu', $text, $matches)) {
            return null;
        }

        $bodies = $matches[1];
        $bestBody = null;
        $bestScore = -1;

        foreach ($bodies as $body) {
            $body = trim($body);
            $score = 0;

            if (preg_match('/\d+\.\d+\d+รวม/u', $body)) {
                $score += 100;
            }

            foreach (array_filter(array_map('trim', explode("\n", $body))) as $line) {
                if ($this->parseSummaryLine($line, null) !== null) {
                    $score++;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestBody = $body;
            }
        }

        return $bestBody;
    }

    private function splitCountAndMin(string $left, float $max, ?int $totalStudents, string $grade): ?array
    {
        if (! preg_match('/^(\d+\.\d{2})(\d+)$/u', $left, $parts)) {
            return null;
        }

        $percent = (float) $parts[1];
        $rest = $parts[2];

        // เมื่อรู้จำนวนรวมจากแถวสรุป — จำนวนต่อเกรด = round(% × รวม / 100)
        // ตามคอลัมน์ "รวม" ในตารางท้ายไฟล์ (ไม่เดาจากหลักที่ต่อกันซึ่งกำกวม)
        if ($totalStudents !== null) {
            $expected = $percent < 0.005
                ? 0
                : (int) round($totalStudents * $percent / 100);

            $min = $this->minScoreAfterCount($rest, $expected, $max);
            if ($min !== null) {
                return [
                    'percent' => $percent,
                    'count' => $expected,
                    'min' => $min,
                ];
            }
        }

        $candidates = [];

        for ($minLen = 1; $minLen <= 4; $minLen++) {
            if (strlen($rest) <= $minLen) {
                continue;
            }
            $min = (float) substr($rest, -$minLen);
            $count = (int) substr($rest, 0, -$minLen);
            if ($min <= $max && $count >= 0) {
                $candidates[] = [
                    'percent' => $percent,
                    'count' => $count,
                    'min' => $min,
                ];
            }
        }

        if ($candidates === []) {
            return null;
        }

        if ($grade === 'F' || $max <= 29) {
            usort($candidates, fn (array $a, array $b): int => $b['count'] <=> $a['count']);

            return $candidates[0];
        }

        usort($candidates, function (array $a, array $b): int {
            $diff = $b['min'] <=> $a['min'];
            if ($diff !== 0) {
                return $diff;
            }

            return $b['count'] <=> $a['count'];
        });

        return $candidates[0];
    }

    /**
     * แยกคะแนนต่ำสุดออกจากหลักที่เหลือ หลังลบจำนวนคน (จากตารางสรุป) แล้ว
     */
    private function minScoreAfterCount(string $rest, int $count, float $max): ?float
    {
        if ($count === 0) {
            // เช่น 0.00038 → rest "038" = min 38, หรือ 0.0000 → rest "00" = min 0
            if ($rest === '' || preg_match('/^0+$/', $rest)) {
                return 0.0;
            }
            $min = (float) $rest;
            if ($min <= $max) {
                return $min;
            }

            return null;
        }

        $prefix = (string) $count;
        if (str_starts_with($rest, $prefix) && strlen($rest) > strlen($prefix)) {
            $min = (float) substr($rest, strlen($prefix));
            if ($min <= $max) {
                return $min;
            }
        }

        // fallback: หา min ที่เหลือเมื่อบังคับ count ตามตารางสรุป
        for ($minLen = 1; $minLen <= strlen($rest); $minLen++) {
            $countDigits = substr($rest, 0, -$minLen);
            if ($countDigits === '' || (int) $countDigits !== $count) {
                continue;
            }
            $min = (float) substr($rest, -$minLen);
            if ($min <= $max) {
                return $min;
            }
        }

        // ยังแยก min ไม่ได้ — ใช้ count จากสรุป และเดา min จากท้ายสุดที่ยัง <= max
        for ($minLen = 1; $minLen <= min(4, strlen($rest)); $minLen++) {
            $min = (float) substr($rest, -$minLen);
            if ($min <= $max) {
                return $min;
            }
        }

        return 0.0;
    }

    /**
     * อ่านคณะของนักศึกษาจากหัวข้อกลุ่มในเนื้อหา (บรรทัดขึ้นต้นด้วย "คณะ...")
     * ไม่ใช้บรรทัดหัวกระดาษเช่น "ปริญญาตรี ภาคปกติ คณะวิทยาศาสตร์" ซึ่งเป็นคณะเจ้าของรายวิชา
     *
     * @return list<string>
     */
    private function parseFaculties(string $text): array
    {
        $codes = [];

        foreach (explode("\n", $text) as $line) {
            $line = trim($line);
            if (! preg_match('/^คณะ(.+)$/u', $line, $match)) {
                continue;
            }

            $name = trim($match[1]);
            // ข้ามบรรทัดที่ปนข้อมูลนักศึกษา / ไม่ใช่หัวข้อคณะ
            if ($name === '' || preg_match('/(?:นาย|นาง|นางสาว|<>|\d{9})/u', $name)) {
                continue;
            }

            $code = $this->mapFacultyName($name);
            if ($code !== null) {
                $codes[$code] = true;
            }
        }

        return array_keys($codes);
    }

    private function mapFacultyName(string $name): ?string
    {
        foreach (self::FACULTY_MAP as $thai => $code) {
            if ($name === $thai || str_starts_with($name, $thai)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * ชื่อไฟล์มาตรฐานจากเนื้อหาที่อ่านได้ เช่น SC101011-01.pdf
     */
    public function canonicalFilename(string $subjectCode, int $section): string
    {
        $code = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $subjectCode) ?: 'SUBJECT');

        return sprintf('%s-%02d.pdf', $code, $section);
    }

    private function parseDegree(string $text): int
    {
        if (str_contains($text, 'ปริญญาเอก')) {
            return 7;
        }
        if (str_contains($text, 'ปริญญาโท')) {
            return 5;
        }

        return 3;
    }

    private function parseTypeCourse(string $text): int
    {
        return match (true) {
            str_contains($text, 'โครงการพิเศษ นานาชาติ') => 5,
            str_contains($text, 'ปกติ นานาชาติ') => 4,
            str_contains($text, 'โครงการพิเศษ') => 2,
            str_contains($text, 'ก้าวหน้า') => 3,
            default => 1,
        };
    }
}
