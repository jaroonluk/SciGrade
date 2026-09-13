<?php

namespace Tests\Feature;

use App\Models\ThesisGrade;
use App\Models\ThesisGradeFile;
use App\Models\ThesisGradeStudent;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThesisGradeInstructorIndexFilesTest extends TestCase
{
    #[Test]
    public function instructor_list_shows_own_files_and_department_files(): void
    {
        $this->actingAs(new User(['name' => 'อาจารย์ ทดสอบ', 'email' => 'teacher@kku.ac.th']));

        $report = new ThesisGrade([
            'subject_code' => 'SC899001',
            'subject' => 'THESIS',
            'section' => '1',
            'term' => 2,
            'year' => 2568,
            'status' => ThesisGrade::STATUS_SUBMITTED,
        ]);
        $report->thesis_grade_id = 22;
        $report->setRelation('students', collect([
            new ThesisGradeStudent(['student_code' => '677020018-0']),
        ]));

        $ts = new ThesisGradeFile([
            'thesis_grade_id' => 22,
            'file_type' => ThesisGradeFile::TYPE_TS_REPORT,
            'original_name' => 'TS-SC899001-01-2-2568.pdf',
        ]);
        $ts->file_id = 11;
        $chair = new ThesisGradeFile([
            'thesis_grade_id' => 22,
            'file_type' => ThesisGradeFile::TYPE_CHAIR_SIGNED,
            'original_name' => 'dept-admin-signed.pdf',
        ]);
        $chair->file_id = 21;
        $report->setRelation('files', collect([$ts, $chair]));

        $html = view('thesis-grades.index', [
            'reports' => collect([$report]),
            'term' => 2,
            'year' => 2568,
            'years' => [2568],
            'staffDisplayName' => 'อาจารย์ ทดสอบ',
        ])->render();

        $this->assertStringContainsString('ไฟล์ที่คุณอัปโหลด', $html);
        $this->assertStringContainsString('TS-SC899001-01-2-2568.pdf', $html);
        $this->assertStringContainsString('เอกสารจาก Admin สาขา', $html);
        $this->assertStringContainsString('dept-admin-signed.pdf', $html);
        $this->assertStringContainsString('รอสาขากดผ่านที่ประชุมสาขาวิชา', $html);
    }

    #[Test]
    public function instructor_list_says_department_upload_is_optional_when_missing(): void
    {
        $this->actingAs(new User(['name' => 'อาจารย์ ทดสอบ', 'email' => 'teacher@kku.ac.th']));

        $report = new ThesisGrade([
            'subject_code' => 'SC899001',
            'subject' => 'THESIS',
            'section' => '1',
            'term' => 2,
            'year' => 2568,
            'status' => ThesisGrade::STATUS_SUBMITTED,
        ]);
        $report->thesis_grade_id = 23;
        $report->setRelation('students', collect());
        $report->setRelation('files', collect());

        $html = view('thesis-grades.index', [
            'reports' => collect([$report]),
            'term' => 2,
            'year' => 2568,
            'years' => [2568],
            'staffDisplayName' => 'อาจารย์ ทดสอบ',
        ])->render();

        $this->assertStringContainsString('สาขายังไม่ได้อัปโหลดเอกสารเพิ่ม — ไม่บังคับ', $html);
        $this->assertStringContainsString('รอสาขากดผ่านที่ประชุมสาขาวิชา', $html);
    }
}
