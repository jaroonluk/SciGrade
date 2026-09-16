<?php

namespace Tests\Feature;

use App\Models\ThesisGrade;
use App\Models\ThesisGradeFile;
use App\Models\ThesisGradeStudent;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeptAdminThesisGradeIndexTest extends TestCase
{
    #[Test]
    public function list_shows_students_and_compact_files_without_opening_detail(): void
    {
        $this->actingAs(new User(['name' => 'Admin สาขา', 'email' => 'dept@kku.ac.th']));

        $report = new ThesisGrade([
            'subject_code' => 'SC899001',
            'subject' => 'THESIS',
            'section' => '1',
            'term' => 2,
            'year' => 2568,
            'teacher' => 'อ. ทดสอบ',
            'status' => ThesisGrade::STATUS_SUBMITTED,
        ]);
        $report->thesis_grade_id = 22;
        $student = new ThesisGradeStudent([
            'student_code' => '677020018-0',
            'student_name' => 'สมชาย ใจดี',
            'name_prefix' => 'นาย',
            'first_name' => 'สมชาย',
            'last_name' => 'ใจดี',
            'degree' => 'master',
            'thesis_terms_count' => 2,
            'proposal_approved' => true,
            'grade' => 'S',
            'credits_passed' => 3,
        ]);
        $student->student_id = 55;
        $report->setRelation('students', collect([$student]));

        $ts = new ThesisGradeFile([
            'thesis_grade_id' => 22,
            'file_type' => ThesisGradeFile::TYPE_TS_REPORT,
            'original_name' => 'TS-SC899001-01-2-2568.pdf',
        ]);
        $ts->file_id = 11;
        $chair = new ThesisGradeFile([
            'thesis_grade_id' => 22,
            'file_type' => ThesisGradeFile::TYPE_CHAIR_SIGNED,
            'original_name' => 'chair-signed.pdf',
        ]);
        $chair->file_id = 21;
        $report->setRelation('files', collect([$ts, $chair]));

        $html = view('dept-admin.thesis-grades.index', [
            'reports' => new LengthAwarePaginator(collect([$report]), 1, 20, 1, [
                'path' => '/dept-admin/thesis-grades',
            ]),
            'departments' => collect(),
            'filters' => ['term' => 2, 'year' => 2568, 'status' => ''],
            'years' => [2568],
        ])->render();

        $this->assertStringContainsString('ผ่านที่ประชุมสาขาวิชา', $html);
        $this->assertStringContainsString('รายชื่อนักศึกษา', $html);
        $this->assertStringContainsString('stu-name', $html);
        $this->assertStringContainsString('นาย สมชาย ใจดี', $html);
        $this->assertStringContainsString('677020018-0', $html);
        $this->assertStringContainsString('ใบ TS', $html);
        $this->assertStringContainsString('เอกสารสาขา', $html);
        $this->assertStringContainsString('TS-SC899001-01-2-2568.pdf', $html);
        $this->assertStringContainsString('chair-signed.pdf', $html);
        $this->assertStringContainsString(route('dept-admin.thesis-grades.receive', $report), $html);
        $this->assertStringContainsString('+ PDF', $html);
        $this->assertStringNotContainsString('>รายละเอียด</a>', $html);
    }

    #[Test]
    public function list_shows_s0_actions_inline_for_zero_credit_students(): void
    {
        $this->actingAs(new User(['name' => 'Admin สาขา', 'email' => 'dept@kku.ac.th']));

        $report = new ThesisGrade([
            'subject_code' => 'SC899001',
            'subject' => 'THESIS',
            'section' => '1',
            'term' => 2,
            'year' => 2568,
            'teacher' => 'อ. ทดสอบ',
            'status' => ThesisGrade::STATUS_SUBMITTED,
        ]);
        $report->thesis_grade_id = 24;
        $student = new ThesisGradeStudent([
            'student_code' => '677020018-0',
            'student_name' => 'ทดสอบ ระบบ',
            'grade' => 'S',
            'credits_passed' => 0,
        ]);
        $student->student_id = 91;
        $report->setRelation('students', collect([$student]));
        $report->setRelation('files', collect());

        $html = view('dept-admin.thesis-grades.index', [
            'reports' => new LengthAwarePaginator(collect([$report]), 1, 20, 1, [
                'path' => '/dept-admin/thesis-grades',
            ]),
            'departments' => collect(),
            'filters' => ['term' => 2, 'year' => 2568, 'status' => ''],
            'years' => [2568],
        ])->render();

        $this->assertStringContainsString('ขาดบันทึก', $html);
        $this->assertStringContainsString(route('dept-admin.thesis-grades.s0-letter', [$report, $student]), $html);
        $this->assertStringContainsString(route('dept-admin.thesis-grades.s0.docx', [$report, $student]), $html);
    }

    #[Test]
    public function received_row_does_not_show_receive_button(): void
    {
        $this->actingAs(new User(['name' => 'Admin สาขา', 'email' => 'dept@kku.ac.th']));

        $report = new ThesisGrade([
            'subject_code' => 'SC899001',
            'subject' => 'THESIS',
            'section' => '1',
            'status' => ThesisGrade::STATUS_RECEIVED,
        ]);
        $report->thesis_grade_id = 23;
        $report->setRelation('students', collect());
        $report->setRelation('files', collect());

        $html = view('dept-admin.thesis-grades.index', [
            'reports' => new LengthAwarePaginator(collect([$report]), 1, 20, 1, [
                'path' => '/dept-admin/thesis-grades',
            ]),
            'departments' => collect(),
            'filters' => ['term' => 2, 'year' => 2568, 'status' => ''],
            'years' => [2568],
        ])->render();

        $this->assertStringContainsString('ผ่านที่ประชุมสาขาฯ แล้ว', $html);
        $this->assertStringNotContainsString('ยืนยันผ่านที่ประชุมสาขาวิชา', $html);
    }
}
