<?php

namespace Tests\Unit;

use App\Models\GradeReport;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DepartmentSubjectFilterApplyToQueryTest extends TestCase
{
    #[Test]
    public function apply_to_query_blocks_all_rows_when_department_has_no_patterns(): void
    {
        $filter = Mockery::mock(DepartmentSubjectFilter::class)->makePartial();
        $filter->shouldReceive('subjectMatchers')->once()->andReturn([]);

        $sql = strtolower(GradeReport::query()->tap(
            fn ($query) => $filter->applyToQuery($query, 999)
        )->toSql());

        $this->assertStringContainsString('1 = 0', $sql);
    }

    #[Test]
    public function apply_to_query_matches_trimmed_subject_code_and_subject_code2(): void
    {
        $filter = Mockery::mock(DepartmentSubjectFilter::class)->makePartial();
        $filter->shouldReceive('subjectMatchers')->once()->andReturn([
            function ($query): void {
                $query->where(function ($inner): void {
                    $inner->orWhereRaw(
                        "UPPER(REPLACE(REPLACE(TRIM(`subject_code`), ' ', ''), UNHEX('C2A0'), '')) LIKE ?",
                        ['SC1%']
                    )->orWhereRaw(
                        "UPPER(REPLACE(REPLACE(TRIM(IFNULL(`subject_code2`, '')), ' ', ''), UNHEX('C2A0'), '')) LIKE ?",
                        ['SC1%']
                    );
                });
            },
        ]);

        $sql = strtolower(GradeReport::query()->tap(
            fn ($query) => $filter->applyToQuery($query, 8)
        )->toSql());

        $this->assertStringContainsString('subject_code', $sql);
        $this->assertStringContainsString('subject_code2', $sql);
        $this->assertStringContainsString('trim', $sql);
    }
}
