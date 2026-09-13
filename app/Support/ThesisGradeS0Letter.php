<?php

namespace App\Support;

use App\Models\ThesisGrade;
use App\Models\ThesisGradeStudent;

/**
 * ข้อความที่เติมในแบบฟอร์มบันทึกชี้แจง S=0 จากรายวิชาที่กำลังรายงาน
 */
class ThesisGradeS0Letter
{
    /**
     * @return array{
     *     subject_line: string,
     *     subject_code: string,
     *     subject: string,
     *     course_kind_th: string,
     *     section: string,
     *     term_label: string,
     *     year: int|string,
     *     teacher: string,
     *     student_code: string,
     *     student_name: string,
     *     degree: string,
     *     thesis_terms: int|string,
     *     proposal_status: string,
     *     grade: string,
     *     credits_passed: string,
     *     note: string
     * }
     */
    public static function fields(ThesisGrade $report, ?ThesisGradeStudent $student = null): array
    {
        $credits = $student?->credits_passed ?? $student?->progress_credits;

        return [
            'subject_line' => 'ชี้แจงการให้เกรด S = 0 ในรายวิชา '.$report->displayCode().' '.$report->subject,
            'subject_code' => $report->displayCode(),
            'subject' => (string) $report->subject,
            'course_kind_th' => self::courseKindThai($report),
            'section' => $report->paddedSection(),
            'term_label' => $report->termLabel(),
            'year' => $report->year,
            'teacher' => trim((string) ($report->teacher ?: $report->username)),
            'student_code' => (string) ($student?->student_code ?? ''),
            'student_name' => (string) ($student?->displayName() ?? ''),
            'degree' => (string) ($student?->degreeLabel() ?? ''),
            'thesis_terms' => $student?->thesis_terms_count ?? '',
            'proposal_status' => $student === null
                ? ''
                : ($student->proposal_approved ? 'ได้รับอนุมัติเค้าโครงแล้ว' : 'ยังไม่ได้รับอนุมัติเค้าโครง'),
            'grade' => strtoupper((string) ($student?->grade ?: 'S')),
            'credits_passed' => $credits === null || $credits === '' ? '0' : (string) $credits,
            'note' => (string) (ThesisGradeStudent::sanitizeNote($student?->note) ?? ''),
        ];
    }

    private static function courseKindThai(ThesisGrade $report): string
    {
        $kind = $report->course_kind ?: ThesisCourse::courseKind((string) $report->subject);

        return match ($kind) {
            'dissertation' => 'ดุษฎีนิพนธ์',
            'independent_study' => 'การศึกษาอิสระ',
            'thesis' => 'วิทยานิพนธ์',
            default => (string) $report->subject,
        };
    }
}
