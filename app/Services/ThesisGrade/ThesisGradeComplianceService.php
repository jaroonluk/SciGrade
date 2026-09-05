<?php

namespace App\Services\ThesisGrade;

class ThesisGradeComplianceService
{
    public static function proposalDeadlineTerms(string $degree): int
    {
        return $degree === 'doctoral' ? 4 : 2;
    }

    public static function isProposalOverdue(string $degree, int $terms, bool $proposalApproved): bool
    {
        if ($proposalApproved) {
            return false;
        }

        return $terms >= self::proposalDeadlineTerms($degree);
    }

    public static function isS0(?string $grade, mixed $credits): bool
    {
        if (strtoupper(trim((string) $grade)) !== 'S') {
            return false;
        }

        if ($credits === null || $credits === '') {
            return true;
        }

        return (float) $credits == 0.0;
    }

    public static function requiresS0Letter(
        string $degree,
        int $terms,
        bool $proposalApproved,
        ?string $grade,
        mixed $credits,
    ): bool {
        return self::isProposalOverdue($degree, $terms, $proposalApproved)
            && self::isS0($grade, $credits);
    }

    public static function requiresDefenseDate(bool $completed, mixed $defenseDate): bool
    {
        if (! $completed) {
            return false;
        }

        return trim((string) $defenseDate) === '';
    }

    /**
     * @param  list<array{
     *     student_code?: string,
     *     student_name?: string,
     *     degree: string,
     *     thesis_terms_count: int,
     *     proposal_approved: bool,
     *     grade: ?string,
     *     progress_credits: mixed,
     *     completed: bool,
     *     defense_date: ?string,
     *     has_s0_letter: bool
     * }>  $students
     * @return list<string>
     */
    public function errorsForSubmit(
        bool $hasTsReport,
        bool $checkedProposal,
        bool $checkedSigned,
        array $students,
    ): array {
        $errors = [];

        if ($students === []) {
            $errors[] = 'กรุณาเพิ่มรายชื่อนักศึกษาอย่างน้อย 1 คน';
        }

        if (! $hasTsReport) {
            $errors[] = 'กรุณาอัปโหลดใบส่งเกรดวิทยานิพนธ์ที่ลงนามดิจิทัลแล้ว (ไฟล์ TS)';
        }

        if (! $checkedProposal) {
            $errors[] = 'กรุณายืนยันว่าได้ตรวจสอบกำหนดอนุมัติเค้าโครงวิทยานิพนธ์แล้ว';
        }

        if (! $checkedSigned) {
            $errors[] = 'กรุณายืนยันว่าไฟล์ใบส่งเกรดได้ลงนามด้วยลายมือชื่อดิจิทัลแล้ว';
        }

        foreach ($students as $index => $student) {
            $label = $this->studentLabel($student, $index);

            if (self::requiresS0Letter(
                (string) ($student['degree'] ?? 'master'),
                (int) ($student['thesis_terms_count'] ?? 0),
                (bool) ($student['proposal_approved'] ?? false),
                $student['grade'] ?? null,
                $student['progress_credits'] ?? null,
            ) && empty($student['has_s0_letter'])) {
                $errors[] = $label.'เลยกำหนดอนุมัติเค้าโครงและผลเป็น S=0 — ต้องแนบหนังสือชี้แจง';
            }

            if (self::requiresDefenseDate(
                (bool) ($student['completed'] ?? false),
                $student['defense_date'] ?? null,
            )) {
                $errors[] = $label.'ครบตามหลักสูตรแล้ว — ต้องระบุวันที่สอบวิทยานิพนธ์';
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $student
     */
    private function studentLabel(array $student, int $index): string
    {
        $code = trim((string) ($student['student_code'] ?? ''));
        $name = trim((string) ($student['student_name'] ?? ''));

        if ($code !== '' && $name !== '') {
            return $code.' '.$name;
        }

        if ($code !== '') {
            return $code;
        }

        if ($name !== '') {
            return $name;
        }

        return 'นักศึกษาลำดับที่ '.($index + 1);
    }
}
