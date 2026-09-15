<?php

namespace Tests\Unit;

use App\Support\GradeReportRemarks;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GradeReportRemarksTest extends TestCase
{
    #[Test]
    public function parse_legacy_single_choice_i_reason(): void
    {
        $parsed = GradeReportRemarks::parse('ได้ I เนื่องจาก :ป่วย', 2);

        $this->assertSame(GradeReportRemarks::FLAG_I, $parsed['flags']);
        $this->assertSame(['ป่วย'], $parsed['i_entries']);
        $this->assertSame([], $parsed['other_entries']);
    }

    #[Test]
    public function parse_and_build_multi_flags(): void
    {
        $reason = GradeReportRemarks::build(
            'SC001|วิชา A,SC002|วิชา B',
            ['ขาดสอบกลางภาค'],
            ['ส่งงานช้า'],
        );

        $this->assertNotNull($reason);
        $parsed = GradeReportRemarks::parse($reason, 7);

        $this->assertTrue(($parsed['flags'] & GradeReportRemarks::FLAG_JOINT) === GradeReportRemarks::FLAG_JOINT);
        $this->assertTrue(($parsed['flags'] & GradeReportRemarks::FLAG_I) === GradeReportRemarks::FLAG_I);
        $this->assertTrue(($parsed['flags'] & GradeReportRemarks::FLAG_OTHER) === GradeReportRemarks::FLAG_OTHER);
        $this->assertSame(['ขาดสอบกลางภาค'], $parsed['i_entries']);
        $this->assertSame(['ส่งงานช้า'], $parsed['other_entries']);
        $this->assertStringContainsString('SC001', (string) $parsed['joint_line']);
    }

    #[Test]
    public function merge_appends_without_overwriting(): void
    {
        $existing = GradeReportRemarks::build(null, ['เหตุผลเดิม'], ['อื่นๆเดิม']);
        $incoming = GradeReportRemarks::build(null, ['เหตุผลใหม่'], ['อื่นๆใหม่']);

        $merged = GradeReportRemarks::merge($existing, 6, $incoming, 6);

        $this->assertSame(14, $merged['reasonid']); // NEW_FORMAT|I|OTHER = 8|2|4
        $parsed = GradeReportRemarks::parse($merged['reason'], $merged['reasonid']);
        $this->assertSame(['เหตุผลเดิม', 'เหตุผลใหม่'], $parsed['i_entries']);
        $this->assertSame(['อื่นๆเดิม', 'อื่นๆใหม่'], $parsed['other_entries']);
    }

    #[Test]
    public function merge_keeps_prior_when_incoming_has_only_joint(): void
    {
        $existing = GradeReportRemarks::build(null, ['I เดิม'], []);
        $incoming = GradeReportRemarks::build('SC100', [], []);

        $merged = GradeReportRemarks::merge($existing, 2, $incoming, 1);
        $parsed = GradeReportRemarks::parse($merged['reason'], $merged['reasonid']);

        $this->assertTrue(GradeReportRemarks::hasJoint($merged['reasonid'], $merged['reason']));
        $this->assertSame(['I เดิม'], $parsed['i_entries']);
        $this->assertStringContainsString('SC100', (string) $parsed['joint_line']);
        $this->assertSame(11, $merged['reasonid']); // NEW_FORMAT|JOINT|I
    }

    #[Test]
    public function has_joint_supports_bitmask(): void
    {
        $this->assertTrue(GradeReportRemarks::hasJoint(1));
        $this->assertTrue(GradeReportRemarks::hasJoint(13, "ตัดเกรดร่วมกับ :SC1\nอื่นๆ :x")); // 8|1|4
        $this->assertFalse(GradeReportRemarks::hasJoint(2));
        $this->assertFalse(GradeReportRemarks::hasJoint(3, 'ข้อความอื่นๆ แบบเดิม'));
        $this->assertTrue(GradeReportRemarks::hasJoint(11, "ตัดเกรดร่วมกับ :SC1\nได้ I เนื่องจาก :ป่วย"));
        $this->assertFalse(GradeReportRemarks::hasJoint(null));
    }
}
