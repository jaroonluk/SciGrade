<?php

namespace Tests\Unit;

use App\Models\ThesisGrade;
use App\Models\ThesisGradeStudent;
use App\Services\ThesisGrade\ThesisGradeDocxExportService;
use App\Support\ThesisGradeS0Letter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ZipArchive;

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

        $this->assertSame('ขอชี้แจงการให้เกรด S = 0 ในรายวิชา SC899001 THESIS กลุ่มที่ 01', $fields['subject_line']);
        $this->assertSame('SC899001', $fields['subject_code']);
        $this->assertSame('THESIS', $fields['subject']);
        $this->assertSame('วิทยานิพนธ์', $fields['course_kind_th']);
        $this->assertSame('วิทยาศาสตรมหาบัณฑิต', $fields['program_th']);
        $this->assertSame('01', $fields['section']);
        $this->assertSame('ภาคปลาย', $fields['term_label']);
        $this->assertSame(2568, $fields['year']);
        $this->assertSame('อ. ทดสอบ', $fields['teacher']);
        $this->assertSame('677020018-0', $fields['student_code']);
        $this->assertSame('นางสาว ทดสอบ ระบบ', $fields['student_name']);
        $this->assertSame('ยังไม่ได้รับอนุมัติเค้าโครง', $fields['proposal_status']);
        $this->assertSame('S', $fields['grade']);
        $this->assertSame('0', $fields['credits_passed']);
        $this->assertStringContainsString('ใคร่ขอชี้แจงกรณี นางสาว ทดสอบ ระบบ', $fields['body']);
        $this->assertStringContainsString('SC899001 THESIS กลุ่มที่ 01 ภาคปลาย ปีการศึกษา 2568', $fields['body']);
        $this->assertStringContainsString('เป็น S=0', $fields['body']);
        $this->assertStringContainsString('ยังไม่ได้รับอนุมัติเค้าโครงภายในกำหนด', $fields['reason']);
    }

    #[Test]
    public function memo_html_matches_the_official_letter_layout(): void
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
            'docxUrl' => '/thesis-grades/22/s0.docx',
        ])->render();

        $this->assertStringContainsString('บันทึกข้อความ', $html);
        $this->assertStringContainsString('ส่วนงาน', $html);
        $this->assertStringContainsString('อว 660301', $html);
        $this->assertStringContainsString('ขอชี้แจงการให้เกรด S = 0 ในรายวิชา SC899001 THESIS', $html);
        $this->assertStringContainsString('กลุ่มที่', $html);
        $this->assertStringContainsString('02', $html);
        $this->assertStringContainsString('ภาคปลาย', $html);
        $this->assertStringContainsString('2568', $html);
        $this->assertStringContainsString('ใคร่ขอชี้แจงกรณี', $html);
        $this->assertStringContainsString('s0-letter-emblem.png', $html);
        $this->assertStringContainsString('ดาวน์โหลด Word', $html);
        $this->assertStringNotContainsString('ข้อมูลรายวิชาที่กำลังรายงานใน SciGrade', $html);
    }

    #[Test]
    public function docx_uses_sarabun_and_official_wording(): void
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

        $response = (new ThesisGradeDocxExportService)->downloadS0Letter($report, $student);
        $path = $response->getFile()->getPathname();
        $this->assertFileExists($path);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $xml = (string) $zip->getFromName('word/document.xml');
        $hasImage = $zip->locateName('word/media/image1.png') !== false
            || $zip->locateName('word/media/image1.jpeg') !== false;
        $zip->close();
        @unlink($path);

        $this->assertStringContainsString('TH SarabunPSK', $xml);
        $this->assertStringContainsString('บันทึกข้อความ', $xml);
        $this->assertStringContainsString('ขอชี้แจงการให้เกรด', $xml);
        $this->assertStringContainsString('ใคร่ขอชี้แจงกรณี', $xml);
        $this->assertStringContainsString('SC899001', $xml);
        $this->assertTrue($hasImage || is_file(public_path(ThesisGradeS0Letter::EMBLEM_RELATIVE)));
    }

    #[Test]
    public function summary_docx_uses_valid_table_width_unit(): void
    {
        $report = new ThesisGrade([
            'subject_code' => 'SC899001',
            'subject' => 'THESIS',
            'section' => '1',
            'term' => 2,
            'year' => 2568,
            'status' => ThesisGrade::STATUS_RECEIVED,
        ]);
        $report->setRelation('students', collect([
            new ThesisGradeStudent(['student_code' => '677020018-0']),
        ]));

        $response = (new ThesisGradeDocxExportService)->downloadSummary(collect([$report]), 2, 2568);
        $path = $response->getFile()->getPathname();
        $this->assertFileExists($path);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $this->assertStringContainsString('ผลการเรียนวิทยานิพนธ์', $xml);
        $this->assertStringContainsString('SC899001', $xml);
        $this->assertStringContainsString('w:type="dxa"', $xml);
        $this->assertStringNotContainsString('w:orient="landscape"', $xml);
        $this->assertStringNotContainsString('หมายเหตุ (กรอกเพิ่ม)', $xml);
        $this->assertStringNotContainsString('คอลัมน์หมายเหตุเว้นว่างไว้ให้ Admin กลางกรอกเพิ่มนอกระบบ', $xml);
        $this->assertStringContainsString('จึงเสนอที่ประชุมเพื่อโปรดพิจารณา', $xml);
        $this->assertStringContainsString('มติที่ประชุม', $xml);
    }
}
