<?php

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DepartmentPatternEducationLevelViewTest extends TestCase
{
    #[Test]
    public function index_view_shows_unified_department_patterns_without_education_split(): void
    {
        $this->actingAs(new User(['name' => 'Admin กลาง', 'email' => 'faculty@kku.ac.th']));
        $this->withViewErrors([]);

        $dept = (object) [
            'department_id' => 12,
            'department_name' => 'สาขาวิชาสถิติ',
            'patterns' => collect([
                (object) ['id' => 1, 'pattern' => '312%', 'department_id' => 12],
                (object) ['id' => 2, 'pattern' => 'SC9%', 'department_id' => 12],
            ]),
            'pattern_details' => [
                ['pattern' => '312%', 'label' => 'ขึ้นต้นด้วย 312', 'kind' => 'prefix'],
                ['pattern' => 'SC9%', 'label' => 'ขึ้นต้นด้วย SC9', 'kind' => 'prefix'],
            ],
            'pattern_count' => 2,
        ];

        $html = view('super-admin.department-patterns.index', [
            'departments' => collect([$dept]),
            'q' => '',
            'focusDepartmentId' => null,
        ])->render();

        $this->assertStringContainsString('สาขาวิชาสถิติ', $html);
        $this->assertStringContainsString('312%', $html);
        $this->assertStringContainsString('SC9%', $html);
        $this->assertStringContainsString('มีรหัสเงื่อนไข', $html);
        $this->assertStringContainsString('ไม่แยกตามระดับการศึกษา', $html);
        $this->assertStringNotContainsString('ชั้นตัวกรองรหัสวิชา', $html);
        $this->assertStringNotContainsString('รหัสวิชาปริญญาตรี', $html);
        $this->assertStringNotContainsString('รหัสวิชาบัณฑิตศึกษา', $html);
        $this->assertStringNotContainsString('แสดงชั้นกรอง', $html);
        $this->assertStringNotContainsString('ทั้งหมด (ปริญญาตรี + บัณฑิตศึกษา)', $html);
    }
}
