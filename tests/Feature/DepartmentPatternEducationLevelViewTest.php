<?php

namespace Tests\Feature;

use App\Models\DepartmentSubjectPattern;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DepartmentPatternEducationLevelViewTest extends TestCase
{
    #[Test]
    public function index_view_shows_both_level_panels_side_by_side(): void
    {
        $this->actingAs(new User(['name' => 'Admin กลาง', 'email' => 'faculty@kku.ac.th']));
        $this->withViewErrors([]);

        $dept = (object) [
            'department_id' => 12,
            'department_name' => 'สาขาวิชาสถิติ',
            'bachelor_patterns' => collect([
                (object) ['id' => 1, 'pattern' => '312%', 'department_id' => 12, 'education_level' => 'bachelor'],
            ]),
            'graduate_patterns' => collect([
                (object) ['id' => 2, 'pattern' => 'SC9%', 'department_id' => 12, 'education_level' => 'graduate'],
            ]),
            'bachelor_details' => [
                ['pattern' => '312%', 'label' => 'ขึ้นต้นด้วย 312', 'kind' => 'prefix'],
            ],
            'graduate_details' => [
                ['pattern' => 'SC9%', 'label' => 'ขึ้นต้นด้วย SC9', 'kind' => 'prefix'],
            ],
            'bachelor_count' => 1,
            'graduate_count' => 1,
            'patterns' => collect(),
            'pattern_details' => [],
        ];

        $html = view('super-admin.department-patterns.index', [
            'departments' => collect([$dept]),
            'q' => '',
            'focusDepartmentId' => null,
            'viewFilter' => 'all',
            'educationLevel' => DepartmentSubjectPattern::EDUCATION_BACHELOR,
        ])->render();

        $this->assertStringContainsString('สาขาวิชาสถิติ', $html);
        $this->assertStringContainsString('รหัสวิชา ปริญญาตรี', $html);
        $this->assertStringContainsString('รหัสวิชา บัณฑิตศึกษา', $html);
        $this->assertStringContainsString('312%', $html);
        $this->assertStringContainsString('SC9%', $html);
        $this->assertStringContainsString('ทั้งหมด (ปริญญาตรี + บัณฑิตศึกษา)', $html);
        $this->assertStringContainsString('ชั้นตัวกรองรหัสวิชา', $html);
    }

    #[Test]
    public function index_view_can_show_only_graduate_panel(): void
    {
        $this->actingAs(new User(['name' => 'Admin กลาง', 'email' => 'faculty@kku.ac.th']));
        $this->withViewErrors([]);

        $dept = (object) [
            'department_id' => 12,
            'department_name' => 'สาขาวิชาสถิติ',
            'bachelor_patterns' => collect(),
            'graduate_patterns' => collect([
                (object) ['id' => 2, 'pattern' => 'SC9%', 'department_id' => 12, 'education_level' => 'graduate'],
            ]),
            'bachelor_details' => [],
            'graduate_details' => [
                ['pattern' => 'SC9%', 'label' => 'ขึ้นต้นด้วย SC9', 'kind' => 'prefix'],
            ],
            'bachelor_count' => 0,
            'graduate_count' => 1,
            'patterns' => collect(),
            'pattern_details' => [],
        ];

        $html = view('super-admin.department-patterns.index', [
            'departments' => collect([$dept]),
            'q' => '',
            'focusDepartmentId' => null,
            'viewFilter' => 'graduate',
            'educationLevel' => DepartmentSubjectPattern::EDUCATION_GRADUATE,
        ])->render();

        $this->assertStringContainsString('รหัสวิชา บัณฑิตศึกษา', $html);
        $this->assertStringContainsString('SC9%', $html);
        $this->assertStringNotContainsString('รหัสวิชา ปริญญาตรี', $html);
    }
}
