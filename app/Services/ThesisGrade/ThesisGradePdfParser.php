<?php

namespace App\Services\ThesisGrade;

use App\Support\ThesisCourse;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Smalot\PdfParser\Parser;

class ThesisGradePdfParseException extends RuntimeException {}

class ThesisGradePdfParser
{
    public const SUBJECT_CHOICES = [
        'THESIS',
        'INDEPENDENT STUDY',
        'DISSERTATION',
    ];

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
            throw new ThesisGradePdfParseException('อ่านไฟล์ PDF ไม่ได้');
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
                throw new ThesisGradePdfParseException('อ่านข้อความจาก PDF ไม่สำเร็จ');
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
     *     warnings: list<string>
     * }
     */
    public function parseText(string $rawText, string $originalFilename, int $termFallback, int $yearFallback): array
    {
        $text = $this->normalizeText($rawText);
        $warnings = [];

        if ($text === '') {
            throw new ThesisGradePdfParseException('ไฟล์ PDF ไม่มีข้อความให้อ่าน');
        }

        $fromName = $this->parseFilename($originalFilename);

        $subjectCode = $fromName['subject_code'] ?? '';
        $subjectRaw = '';
        if (preg_match('/(?:^|\n)\s*([A-Z]{2}\d{5,8})\s*:\s*([^\n]+)/u', $text, $m)) {
            $subjectCode = strtoupper(trim($m[1]));
            $subjectRaw = trim($m[2]);
        } elseif ($subjectCode === '') {
            throw new ThesisGradePdfParseException('ไม่พบรหัสวิชาในไฟล์ PDF');
        }

        $subject = $this->normalizeSubjectChoice($subjectRaw !== '' ? $subjectRaw : ($fromName['subject'] ?? 'THESIS'));
        if ($subject === null) {
            $subject = 'THESIS';
            $warnings[] = 'ไม่สามารถจับชนิดวิชาจาก PDF ได้ — ตั้งเป็น THESIS ให้แก้ไขเอง';
        }

        $term = $termFallback;
        $year = $yearFallback;
        if (preg_match('/ภาคการศึกษาที่\s*(\d+)\s*\/\s*(\d{4})/u', $text, $tm)) {
            $term = (int) $tm[1];
            $year = (int) $tm[2];
        } elseif (! empty($fromName['term']) && ! empty($fromName['year'])) {
            $term = (int) $fromName['term'];
            $year = (int) $fromName['year'];
        }

        $section = $fromName['section'] ?? null;
        if (preg_match('/กลุ่ม(?:เรียน)?\s*[:：]?\s*(\d{1,2})/u', $text, $sm)) {
            $section = str_pad((string) ((int) $sm[1]), 2, '0', STR_PAD_LEFT);
        }
        if ($section === null && preg_match('/Sec(?:tion)?\s*[:：]?\s*(\d{1,2})/iu', $text, $sm)) {
            $section = str_pad((string) ((int) $sm[1]), 2, '0', STR_PAD_LEFT);
        }
        if ($section === null) {
            $section = '01';
            $warnings[] = 'ไม่พบกลุ่มเรียนในไฟล์ — ตั้งเป็น 01 ให้แก้ไขเอง';
        } else {
            $section = str_pad((string) ((int) preg_replace('/\D/', '', (string) $section) ?: 1), 2, '0', STR_PAD_LEFT);
        }

        $teacher = null;
        if (preg_match('/(?:รศ\.|ผศ\.|ศ\.|อ\.|ดร\.|Asst\.|Assoc\.|Prof\.)[^\n\t]+/u', $text, $teach)) {
            $teacher = trim(preg_replace('/\s+/', ' ', $teach[0]) ?? '');
            $teacher = trim(preg_replace('/\s*กลุ่ม\s*\d+.*$/u', '', $teacher) ?? '');
        } elseif (preg_match('/(?:อาจารย์|Instructor|Teacher)\s*[:：]\s*([^\n]+)/iu', $text, $teach)) {
            $teacher = trim($teach[1]);
        }

        // "รศ.ดร.xxx\tกลุ่ม 1"
        if ($section === '01' && preg_match('/กลุ่ม\s*(\d{1,2})/u', $text, $sm)) {
            $section = str_pad((string) ((int) $sm[1]), 2, '0', STR_PAD_LEFT);
        }

        $students = $this->parseStudents($text);
        if ($students === []) {
            $warnings[] = 'ไม่พบรายชื่อนักศึกษาใน PDF — กรุณาเพิ่มเองในขั้นตอนถัดไป';
        }

        return [
            'subject_code' => $subjectCode,
            'subject' => $subject,
            'term' => $term,
            'year' => $year,
            'section' => $section,
            'teacher' => $teacher !== '' ? $teacher : null,
            'students' => $students,
            'warnings' => $warnings,
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
     * @return array{subject_code?: string, section?: string, term?: int, year?: int, subject?: string}
     */
    private function parseFilename(string $filename): array
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        // TS-SC057898-01-2-2568 or SC057898-01-2-2568
        if (preg_match('/^(?:TS-)?([A-Z]{2}\d{5,8})-(\d{1,2})-(\d)-(\d{4})/i', $base, $m)) {
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

        // รูปแบบ REG ที่ข้อความติดกัน เช่น "นางสาว...ภาณุมาศS11655020091-41"
        if (preg_match_all('/([ก-๙A-Za-z.\s]+?)\s*([SUIW])\s*(\d{9,11})\b/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $name = trim(preg_replace('/\s+/', ' ', $m[1]) ?? '');
                $name = preg_replace('/^(?:<>|%|#)+/', '', $name) ?? $name;
                $name = trim($name);
                if ($name === '' || mb_strlen($name) < 2) {
                    continue;
                }
                if (preg_match('/(หมายเหตุ|รหัสประจำตัว|ลำดับ|ผู้สอน|รายวิชา|CONTROL)/iu', $name)) {
                    continue;
                }

                $code = $m[3];
                $grade = strtoupper($m[2]);
                $students[$code] = [
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
            if (preg_match('/\b(S|U|I|W)\b/u', $line, $g)) {
                $grade = strtoupper($g[1]);
            } elseif (preg_match('/([SUIW])'.preg_quote($code, '/').'/', $line, $g)) {
                $grade = strtoupper($g[1]);
            }

            $name = trim(preg_replace('/\s+/', ' ', $line) ?? '');
            $name = preg_replace('/'.preg_quote($code, '/').'/', '', $name, 1) ?? $name;
            $name = preg_replace('/\b(S|U|I|W)\b/u', '', $name) ?? $name;
            $name = preg_replace('/[<>%\-]+/', ' ', $name) ?? $name;
            $name = trim(preg_replace('/[\d.]+/', ' ', $name) ?? '');
            $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
            $name = preg_replace('/(หมายเหตุ|ชื่อ-สกุล|เกรด|ผ่าน|ลง|รหัสประจำตัว|ลำดับ)/u', '', $name) ?? $name;
            $name = trim($name);

            if ($name === '' || mb_strlen($name) < 2) {
                $name = 'นักศึกษา '.$code;
            }

            $students[$code] = [
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

        return array_values($students);
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
