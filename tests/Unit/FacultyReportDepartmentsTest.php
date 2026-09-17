<?php

namespace Tests\Unit;

use App\Support\FacultyReportDepartments;
use PHPUnit\Framework\TestCase;

class FacultyReportDepartmentsTest extends TestCase
{
    public function test_excludes_requested_faculty_report_departments(): void
    {
        $this->assertTrue(FacultyReportDepartments::isExcludedFromSelect('หลักสูตรวัสดุศาสตร์และนาโนเทคโนโลยี'));
        $this->assertTrue(FacultyReportDepartments::isExcludedFromSelect('หลักสูตรวิสดุศาสตร์และนาโนเทคโนโลยี'));
        $this->assertTrue(FacultyReportDepartments::isExcludedFromSelect('กองบริหารงานคณะ คณะวิทยาศาสตร์'));
        $this->assertTrue(FacultyReportDepartments::isExcludedFromSelect('สาขาวิชาวิทยาการข้อมูลและปัญญาประดิษฐ์'));
        $this->assertTrue(FacultyReportDepartments::isExcludedFromSelect('หลักสูตรนิติวิทยาศาสตร์'));
        $this->assertTrue(FacultyReportDepartments::isExcludedFromSelect('หลักสูตรวิทยาศาสตร์ชีวภาพ'));

        $this->assertFalse(FacultyReportDepartments::isExcludedFromSelect('สาขาวิชาเคมี'));
        $this->assertFalse(FacultyReportDepartments::isExcludedFromSelect('งานบริการการศึกษา'));
        $this->assertFalse(FacultyReportDepartments::isExcludedFromSelect(null));
    }
}
