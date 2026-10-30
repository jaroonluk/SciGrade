<?php

namespace Tests\Unit;

use App\Services\DeptAdmin\DepartmentReportQueryService;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use Illuminate\Database\Eloquent\Builder;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DepartmentReportScopeDefaultTest extends TestCase
{
    #[Test]
    public function base_query_defaults_to_subject_patterns_only(): void
    {
        $filter = Mockery::mock(DepartmentSubjectFilter::class);
        $filter->shouldReceive('applyDepartmentsToQuery')
            ->once()
            ->andReturnUsing(fn (Builder $query) => $query);
        $filter->shouldNotReceive('applyDepartmentsExamReportsToQuery');

        $service = new DepartmentReportQueryService($filter);

        $service->baseQuery([
            'department_ids' => [25],
            'department_id' => 25,
            'term' => 1,
            'year' => 2567,
            'education_level' => 'all',
        ]);
    }

    #[Test]
    public function base_query_can_still_require_department_instructor_when_requested(): void
    {
        $filter = Mockery::mock(DepartmentSubjectFilter::class);
        $filter->shouldReceive('applyDepartmentsExamReportsToQuery')
            ->once()
            ->andReturnUsing(fn (Builder $query) => $query);
        $filter->shouldNotReceive('applyDepartmentsToQuery');

        $service = new DepartmentReportQueryService($filter);

        $service->baseQuery([
            'department_ids' => [25],
            'department_id' => 25,
            'term' => 1,
            'year' => 2567,
            'education_level' => 'all',
            'require_department_instructor' => true,
        ]);
    }
}
