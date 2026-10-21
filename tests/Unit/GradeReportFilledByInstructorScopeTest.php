<?php

namespace Tests\Unit;

use App\Models\GradeReport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GradeReportFilledByInstructorScopeTest extends TestCase
{
    #[Test]
    public function it_uses_non_correlated_subqueries_instead_of_exists(): void
    {
        $sql = strtolower(GradeReport::query()->filledByInstructor('teacher01')->toSql());

        $this->assertStringNotContainsString('exists', $sql);
        $this->assertStringContainsString(' in (', $sql);
        $this->assertStringContainsString('grade_report_file', $sql);
    }
}
