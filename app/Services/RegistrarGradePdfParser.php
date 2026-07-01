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

    private const FACULTY_MAP = [
        'วิทยาศาสตร์' => 'SC',
        'วิศวกรรมศาสตร์' => 'EN',
        'เกษตรศาสตร์' => 'AG',
        'ศึกษาศาสตร์' => 'ED',
        'พยาบาลศาสตร์' => 'NU',
        'แพทยศาสตร์' => 'MD',
        'เภสัชศาสตร์' => 'PH',
        'ทันตแพทยศาสตร์' => 'DN',
        'สาธารณสุขศาสตร์' => 'HS',
        'นิติศาสตร์' => 'LA',
        'เศรษฐศาสตร์' => 'EC',
        'บริหารธุรกิจ' => 'BA',
        'บัณฑิตวิทยาลัย' => 'GS',
        'มนุษยศาสตร์และสังคมศาสตร์' => 'HU',
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

        try {
            $text = $this->parser->parseFile($absolutePath)->getText();
        } catch (\Throwable) {
            throw new RegistrarPdfParseException($this->invalidFormatMessage());
        }

        $text = $this->normalizeText($text);

        if (! str_contains($text, 'ใบส่งผลการศึกษา')) {
            throw new RegistrarPdfParseException($this->invalidFormatMessage());
        }

        if (! preg_match('/controlcode\s*:\s*(\d+)/iu', $text, $controlMatch)
            && ! preg_match('/CONTROL\s+CODE\s*:\s*(\d+)/iu', $text)) {
            throw new RegistrarPdfParseException($this->invalidFormatMessage());
        }

        if (! preg_match('/^([A-Z0-9]+)\s*:\s*(.+)$/mu', $text, $subjectMatch)) {
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

        $teacher = '';
        $section = $this->sectionFromFilename($originalFilename);
        if (preg_match('/((?:รศ\.|ผศ\.|ศ\.|ดร\.|นาย|นาง|นางสาว)[^\n]+?)\s+กลุ่ม\s*(\d+)/u', $text, $teacherMatch)) {
            $teacher = trim(preg_replace('/\s+/u', ' ', $teacherMatch[1]));
            $section = (int) $teacherMatch[2];
        } elseif ($section === null) {
            throw new RegistrarPdfParseException($this->invalidFormatMessage());
        }

        if ($this->parseStudents($text) === []) {
            throw new RegistrarPdfParseException($this->invalidFormatMessage());
        }

        $summary = $this->parseGradeSummary($text);
        if ($summary['counts'] === []) {
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
            'intflag' => 0,
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
     * @return list<array{grade: string}>
     */
    private function parseStudents(string $text): array
    {
        $students = [];
        $pattern = '/(?:<>\s+|\n\s+)((?:นาย|นาง|นางสาว|น\.ส\.|ด\.ช\.|ด\.ญ\.).+?)(A|B\+|B|C\+|C|D\+|D|F|I|S|W|V)(\d{9})-\d+/u';

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
     * @return array{counts: array<string, int>, ranges: array<string, string>}
     */
    private function parseGradeSummary(string $text): array
    {
        if (! preg_match('/%รวมMANUALเกรด(.+?)(?:controlcode|CONTROL CODE)/isu', $text, $blockMatch)) {
            return ['counts' => [], 'ranges' => []];
        }

        $counts = array_fill_keys(array_values(self::GRADE_KEYS), 0);
        $ranges = [];
        $lines = array_filter(array_map('trim', explode("\n", trim($blockMatch[1]))));

        foreach ($lines as $line) {
            if (str_contains($line, 'รวม')) {
                continue;
            }

            $row = $this->parseSummaryLine($line);
            if ($row === null) {
                continue;
            }

            $field = self::GRADE_KEYS[$row['grade']] ?? null;
            if ($field) {
                $counts[$field] = $row['count'];
            }

            $scoreField = self::SCORE_KEYS[$row['grade']] ?? null;
            if ($scoreField && $row['min'] !== null && $row['max'] !== null) {
                // ฟอร์มเก็บรูปแบบ ขอบเขตบน-ขอบเขตล่าง (max-min)
                $ranges[$scoreField] = sprintf('%d-%d', $row['max'], $row['min']);
            }
        }

        return ['counts' => $counts, 'ranges' => $ranges];
    }

    /**
     * @return array{percent: string, count: int, min: ?int, max: ?int, grade: string}|null
     */
    private function parseSummaryLine(string $line): ?array
    {
        if (str_contains($line, '<<->>')) {
            if (preg_match('/^(\d+\.\d{2})(\d+)<<->>W$/u', $line, $match)) {
                return [
                    'percent' => $match[1],
                    'count' => (int) $match[2],
                    'min' => null,
                    'max' => null,
                    'grade' => 'W',
                ];
            }

            return null;
        }

        if (! preg_match('/^(.+)\s+-\s+(\d{1,3})(A|B\+|B|C\+|C|D\+|D|F)$/u', $line, $match)) {
            return null;
        }

        $left = $match[1];
        $max = (int) $match[2];
        $grade = $match[3];

        if (! preg_match('/^(\d+\.\d{2})(\d+)$/u', $left, $parts)) {
            return null;
        }

        $rest = $parts[2];
        $percent = $parts[1];
        $candidates = [];

        for ($minLen = 1; $minLen <= 3; $minLen++) {
            if (strlen($rest) <= $minLen) {
                continue;
            }
            $min = (int) substr($rest, -$minLen);
            $count = (int) substr($rest, 0, -$minLen);
            if ($count > 0 && $min <= $max) {
                $candidates[] = [
                    'percent' => $percent,
                    'count' => $count,
                    'min' => $min,
                    'max' => $max,
                    'grade' => $grade,
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

        usort($candidates, fn (array $a, array $b): int => $b['min'] <=> $a['min']);

        return $candidates[0];
    }

    /**
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
            foreach (self::FACULTY_MAP as $thai => $code) {
                if (str_contains($match[1], $thai)) {
                    $codes[$code] = true;
                }
            }
        }

        if ($codes !== []) {
            return array_keys($codes);
        }

        if (preg_match_all('/คณะ([^\n]+)/u', $text, $matches)) {
            foreach ($matches[1] as $name) {
                $name = trim($name);
                foreach (self::FACULTY_MAP as $thai => $code) {
                    if (str_contains($name, $thai)) {
                        $codes[$code] = true;
                    }
                }
            }
        }

        return array_keys($codes);
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
