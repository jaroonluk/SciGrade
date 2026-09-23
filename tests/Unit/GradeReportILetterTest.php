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
        $this->assertSame('ป่วยและมีใบรับรองแพทย์', $fields['students'][0]['reason']);
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
