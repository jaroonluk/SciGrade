<?php

namespace App\Services;

use App\Models\GradReport2;

class GradReport2Service
{
    /**
     * จาก project_old/grade_add_new.php — function checksubject()
     */
    public function resolveSubjectCode2(string $subjectCode, ?string $jointSubjectCode = null): string
    {
        $subjectCode = $this->normalizeSubjectCode($subjectCode);
        $jointSubjectCode = $jointSubjectCode ? $this->normalizeSubjectCode($jointSubjectCode) : '';

        $existing = GradReport2::query()
            ->where('subject_code', $subjectCode)
            ->first();

        if ($existing) {
            return trim((string) $existing->subject_code2);
        }

        if ($jointSubjectCode !== '') {
            $jointExisting = GradReport2::query()
                ->where('subject_code', $jointSubjectCode)
                ->first();

            if ($jointExisting) {
                return trim((string) $jointExisting->subject_code2);
            }
        }

        return $subjectCode;
    }

    /**
     * จาก project_old/grade_add_new.php — function checksubjectID()
     * เรียกเมื่อ reasonid = 1 (ตัดเกรดร่วมกับ) — ระบบเดิมรองรับวิชาร่วม 1 รหัส (std_i1)
     */
    public function syncJointGradeSubjects(
        string $mainSubjectCode,
        string $subjectName,
        string $username,
        array $jointSubjectCodes,
    ): void {
        $sub1 = $this->normalizeSubjectCode($mainSubjectCode);
        if ($sub1 === '') {
            return;
        }

        if (GradReport2::query()->where('subject_code', $sub1)->exists()) {
            return;
        }

        $jointSubjectCodes = array_values(array_unique(array_filter(array_map(
            fn (string $code) => $this->normalizeSubjectCode($code),
            $jointSubjectCodes,
        ))));

        if ($jointSubjectCodes === []) {
            return;
        }

        $sub2 = $jointSubjectCodes[0];
        $subject = mb_strtoupper(trim($subjectName));
        $username = trim($username);

        $jointRow = GradReport2::query()->where('subject_code', $sub2)->first();

        if (! $jointRow) {
            GradReport2::query()->create([
                'subject_code2' => $sub2,
                'subject_code' => $sub2,
                'subject' => $subject,
                'username' => $username,
            ]);

            GradReport2::query()->create([
                'subject_code2' => $sub2,
                'subject_code' => $sub1,
                'subject' => $subject,
                'username' => $username,
            ]);

            return;
        }

        GradReport2::query()->create([
            'subject_code2' => trim((string) $jointRow->subject_code2),
            'subject_code' => $sub1,
            'subject' => $subject,
            'username' => $username,
        ]);
    }

    public function normalizeSubjectCode(string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($code)) ?? '');
    }

    /**
     * @return list<string>
     */
    public function parseJointCodesFromReason(?string $reason): array
    {
        if ($reason === null || trim($reason) === '') {
            return [];
        }

        $prefixes = ['ตัดเกรดร่วมกับ :', 'ตัดเกรดร่วมกับ:', 'ซ้อนวิชากับ :', 'ซ้อนวิชากับ:'];
        $rest = trim($reason);

        foreach ($prefixes as $prefix) {
            if (str_starts_with($rest, $prefix)) {
                $rest = trim(substr($rest, strlen($prefix)));
                break;
            }
        }

        $codes = [];
        foreach (explode(',', $rest) as $pair) {
            $pair = trim($pair);
            if ($pair === '') {
                continue;
            }

            $pipeIdx = strpos($pair, '|');
            $code = $pipeIdx === false ? $pair : substr($pair, 0, $pipeIdx);
            $normalized = $this->normalizeSubjectCode($code);

            if ($normalized !== '') {
                $codes[] = $normalized;
            }
        }

        return array_values(array_unique($codes));
    }
}
