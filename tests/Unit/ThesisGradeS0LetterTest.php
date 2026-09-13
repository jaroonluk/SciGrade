<?php

namespace Tests\Unit;

use App\Models\ThesisGrade;
use App\Models\ThesisGradeStudent;
use App\Support\ThesisGradeS0Letter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThesisGradeS0LetterTest extends TestCase
{
    #[Test]
    public function it_fills_the_memo_with_the_course_being_reported(): void
    {
        $report = new ThesisGrade([
            'subject_code' => 'SC899001',
            'subject' => 'THESIS',
            'section' => '1',
            'term' => 2,
            'year' => 2568,
            'teacher' => 'อ. ทดสอบ',
            'course_kind' => 'thesis',
        ]);

        $student = new ThesisGradeStudent([
            'student_code' => '677020018-0',
            'name_prefix' => 'นางสาว',
            'first_name' => 'ทดสอบ',
            'last_name' => 'ระบบ',
            'degree' => 'master',
            'thesis_terms_count' => 3,
            'proposal_approved' => false,
            'grade' => 'S',
            'credits_passed' => 0,
        ]);

        $fields = ThesisGradeS0Letter::fields($report, $student);

        $this->assertSame('ชี้แจงการให้เกรด S = 0 ในรายวิชา SC899001 THESIS', $fields['subject_line']);
        $this->assertSame('SC899001', $fields['subject_code']);
        $this->assertSame('THESIS', $fields['subject']);
        $this->assertSame('วิทยานิพนธ์', $fields['course_kind_th']);
        $this->assertSame('01', $fields['section']);
        $this->assertSame('ภาคปลาย', $fields['term_label']);
        $this->assertSame(2568, $fields['year']);
        $this->assertSame('อ. ทดสอบ', $fields['teacher']);
        $this->assertSame('677020018-0', $fields['student_code']);
        $this->assertSame('นางสาว ทดสอบ ระบบ', $fields['student_name']);
        $this->assertSame('ยังไม่ได้รับอนุมัติเค้าโครง', $fields['proposal_status']);
        $this->assertSame('S', $fields['grade']);
        $this->assertSame('0', $fields['credits_passed']);
    }

    #[Test]
    public function memo_html_shows_course_fields_without_section_guessing(): void
    {
        $report = new ThesisGrade([
            'subject_code' => 'SC899001',
            'subject' => 'THESIS',
            'section' => '2',
            'term' => 2,
            'year' => 2568,
            'teacher' => 'อ. ทดสอบ',
        ]);

        $html = view('thesis-grades.s0-letter', [
            'fields' => ThesisGradeS0Letter::fields($report),
            'backUrl' => '/thesis-grades/22?step=2',
            'officialFormUrl' => '',
            'docxUrl' => '',
        ])->render();

        $this->assertStringContainsString('ชี้แจงการให้เกรด S = 0 ในรายวิชา SC899001 THESIS', $html);
        $this->assertStringContainsString('SC899001 THESIS', $html);
        $this->assertStringContainsString('กลุ่มที่', $html);
        $this->assertStringContainsString('02', $html);
        $this->assertStringContainsString('ภาคปลาย', $html);
        $this->assertStringContainsString('2568', $html);
        $this->assertStringContainsString('อ. ทดสอบ', $html);
        $this->assertStringContainsString('ข้อมูลรายวิชาที่กำลังรายงานใน SciGrade', $html);
    }
}
