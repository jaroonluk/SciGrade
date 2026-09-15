<?php

namespace Tests\Feature;

use App\Models\DepartmentSubjectPattern;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DepartmentPatternEducationLevelViewTest extends TestCase
{
    #[Test]
    public function index_view_has_bachelor_and_graduate_options(): void
    {
        $this->actingAs(new User(['name' => 'Admin กลาง', 'email' => 'faculty@kku.ac.th']));
        $this->withViewErrors([]);

        $dept = (object) [
            'department_id' => 12,
            'department_name' => 'สาขาวิชาสถิติ',
            'patterns' => collect(),
            'bachelor_count' => 2,
            'graduate_count' => 1,
            'pattern_details' => [],
        ];

        $html = view('super-admin.department-patterns.index', [
            'departments' => collect([$dept]),
            'q' => '',
            'focusDepartmentId' => null,
            'educationLevel' => DepartmentSubjectPattern::EDUCATION_GRADUATE,
        ])->render();

        $this->assertStringContainsString('ปริญญาตรี', $html);
        $this->assertStringContainsString('บัณฑิตศึกษา', $html);
        $this->assertStringContainsString('name="education_level"', $html);
        $this->assertStringContainsString('value="graduate"', $html);
        $this->assertStringContainsString('กำลังแก้ <strong>บัณฑิตศึกษา</strong>', $html);
    }
}
