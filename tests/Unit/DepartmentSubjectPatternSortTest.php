<?php

namespace Tests\Unit;

use App\Services\SuperAdmin\DepartmentSubjectPatternService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class DepartmentSubjectPatternSortTest extends TestCase
{
    #[Test]
    public function departments_follow_preferred_faculty_order_then_others(): void
    {
        $service = app(DepartmentSubjectPatternService::class);
        $method = new ReflectionMethod(DepartmentSubjectPatternService::class, 'sortDepartmentsByPreferredOrder');
        $method->setAccessible(true);

        $rows = collect([
            (object) ['department_id' => 1, 'department_name' => 'สาขาวิชาฟิสิกส์'],
            (object) ['department_id' => 2, 'department_name' => 'หน่วยงานอื่นของคณะ'],
            (object) ['department_id' => 3, 'department_name' => 'สาขาวิชาชีววิทยา'],
            (object) ['department_id' => 4, 'department_name' => 'งานบริการการศึกษา'],
            (object) ['department_id' => 5, 'department_name' => 'สาขาวิชาเคมี'],
            (object) ['department_id' => 6, 'department_name' => 'สาขาวิชาวิทยาศาสตร์บูรณาการ'],
            (object) ['department_id' => 7, 'department_name' => 'สาขาวิชาสถิติ'],
            (object) ['department_id' => 8, 'department_name' => 'สาขาวิชาคณิตศาสตร์'],
            (object) ['department_id' => 9, 'department_name' => 'สาขาวิชาจุลชีววิทยา'],
            (object) ['department_id' => 10, 'department_name' => 'สาขาวิชาชีวเคมี'],
            (object) ['department_id' => 11, 'department_name' => 'สาขาวิชาวิทยาศาสตร์สิ่งแวดล้อม'],
            (object) ['department_id' => 12, 'department_name' => 'อีกหน่วยงานหนึ่ง'],
        ]);

        /** @var Collection<int, object> $sorted */
        $sorted = $method->invoke($service, $rows);
        $names = $sorted->pluck('department_name')->all();

        $this->assertSame([
            'งานบริการการศึกษา',
            'สาขาวิชาวิทยาศาสตร์บูรณาการ',
            'สาขาวิชาชีววิทยา',
            'สาขาวิชาเคมี',
            'สาขาวิชาคณิตศาสตร์',
            'สาขาวิชาฟิสิกส์',
            'สาขาวิชาสถิติ',
            'สาขาวิชาจุลชีววิทยา',
            'สาขาวิชาชีวเคมี',
            'สาขาวิชาวิทยาศาสตร์สิ่งแวดล้อม',
        ], array_slice($names, 0, 10));

        $this->assertContains('หน่วยงานอื่นของคณะ', $names);
        $this->assertContains('อีกหน่วยงานหนึ่ง', $names);
        $this->assertGreaterThan(9, array_search('หน่วยงานอื่นของคณะ', $names, true));
        $this->assertGreaterThan(9, array_search('อีกหน่วยงานหนึ่ง', $names, true));
    }
}
