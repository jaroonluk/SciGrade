<?php

namespace App\Support;

/**
 * ข้อมูลแบบฟอร์มบันทึกชี้แจงให้เกรด I ตาม docs/บันทึกชี้แจงให้เกรด I.docx
 */
class GradeReportILetter
{
    public const EMBLEM_RELATIVE = 'images/s0-letter-emblem.png';

    public const FOOTER_RELATIVE = 'images/s0-letter-footer.png';

    /**
     * @param  array{
     *     subject_code?: string,
     *     subject?: string,
     *     term?: int|string,
     *     year?: int|string,
     *     teacher?: string,
     *     section?: int|string|null,
     *     sections?: list<int|string>|null,
     *     department?: string|null,
     *     reason?: string|null,
     *     students?: list<array{name?: string, student_code?: string, section?: int|string|null}>
     * }  $input
     * @return array{
     *     subject_code: string,
     *     subject: string,
     *     term: int,
     *     year: int|string,
     *     term_short: string,
     *     term_label: string,
     *     teacher: string,
     *     section_label: string,
     *     department: string,
     *     reason: string,
     *     student_count: int,
     *     students: list<array{name: string, student_code: string, reason: string}>,
     *     memo_no: string,
     *     letter_date: string,
     *     to_line: string,
     *     subject_line: string,
     *     body: string,
     *     chair_title: string,
     *     emblem_path: string,
     *     footer_path: string
     * }
     */
    public static function fields(array $input): array
    {
        $subjectCode = strtoupper(trim((string) ($input['subject_code'] ?? '')));
        $subject = trim((string) ($input['subject'] ?? ''));
        $term = (int) ($input['term'] ?? 0);
        $year = $input['year'] ?? '';
        $teacher = trim((string) ($input['teacher'] ?? ''));
        $department = trim((string) ($input['department'] ?? ''));
        $reason = trim((string) ($input['reason'] ?? ''));

        $sections = [];
        if (! empty($input['sections']) && is_array($input['sections'])) {
            foreach ($input['sections'] as $sec) {
                $n = (int) $sec;
                if ($n > 0) {
                    $sections[] = $n;
                }
            }
        } elseif (! empty($input['section'])) {
            $n = (int) $input['section'];
            if ($n > 0) {
                $sections[] = $n;
            }
        }
        $sections = array_values(array_unique($sections));
        sort($sections);
        $sectionLabel = $sections === []
            ? '......'
            : implode(', ', array_map(fn ($s) => (string) (int) $s, $sections));

        $termShort = match ($term) {
            1 => 'ต้น',
            2 => 'ปลาย',
            3 => 'พิเศษ',
            default => '........',
        };
        $termLabel = match ($term) {
            1 => 'ภาคต้น',
            2 => 'ภาคปลาย',
            3 => 'ภาคการศึกษาพิเศษ',
            default => '..................',
        };

        $students = [];
        foreach ($input['students'] ?? [] as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $code = trim((string) ($row['student_code'] ?? ''));
            if ($name === '' && $code === '') {
                continue;
            }
            $students[] = [
                'name' => $name !== '' ? $name : '................................',
                'student_code' => $code !== '' ? $code : '..................',
                'reason' => $reason !== '' ? $reason : '',
            ];
        }

        $count = count($students);
        $deptPart = $department !== '' ? $department : '..................';
        $codePart = $subjectCode !== '' ? $subjectCode : '...............';
        $namePart = $subject !== '' ? $subject : '.................';

        $subjectLine = 'ชี้แจงสาเหตุการให้เกรด I รายวิชา '.$codePart
            .' (รหัสวิชา) '.($subject !== '' ? $subject : '.................')
            .' (ชื่อวิชา) ประจำภาค '.$termLabel
            .' (ต้น/ปลาย) ปีการศึกษา '.($year !== '' ? $year : '......................');

        $body = 'ตามที่นักศึกษาจำนวน '.($count > 0 ? $count : '......').' ราย ที่มีรายชื่อดังต่อไปนี้'
            .' ได้รับการประเมินเกรด I ในรายวิชา '.$codePart
            .' (รหัสวิชา) '.$namePart
            .' (ชื่อวิชา) กลุ่มที่ '.$sectionLabel
            .' ประจำภาค '.$termLabel
            .' (ต้น/ปลาย) ปีการศึกษา '.($year !== '' ? $year : '...........')
            .' อาจารย์ประจำวิชา/อาจารย์ที่ปรึกษาโครงงาน ใคร่ขอชี้แจงสาเหตุที่นักศึกษา'
            .' แต่ละรายไม่สามารถปฏิบัติงานได้ครบตามเงื่อนไขที่อาจารย์ผู้สอนกำหนด'
            .' ด้วยเหตุจำเป็นหรือสุดวิสัยดังต่อไปนี้';

        return [
            'subject_code' => $subjectCode,
            'subject' => $subject,
            'term' => $term,
            'year' => $year,
            'term_short' => $termShort,
            'term_label' => $termLabel,
            'teacher' => $teacher,
            'section_label' => $sectionLabel,
            'department' => $department,
            'reason' => $reason,
            'student_count' => $count,
            'students' => $students,
            'memo_no' => 'อว 660301.1.1. /.................',
            'letter_date' => ThaiDateTime::formatLongDate(now()),
            'to_line' => 'คณบดีคณะวิทยาศาสตร์ ผ่านหัวหน้าสาขาวิชา'.$deptPart,
            'subject_line' => $subjectLine,
            'body' => $body,
            'chair_title' => 'หัวหน้าสาขาวิชา'.($department !== '' ? $department : '........'),
            'emblem_path' => public_path(self::EMBLEM_RELATIVE),
            'footer_path' => public_path(self::FOOTER_RELATIVE),
        ];
    }
}
