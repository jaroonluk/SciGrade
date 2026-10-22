<?php

namespace Tests\Unit;

use App\Services\Instructor\GradeReportIDocxExportService;
use App\Support\GradeReportILetter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ZipArchive;

class GradeReportILetterTest extends TestCase
{
    #[Test]
    public function it_builds_fields_from_registrar_students(): void
    {
        $fields = GradeReportILetter::fields([
            'subject_code' => 'SC101011',
            'subject' => 'BIOLOGY',
            'term' => 2,
            'year' => 2568,
            'teacher' => 'อ.ทดสอบ',
            'sections' => [1, 2],
            'reason' => 'ป่วยและมีใบรับรองแพทย์',
            'students' => [
                ['name' => 'นายทดสอบ ระบบ', 'student_code' => '663030001-2', 'section' => 1],
                ['name' => 'นางสาวตัวอย่าง งาน', 'student_code' => '663030002-0', 'section' => 2],
            ],
        ]);

        $this->assertSame('SC101011', $fields['subject_code']);
        $this->assertSame(2, $fields['student_count']);
        $this->assertSame('1, 2', $fields['section_label']);
        $this->assertStringContainsString('เกรด I', $fields['body']);
        $this->assertStringContainsString('SC101011', $fields['subject_line']);
        $this->assertStringContainsString('BIOLOGY', $fields['subject_line']);
        $this->assertStringContainsString('ภาคปลาย', $fields['subject_line']);
        $this->assertStringNotContainsString('(รหัสวิชา)', $fields['subject_line']);
        $this->assertStringNotContainsString('(ชื่อวิชา)', $fields['subject_line']);
        $this->assertStringNotContainsString('(ต้น/ปลาย)', $fields['subject_line']);
        $this->assertStringNotContainsString('(รหัสวิชา)', $fields['body']);
        $this->assertStringNotContainsString('(ชื่อวิชา)', $fields['body']);
        $this->assertStringNotContainsString('(ต้น/ปลาย)', $fields['body']);
        $this->assertSame('ป่วยและมีใบรับรองแพทย์', $fields['students'][0]['reason']);
    }

    #[Test]
    public function chair_title_uses_department_name_without_duplicating_prefix(): void
    {
        $fields = GradeReportILetter::fields([
            'subject_code' => 'SC414761',
            'subject' => 'MATH',
            'term' => 1,
            'year' => 2567,
            'department' => 'สาขาวิชาคณิตศาสตร์',
            'students' => [
                ['name' => 'นายเอ', 'student_code' => '663030001-2'],
            ],
        ]);

        $this->assertSame('หัวหน้าสาขาวิชาคณิตศาสตร์', $fields['chair_title']);
        $this->assertSame('คณบดีคณะวิทยาศาสตร์ ผ่านหัวหน้าสาขาวิชาคณิตศาสตร์', $fields['to_line']);
        $this->assertSame('สาขาวิชาคณิตศาสตร์', $fields['department']);
        $this->assertSame('หัวหน้าสาขาวิชาชีววิทยา', GradeReportILetter::chairTitleForDepartment('ชีววิทยา'));
        $this->assertSame('หัวหน้าสาขาวิชา........', GradeReportILetter::chairTitleForDepartment(''));
    }

    #[Test]
    public function it_uses_per_student_reason_over_global_reason(): void
    {
        $fields = GradeReportILetter::fields([
            'subject_code' => 'SC101011',
            'subject' => 'BIOLOGY',
            'term' => 1,
            'year' => 2567,
            'reason' => 'เหตุผลรวม',
            'students' => [
                ['name' => 'นายเอ', 'student_code' => '663030001-2', 'reason' => 'ป่วย'],
                ['name' => 'นายบี', 'student_code' => '663030002-0'],
            ],
        ]);

        $this->assertSame('ป่วย', $fields['students'][0]['reason']);
        $this->assertSame('เหตุผลรวม', $fields['students'][1]['reason']);
        $this->assertStringContainsString('ภาคต้น', $fields['body']);
        $this->assertStringNotContainsString('(ต้น/ปลาย)', $fields['body']);
    }

    #[Test]
    public function docx_contains_student_rows_and_official_wording(): void
    {
        $response = (new GradeReportIDocxExportService)->download([
            'subject_code' => 'SC101011',
            'subject' => 'BIOLOGY',
            'term' => 2,
            'year' => 2568,
            'teacher' => 'อ.ทดสอบ',
            'department' => 'สาขาวิชาชีววิทยา',
            'sections' => [1],
            'reason' => 'เหตุสุดวิสัย',
            'students' => [
                ['name' => 'นายทดสอบ ระบบ', 'student_code' => '663030001-2'],
            ],
        ]);

        $path = $response->getFile()->getPathname();
        $this->assertFileExists($path);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $this->assertStringContainsString('TH SarabunPSK', $xml);
        $this->assertStringContainsString('บันทึกข้อความ', $xml);
        $this->assertStringContainsString('ชี้แจงสาเหตุการให้เกรด I', $xml);
        $this->assertStringContainsString('SC101011', $xml);
        $this->assertStringContainsString('นายทดสอบ ระบบ', $xml);
        $this->assertStringContainsString('663030001-2', $xml);
        $this->assertStringContainsString('เหตุสุดวิสัย', $xml);
        $this->assertStringContainsString('หัวหน้าสาขาวิชาชีววิทยา', $xml);
        $this->assertStringContainsString('อาจารย์ประจำวิชา', $xml);
        $this->assertStringNotContainsString('อาจารย์ที่ปรึกษาโครงงาน', $xml);
        $this->assertStringNotContainsString('หัวหน้าสาขาวิชาสาขาวิชา', $xml);
        $this->assertStringNotContainsString('(รหัสวิชา)', $xml);
        $this->assertStringNotContainsString('(ชื่อวิชา)', $xml);
        $this->assertStringNotContainsString('(ต้น/ปลาย)', $xml);
    }

    #[Test]
    public function docx_rejects_empty_students(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new GradeReportIDocxExportService)->download([
            'subject_code' => 'SC101011',
            'term' => 2,
            'year' => 2568,
            'students' => [],
        ]);
    }
}
