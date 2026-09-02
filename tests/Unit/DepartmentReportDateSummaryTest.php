<?php

namespace Tests\Unit;

use App\Services\DeptAdmin\DepartmentReportQueryService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class DepartmentReportDateSummaryTest extends TestCase
{
    public function test_term_label_and_thai_date_helpers(): void
    {
        $service = $this->getMockBuilder(DepartmentReportQueryService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $termLabel = new ReflectionMethod(DepartmentReportQueryService::class, 'termLabel');
        $termLabel->setAccessible(true);
        $this->assertSame('ภาคปลาย', $termLabel->invoke($service, 2));
        $this->assertSame('ทุกภาค', $termLabel->invoke($service, null));

        $formatThai = new ReflectionMethod(DepartmentReportQueryService::class, 'formatThaiDate');
        $formatThai->setAccessible(true);
        $this->assertSame('15/03/2026', $formatThai->invoke($service, '2026-03-15'));

        $normalize = new ReflectionMethod(DepartmentReportQueryService::class, 'normalizeDateString');
        $normalize->setAccessible(true);
        $this->assertSame('2026-03-15', $normalize->invoke($service, '2026-03-15 12:30:00'));
        $this->assertNull($normalize->invoke($service, null));
    }
}
