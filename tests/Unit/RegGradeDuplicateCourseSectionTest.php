<?php

namespace Tests\Unit;

use App\Services\DeptAdmin\DepartmentSubjectFilter;
use App\Services\FacultyAdmin\RegGradeDepartmentService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegGradeDuplicateCourseSectionTest extends TestCase
{
    #[Test]
    public function it_marks_same_course_and_section_as_separate_duplicate_entries(): void
    {
        $service = new RegGradeDepartmentService($this->createMock(DepartmentSubjectFilter::class));

        $rows = $service->markDuplicateCourseSectionRows(collect([
            (object) ['COURSECODE' => 'SC203001', 'SECTION' => '1'],
            (object) ['COURSECODE' => 'SC203001', 'SECTION' => '01'],
            (object) ['COURSECODE' => 'SC203001', 'SECTION' => '2'],
            (object) ['COURSECODE' => 'SC204001', 'SECTION' => '1'],
        ]));

        $this->assertTrue($rows[0]->is_duplicate_entry);
        $this->assertTrue($rows[1]->is_duplicate_entry);
        $this->assertSame(2, $rows[0]->duplicate_count);
        $this->assertSame(2, $rows[1]->duplicate_count);
        $this->assertFalse($rows[2]->is_duplicate_entry);
        $this->assertFalse($rows[3]->is_duplicate_entry);
        $this->assertSame(1, $rows[2]->duplicate_count);
    }
}
