<?php

namespace Tests\Unit;

use App\Services\DeptAdmin\DepartmentSubjectFilter;
use PHPUnit\Framework\TestCase;

class DepartmentExamReportFilterTest extends TestCase
{
    public function test_education_services_name_detection(): void
    {
        $this->assertTrue(DepartmentSubjectFilter::isEducationServicesName('งานบริการการศึกษา'));
        $this->assertTrue(DepartmentSubjectFilter::isEducationServicesName('งานบริการการศึกษา คณะวิทยาศาสตร์'));
        $this->assertFalse(DepartmentSubjectFilter::isEducationServicesName('สาขาวิชาชีววิทยา'));
        $this->assertFalse(DepartmentSubjectFilter::isEducationServicesName(null));
        $this->assertFalse(DepartmentSubjectFilter::isEducationServicesName(''));
    }
}
