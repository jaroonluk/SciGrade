<?php

namespace Tests\Feature;

use App\Models\ThesisGrade;
use App\Models\ThesisGradeFile;
use App\Models\ThesisGradeStudent;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacultyAdminThesisGradeIndexTest extends TestCase
{
    #[Test]
    public function list_shows_instructor_and_department_files_plus_faculty_receive(): void
    {
        $this->actingAs(new User(['name' => 'Admin กลาง', 'email' => 'faculty@kku.ac.th']));

        $report = new ThesisGrade([
            'subject_code' => 'SC899001',
            'subject' => 'THESIS',
            'section' => '1',
            'term' => 2,
            'year' => 2568,
            'teacher' => 'อ. ทดสอบ',
            'status' => ThesisGrade::STATUS_RECEIVED,
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
            'original_name' => 'chair-signed.pdf',
        ]);
        $chair->file_id = 21;
        $report->setRelation('files', collect([$ts, $chair]));

        $html = view('faculty-admin.thesis-grades.index', [
            'reports' => new LengthAwarePaginator(collect([$report]), 1, 20, 1, [
                'path' => '/faculty-admin/thesis-grades',
            ]),
            'departments' => collect(),
            'filters' => ['term' => 2, 'year' => 2568, 'status' => ''],
            'years' => [2568],
        ])->render();

        $this->assertStringContainsString('ไฟล์อาจารย์', $html);
        $this->assertStringContainsString('ไฟล์ Admin สาขา', $html);
        $this->assertStringContainsString('TS-SC899001-01-2-2568.pdf', $html);
        $this->assertStringContainsString('chair-signed.pdf', $html);
        $this->assertStringContainsString('ผ่านที่ประชุมกรรมการคณะฯ', $html);
        $this->assertStringContainsString('ดาวน์โหลดเอกสารสมบูรณ์', $html);
        $this->assertStringContainsString('ชุดสมบูรณ์มาจากไฟล์ Admin สาขา', $html);
        $this->assertStringContainsString(route('faculty-admin.thesis-grades.receive', $report), $html);
        $this->assertStringContainsString('สรุปผลการเรียน', $html);
        $this->assertStringNotContainsString('สรุปผลตาราง 3.1', $html);
    }

    #[Test]
    public function submitted_row_waits_for_department_and_uses_instructor_file(): void
    {
        $this->actingAs(new User(['name' => 'Admin กลาง', 'email' => 'faculty@kku.ac.th']));

        $report = new ThesisGrade([
            'subject_code' => 'SC899001',
            'subject' => 'THESIS',
            'section' => '1',
            'status' => ThesisGrade::STATUS_SUBMITTED,
        ]);
        $report->thesis_grade_id = 23;
        $report->setRelation('students', collect());
        $ts = new ThesisGradeFile([
            'thesis_grade_id' => 23,
            'file_type' => ThesisGradeFile::TYPE_TS_REPORT,
            'original_name' => 'instructor-only.pdf',
        ]);
        $ts->file_id = 31;
        $report->setRelation('files', collect([$ts]));

        $html = view('faculty-admin.thesis-grades.index', [
            'reports' => new LengthAwarePaginator(collect([$report]), 1, 20, 1, [
                'path' => '/faculty-admin/thesis-grades',
            ]),
            'departments' => collect(),
            'filters' => ['term' => 2, 'year' => 2568, 'status' => ''],
            'years' => [2568],
        ])->render();

        $this->assertStringContainsString('รอสาขาผ่านที่ประชุมก่อน', $html);
        $this->assertStringContainsString('ชุดสมบูรณ์ใช้ไฟล์อาจารย์', $html);
        $this->assertStringNotContainsString('ยืนยันผ่านที่ประชุมกรรมการคณะฯ', $html);
    }
}
