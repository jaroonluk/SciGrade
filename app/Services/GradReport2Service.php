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
     * @param  list<string>  $jointSubjectCodes
     */
    public function resolveSubjectCode2Multi(string $subjectCode, array $jointSubjectCodes): string
    {
        $subjectCode = $this->normalizeSubjectCode($subjectCode);

        $existing = GradReport2::query()
            ->where('subject_code', $subjectCode)
            ->first();

        if ($existing) {
            return trim((string) $existing->subject_code2);
        }

        foreach ($this->normalizeJointCodes($jointSubjectCodes, $subjectCode) as $jointCode) {
            $jointExisting = GradReport2::query()
                ->where('subject_code', $jointCode)
                ->first();

            if ($jointExisting) {
                return trim((string) $jointExisting->subject_code2);
            }
        }

        return $subjectCode;
    }

    /**
     * จาก project_old/grade_add_new.php — function checksubjectID()
     * เรียกเมื่อ reasonid = 1 (ตัดเกรดร่วมกับ)
     *
     * ถ้ารหัสวิชาหลักมีใน grad_report2 แล้ว แต่ผู้ใช้เพิ่มรหัสวิชาร่วมใหม่
     * จะ insert เพิ่มเข้ากลุ่มเดิม (subject_code2) โดยไม่ทับข้อมูลเดิม
     */
    public function syncJointGradeSubjects(
        string $mainSubjectCode,
        string $subjectName,
        string $username,
        array $jointSubjectCodes,
    ): void {
        $mainCode = $this->normalizeSubjectCode($mainSubjectCode);
        if ($mainCode === '') {
            return;
        }

        $jointCodes = $this->normalizeJointCodes($jointSubjectCodes, $mainCode);
        if ($jointCodes === []) {
            return;
        }

        $subject = mb_strtoupper(trim($subjectName));
        $username = trim($username);

        $mainRow = GradReport2::query()->where('subject_code', $mainCode)->first();

        if ($mainRow) {
            $groupCode = trim((string) $mainRow->subject_code2);

            foreach ($jointCodes as $jointCode) {
                $this->addSubjectToGroupIfMissing($groupCode, $jointCode, $subject, $username);
            }

            return;
        }

        $groupCode = $this->resolveGroupCodeForNewMain($jointCodes);
        $subjectNameForSelf = $this->subjectNameForCode($groupCode, $jointCodes, $subject);

        if (! $this->groupLinkExists($groupCode, $groupCode)) {
            $this->insertGroupRow($groupCode, $groupCode, $subjectNameForSelf, $username);
        }

        foreach ($jointCodes as $jointCode) {
            $this->addSubjectToGroupIfMissing($groupCode, $jointCode, $subject, $username);
        }

        $this->addSubjectToGroupIfMissing($groupCode, $mainCode, $subject, $username);
    }

    /**
     * @param  list<string>  $jointCodes
     */
    private function resolveGroupCodeForNewMain(array $jointCodes): string
    {
        foreach ($jointCodes as $jointCode) {
            $jointRow = GradReport2::query()->where('subject_code', $jointCode)->first();
            if ($jointRow) {
                return trim((string) $jointRow->subject_code2);
            }
        }

        return $jointCodes[0];
    }

    /**
     * @param  list<string>  $jointCodes
     */
    private function subjectNameForCode(string $code, array $jointCodes, string $fallback): string
    {
        $row = GradReport2::query()->where('subject_code', $code)->first();
        if ($row && trim((string) $row->subject) !== '') {
            return mb_strtoupper(trim((string) $row->subject));
        }

        return $fallback;
    }

    private function addSubjectToGroupIfMissing(
        string $groupCode,
        string $subjectCode,
        string $subject,
        string $username,
    ): void {
        $groupCode = $this->normalizeSubjectCode($groupCode);
        $subjectCode = $this->normalizeSubjectCode($subjectCode);

        if ($groupCode === '' || $subjectCode === '') {
            return;
        }

        if ($this->groupLinkExists($groupCode, $subjectCode)) {
            return;
        }

        $this->insertGroupRow($groupCode, $subjectCode, $subject, $username);
    }

    private function groupLinkExists(string $groupCode, string $subjectCode): bool
    {
        return GradReport2::query()
            ->where('subject_code2', $groupCode)
            ->where('subject_code', $subjectCode)
            ->exists();
    }

    private function insertGroupRow(
        string $groupCode,
        string $subjectCode,
        string $subject,
        string $username,
    ): void {
        GradReport2::query()->create([
            'subject_code2' => $groupCode,
            'subject_code' => $subjectCode,
            'subject' => $subject,
            'username' => $username,
        ]);
    }

    /**
     * @param  list<string>  $jointSubjectCodes
     * @return list<string>
     */
    private function normalizeJointCodes(array $jointSubjectCodes, string $mainCode): array
    {
        $codes = array_values(array_unique(array_filter(array_map(
            fn (string $code) => $this->normalizeSubjectCode($code),
            $jointSubjectCodes,
        ))));

        return array_values(array_filter(
            $codes,
            fn (string $code) => $code !== '' && $code !== $mainCode,
        ));
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
