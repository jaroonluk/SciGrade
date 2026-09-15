<?php

namespace Tests\Unit;

use App\Models\GradeReport;
use App\Models\GradeStd;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GradeStdOwnershipTest extends TestCase
{
    #[Test]
    public function missing_student_counts_when_total_is_zero(): void
    {
        $this->assertTrue((new GradeStd(['total_std' => 0]))->isMissingStudentCounts());
        $this->assertFalse((new GradeStd(['total_std' => 8]))->isMissingStudentCounts());
    }

    #[Test]
    public function empty_section_username_belongs_to_report_owner(): void
    {
        $report = new GradeReport(['username' => 'teacher01']);
        $std = new GradeStd(['sec' => '1', 'username' => '']);

        $this->assertTrue($std->filledBy('teacher01', $report));
        $this->assertFalse($std->filledBy('teacher02', $report));
    }
}
