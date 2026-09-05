<?php

namespace App\Services\ThesisGrade;

class ThesisGradeStudentParser
{
    /**
     * @return list<array{
     *     student_code: string,
     *     student_name: string,
     *     degree: string,
     *     thesis_terms_count: int,
     *     proposal_approved: bool,
     *     grade: string,
     *     progress_credits: ?float,
     *     completed: bool,
     *     defense_date: ?string
     * }>
     */
    public function parsePaste(string $text): array
    {
        $rows = [];

        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $cells = preg_split('/[\t,;|]+/u', $line) ?: [];
            $cells = array_map(fn ($cell) => trim((string) $cell), $cells);

            if ($this->looksLikeHeader($cells)) {
                continue;
            }

            $code = $cells[0] ?? '';
            if ($code === '' || ! preg_match('/[0-9A-Za-z]/', $code)) {
                continue;
            }

            $credits = $cells[6] ?? '';

            $rows[] = [
                'student_code' => $code,
                'student_name' => $cells[1] ?? '',
                'degree' => $this->normalizeDegree($cells[2] ?? ''),
                'thesis_terms_count' => max(1, (int) ($cells[3] ?? 1)),
                'proposal_approved' => $this->normalizeBool($cells[4] ?? ''),
                'grade' => strtoupper(trim((string) ($cells[5] ?? 'S'))) ?: 'S',
                'progress_credits' => $credits === '' ? null : (float) $credits,
                'completed' => $this->normalizeBool($cells[7] ?? ''),
                'defense_date' => $this->normalizeDate($cells[8] ?? ''),
            ];
        }

        return $rows;
    }

    public function normalizeDegree(string $value): string
    {
        $normalized = strtolower(trim($value));

        if ($normalized === '') {
            return 'master';
        }

        if (preg_match('/เอก|doctoral|phd|^d$|p\.?hd/i', $value)) {
            return 'doctoral';
        }

        return 'master';
    }

    public function normalizeBool(string $value): bool
    {
        $normalized = strtolower(trim($value));

        return in_array($normalized, ['1', 'y', 'yes', 'true', 'อนุมัติ', 'ผ่าน', 'ครบ', 'x'], true);
    }

    public function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})$/', $value, $match)) {
            $year = (int) $match[3];
            if ($year > 2400) {
                $year -= 543;
            } elseif ($year < 100) {
                $year += 2000;
            }

            return sprintf('%04d-%02d-%02d', $year, (int) $match[2], (int) $match[1]);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return null;
    }

    /**
     * @param  list<string>  $cells
     */
    private function looksLikeHeader(array $cells): bool
    {
        $first = strtolower($cells[0] ?? '');

        return str_contains($first, 'รหัส') || str_contains($first, 'code') || $first === 'student_code';
    }
}
