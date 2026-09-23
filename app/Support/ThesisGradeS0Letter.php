<?php

namespace App\Support;

use App\Models\ThesisGrade;
use App\Models\ThesisGradeStudent;
use App\Services\DeptAdmin\DepartmentSubjectFilter;

/**
 * ข้อความที่เติมในแบบฟอร์มบันทึกชี้แจง S=0 ตามไฟล์ตัวอย่าง
 * project_old/reg/2. แบบฟอร์มชี้แจง S=0-2-3  New 2023.docx
 */
class ThesisGradeS0Letter
{
    public const EMBLEM_RELATIVE = 'images/s0-letter-emblem.png';

    /** แบนเนอร์ท้ายกระดาษตามแบบฟอร์มชี้แจง S=0 (SCI KKU / วิสัยทัศน์ / EdPEx) */
    public const FOOTER_RELATIVE = 'images/s0-letter-footer.png';

    /**
     * @return array{
     *     subject_line: string,
     *     subject_code: string,
     *     subject: string,
     *     course_kind_th: string,
     *     program_th: string,
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
     *     note: string,
     *     department: string,
     *     memo_no: string,
     *     letter_date: string,
     *     reason: string,
     *     to_line: string,
     *     body: string,
     *     advisor_title: string,
     *     chair_title: string,
     *     emblem_path: string,
     *     emblem_url: string,
     *     footer_path: string,
     *     footer_url: string
     * }
     */
    public static function fields(ThesisGrade $report, ?ThesisGradeStudent $student = null, ?string $department = null): array
    {
        $credits = $student?->credits_passed ?? $student?->progress_credits;
        $department = self::bareDepartmentName($department ?? self::lookupDepartment($report));
        $program = self::programThai($report, $student);
        $reason = self::reasonText($student);
        $subjectLine = 'ขอชี้แจงการให้เกรด S = 0 ในรายวิชา '.$report->displayCode()
            .($report->subject ? ' '.$report->subject : '')
            .' กลุ่มที่ '.$report->paddedSection();

        $fields = [
            'subject_line' => $subjectLine,
            'subject_code' => $report->displayCode(),
            'subject' => (string) $report->subject,
            'course_kind_th' => self::courseKindThai($report),
            'program_th' => $program,
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
            'department' => $department,
            'memo_no' => 'อว 660301.     /    ',
            'letter_date' => ThaiDateTime::formatLongDate(now()),
            'reason' => $reason,
            'to_line' => 'คณบดีคณะวิทยาศาสตร์ (ผ่านหัวหน้าสาขาวิชา'.($department !== '' ? $department : '..................').')',
            'advisor_title' => self::advisorTitle($report),
            'chair_title' => 'หัวหน้าสาขาวิชา'.($department !== '' ? $department : '........'),
            'emblem_path' => public_path(self::EMBLEM_RELATIVE),
            'emblem_url' => asset(self::EMBLEM_RELATIVE),
            'footer_path' => public_path(self::FOOTER_RELATIVE),
            'footer_url' => asset(self::FOOTER_RELATIVE),
        ];

        $fields['body'] = self::bodyParagraph($fields);

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public static function bodyParagraph(array $fields): string
    {
        $name = $fields['student_name'] !== ''
            ? $fields['student_name']
            : '(นาย/นาง/นางสาว)......................................';
        $code = $fields['student_code'] !== ''
            ? $fields['student_code']
            : '..................';
        $program = $fields['program_th'] !== ''
            ? $fields['program_th']
            : '(วิทยาศาสตรมหาบัณฑิต/ปริญญาดุษฎีบัณฑิต)';
        $dept = $fields['department'] !== ''
            ? $fields['department']
            : '................................';
        $course = trim($fields['subject_code'].' '.$fields['subject']);
        $course .= ' กลุ่มที่ '.$fields['section'].' '.$fields['term_label'].' ปีการศึกษา '.$fields['year'];
        $reason = $fields['reason'] !== ''
            ? $fields['reason']
            : '...............................................';

        return 'ใคร่ขอชี้แจงกรณี '.$name
            .' รหัสประจำตัวนักศึกษา '.$code
            .' นักศึกษาหลักสูตร '.$program
            .' สาขาวิชา'.$dept
            .' ได้รับการประเมินผลในรายวิชา '.$course
            .' เป็น S=0 ด้วยเหตุผลจาก '.$reason;
    }

    private static function reasonText(?ThesisGradeStudent $student): string
    {
        if ($student === null) {
            return '';
        }

        $note = ThesisGradeStudent::sanitizeNote($student->note);
        if ($note !== null && $note !== '') {
            return $note;
        }

        if ($student->proposal_approved) {
            return 'ได้รับอนุมัติเค้าโครงแล้ว';
        }

        if ($student->isProposalOverdue()) {
            return 'ยังไม่ได้รับอนุมัติเค้าโครงภายในกำหนด (ลงทะเบียนครบ '.(int) $student->thesis_terms_count.' ภาคการศึกษา)';
        }

        return 'ยังไม่ได้รับอนุมัติเค้าโครง';
    }

    private static function programThai(ThesisGrade $report, ?ThesisGradeStudent $student): string
    {
        $degree = (string) ($student?->degree ?? '');
        if ($degree === ThesisGradeStudent::DEGREE_DOCTORAL) {
            return 'ปริญญาดุษฎีบัณฑิต';
        }
        if ($degree === ThesisGradeStudent::DEGREE_MASTER) {
            return self::courseKindThai($report) === 'การศึกษาอิสระ'
                ? 'วิทยาศาสตรมหาบัณฑิต (การศึกษาอิสระ)'
                : 'วิทยาศาสตรมหาบัณฑิต';
        }

        return '';
    }

    private static function advisorTitle(ThesisGrade $report): string
    {
        return match (self::courseKindThai($report)) {
            'การศึกษาอิสระ' => 'อาจารย์ที่ปรึกษาการศึกษาอิสระ',
            'ดุษฎีนิพนธ์' => 'อาจารย์ที่ปรึกษาดุษฎีนิพนธ์',
            default => 'อาจารย์ที่ปรึกษาวิทยานิพนธ์',
        };
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

    private static function lookupDepartment(ThesisGrade $report): string
    {
        if (app()->runningUnitTests()) {
            return '';
        }

        try {
            $name = app(DepartmentSubjectFilter::class)->departmentNameForSubject($report->displayCode());
        } catch (\Throwable) {
            return '';
        }

        return trim((string) $name);
    }

    private static function bareDepartmentName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        return trim((string) preg_replace('/^สาขาวิชา\s*/u', '', $name));
    }
}
