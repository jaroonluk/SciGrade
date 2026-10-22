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
        $sectionsFromStudents = [];
        foreach ($input['students'] ?? [] as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $code = trim((string) ($row['student_code'] ?? ''));
            if ($name === '' && $code === '') {
                continue;
            }
            $rowReason = trim((string) ($row['reason'] ?? ''));
            $sec = (int) ($row['section'] ?? 0);
            if ($sec > 0) {
                $sectionsFromStudents[] = $sec;
            }
            $students[] = [
                'name' => $name !== '' ? $name : '................................',
                'student_code' => $code !== '' ? $code : '..................',
                'reason' => $rowReason !== '' ? $rowReason : $reason,
                'section' => $sec > 0 ? $sec : null,
            ];
        }

        // กลุ่มที่... = เฉพาะ Section ที่มีนักศึกษาติด I เท่านั้น
        $sections = array_values(array_unique($sectionsFromStudents));
        sort($sections);
        if ($sections === []) {
            // fallback เมื่อไม่มี section ในรายชื่อนักศึกษา
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
        }
        $sectionLabel = $sections === []
            ? '......'
            : implode(', ', array_map(fn ($s) => (string) (int) $s, $sections));

        $count = count($students);
        $codePart = $subjectCode !== '' ? $subjectCode : '...............';
        $namePart = $subject !== '' ? $subject : '.................';
        $yearPart = $year !== '' ? (string) $year : '...........';

        // ชื่อจาก tbldepartment / เงื่อนไขรหัสวิชา มักเป็น «สาขาวิชา...»
        $departmentFull = self::normalizeDepartmentName($department);
        $chairTitle = self::chairTitleForDepartment($departmentFull);

        // ไม่ใส่คำกำกับในวงเล็บ เช่น (รหัสวิชา) (ชื่อวิชา) (ต้น/ปลาย)
        $subjectLine = 'ชี้แจงสาเหตุการให้เกรด I รายวิชา '.$codePart
            .($subject !== '' ? ' '.$subject : '')
            .' ประจำภาค '.$termLabel
            .' ปีการศึกษา '.$yearPart;

        // เนื้อหาก่อนตาราง: ใส่ชื่ออาจารย์ผู้สอนแทนคำว่า «อาจารย์ประจำวิชา»
        $instructorLabel = $teacher !== '' ? $teacher : '................................';

        $body = 'ตามที่นักศึกษาจำนวน '.($count > 0 ? $count : '......').' ราย ที่มีรายชื่อดังต่อไปนี้'
            .' ได้รับการประเมินเกรด I ในรายวิชา '.$codePart
            .($subject !== '' ? ' '.$namePart : '')
            .' กลุ่มที่ '.$sectionLabel
            .' ประจำภาค '.$termLabel
            .' ปีการศึกษา '.$yearPart
            .' '.$instructorLabel.' ใคร่ขอชี้แจงสาเหตุที่นักศึกษา'
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
            'department' => $departmentFull,
            'reason' => $reason,
            'student_count' => $count,
            'students' => $students,
            'memo_no' => 'อว 660301.1.1. /.................',
            'letter_date' => ThaiDateTime::formatLongDate(now()),
            'to_line' => 'คณบดีคณะวิทยาศาสตร์ ผ่าน'.$chairTitle,
            'subject_line' => $subjectLine,
            'body' => $body,
            'chair_title' => $chairTitle,
            'emblem_path' => public_path(self::EMBLEM_RELATIVE),
            'footer_path' => public_path(self::FOOTER_RELATIVE),
        ];
    }

    /**
     * ปรับชื่อหน่วยงานให้ขึ้นต้นด้วย «สาขาวิชา» เมื่อยังไม่มีคำนำหน้า
     */
    public static function normalizeDepartmentName(?string $department): string
    {
        $name = trim((string) $department);
        if ($name === '') {
            return '';
        }

        if (str_starts_with($name, 'หัวหน้าสาขาวิชา')) {
            $name = trim(mb_substr($name, mb_strlen('หัวหน้าสาขาวิชา')));
        } elseif (str_starts_with($name, 'หัวหน้า')) {
            $name = trim(mb_substr($name, mb_strlen('หัวหน้า')));
        }

        if ($name === '') {
            return '';
        }

        if (
            str_starts_with($name, 'สาขาวิชา')
            || str_starts_with($name, 'หน่วยงาน')
            || str_starts_with($name, 'ศูนย์')
            || str_starts_with($name, 'สำนักงาน')
        ) {
            return $name;
        }

        return 'สาขาวิชา'.$name;
    }

    /**
     * หัวหน้าสาขาวิชา + ชื่อหน่วยงาน (ไม่ซ้ำคำว่า สาขาวิชา)
     */
    public static function chairTitleForDepartment(?string $department): string
    {
        $full = self::normalizeDepartmentName($department);
        if ($full === '') {
            return 'หัวหน้าสาขาวิชา........';
        }

        if (str_starts_with($full, 'หัวหน้า')) {
            return $full;
        }

        return 'หัวหน้า'.$full;
    }
}
