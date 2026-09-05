<?php

namespace Tests\Unit;

use App\Services\ThesisGrade\ThesisGradeStudentParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThesisGradeStudentParserTest extends TestCase
{
    #[Test]
    public function it_parses_csv_and_skips_header(): void
    {
        $text = "รหัส,ชื่อ,ระดับ,ภาค,เค้าโครง,เกรด,หน่วยกิต,ครบ,วันที่สอบ\n".
            "653020001-1,สมชาย,โท,3,0,S,0,1,1/3/2569\n".
            "653020015-8,สมหญิง,เอก,4,อนุมัติ,S,3,0,\n";

        $rows = (new ThesisGradeStudentParser)->parsePaste($text);

        $this->assertCount(2, $rows);
        $this->assertSame('653020001-1', $rows[0]['student_code']);
        $this->assertSame('master', $rows[0]['degree']);
        $this->assertFalse($rows[0]['proposal_approved']);
        $this->assertTrue($rows[0]['completed']);
        $this->assertSame('2026-03-01', $rows[0]['defense_date']);
        $this->assertSame('doctoral', $rows[1]['degree']);
        $this->assertTrue($rows[1]['proposal_approved']);
    }
}
