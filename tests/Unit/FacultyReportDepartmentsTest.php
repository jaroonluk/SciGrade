<?php

namespace Tests\Unit;

use App\Support\FacultyReportDepartments;
use PHPUnit\Framework\TestCase;

class FacultyReportDepartmentsTest extends TestCase
{
    public function test_excludes_materials_and_nanotechnology_program(): void
    {
        $this->assertTrue(FacultyReportDepartments::isExcludedFromSelect('หลักสูตรวัสดุศาสตร์และนาโนเทคโนโลยี'));
        $this->assertTrue(FacultyReportDepartments::isExcludedFromSelect('วัสดุศาสตร์และนาโนเทคโนโลยี'));
        $this->assertFalse(FacultyReportDepartments::isExcludedFromSelect('สาขาวิชาเคมี'));
        $this->assertFalse(FacultyReportDepartments::isExcludedFromSelect(null));
    }
}
