<?php

namespace Tests\Feature;

use App\Models\ThesisGrade;
use App\Models\ThesisGradeFile;
use App\Models\ThesisGradeStudent;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacultyThesisGradeS0DocumentsTest extends TestCase
{
    #[Test]
    public function s0_documents_page_lists_memos_for_graduate_faculty_admin(): void
    {
        $this->actingAs(new User(['name' => 'เจ้าหน้าที่ บัณฑิตศึกษา', 'email' => 'grad@kku.ac.th']));

        $report = new ThesisGrade([
            'subject_code' => 'SC899001',
            'subject' => 'THESIS',
            'section' => '1',
            'term' => 2,
            'year' => 2568,
            'status' => ThesisGrade::STATUS_SUBMITTED,
            'teacher' => 'อาจารย์ ทดสอบ',
        ]);
        $report->thesis_grade_id = 40;

        $student = new ThesisGradeStudent([
            'student_code' => '677020018-0',
            'student_name' => 'ทดสอบ ระบบ',
            'grade' => 'S',
            'credits_passed' => 0,
        ]);
        $student->student_id = 77;

        $file = new ThesisGradeFile([
            'thesis_grade_id' => 40,
            'student_id' => 77,
            'file_type' => ThesisGradeFile::TYPE_S0_LETTER,
            'original_name' => 's0-memo.pdf',
        ]);
        $file->file_id = 88;

        $report->setRelation('students', collect([$student]));
        $report->setRelation('files', collect([$file]));

        $paginator = new LengthAwarePaginator([$report], 1, 20, 1);

        $html = view('faculty-admin.thesis-grades.s0-documents', [
            'reports' => $paginator,
            'departments' => collect(),
            'filters' => ['term' => 2, 'year' => 2568, 'status' => '', 'department_id' => null, 'subject_code' => '', 'q' => ''],
            'years' => [2568],
        ])->render();

        $this->assertStringContainsString('รับเอกสารบันทึกข้อความชี้แจง S=0', $html);
        $this->assertStringContainsString('เฉพาะเจ้าหน้าที่งานบริการ (บัณฑิตศึกษา) และ Super Admin', $html);
        $this->assertStringContainsString('677020018-0', $html);
        $this->assertStringContainsString('s0-memo.pdf', $html);
        $this->assertStringContainsString('มีบันทึกแล้ว', $html);
    }
}
