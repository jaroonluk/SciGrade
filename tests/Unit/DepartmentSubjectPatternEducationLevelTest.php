<?php

namespace Tests\Unit;

use App\Models\DepartmentSubjectPattern;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DepartmentSubjectPatternEducationLevelTest extends TestCase
{
    #[Test]
    public function it_normalizes_unknown_values_to_bachelor(): void
    {
        $this->assertSame('bachelor', DepartmentSubjectPattern::normalizeEducationLevel(null));
        $this->assertSame('bachelor', DepartmentSubjectPattern::normalizeEducationLevel('bachelor'));
        $this->assertSame('graduate', DepartmentSubjectPattern::normalizeEducationLevel('graduate'));
    }

    #[Test]
    public function it_labels_education_levels(): void
    {
        $this->assertSame('ปริญญาตรี', DepartmentSubjectPattern::label('bachelor'));
        $this->assertSame('บัณฑิตศึกษา', DepartmentSubjectPattern::label('graduate'));
    }

    #[Test]
    public function it_maps_report_filters_to_pattern_levels(): void
    {
        $this->assertSame('bachelor', DepartmentSubjectPattern::fromReportFilter('bachelor'));
        $this->assertSame('graduate', DepartmentSubjectPattern::fromReportFilter('graduate'));
        $this->assertSame('graduate', DepartmentSubjectPattern::fromReportFilter('master'));
        $this->assertSame('graduate', DepartmentSubjectPattern::fromReportFilter('doctoral'));
        $this->assertNull(DepartmentSubjectPattern::fromReportFilter('all'));
        $this->assertNull(DepartmentSubjectPattern::fromReportFilter(null));
    }
}
