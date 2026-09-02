<?php

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegGradeStatusMultiSectionViewTest extends TestCase
{
    #[Test]
    public function dept_admin_view_shows_section_files_and_one_status_control_per_course(): void
    {
        $this->actingAs(new User(['name' => 'Dept Admin', 'email' => 'dept@kku.ac.th']));

        $html = view('dept-admin.reg-grade-status.index', $this->viewData())->render();

        $this->assertStringContainsString('ติกสลับได้ที่แถวแรกของวิชา', $html);
        $this->assertStringContainsString('แบบรายงานผลการสอบไล่-Sec1', $html);
        $this->assertStringContainsString('ใบส่งผลการศึกษา (REG)-Sec2', $html);
        $this->assertSame(1, substr_count($html, 'data-status-control="1"'));
        $this->assertSame(1, substr_count($html, 'data-status-control="0"'));
        $this->assertSame(1, preg_match_all('/class="[^"]*btn-dept-status[^"]*"/', $html));
        $this->assertStringContainsString('data-course-code="SC203001"', $html);
    }

    #[Test]
    public function faculty_admin_view_keeps_section_details_but_one_click_control(): void
    {
        $this->actingAs(new User(['name' => 'Faculty Admin', 'email' => 'faculty@kku.ac.th']));

        $html = view('faculty-admin.settings.reg-grade-status.index', $this->viewData(
            status: 2,
            courseCanApproveFaculty: true,
            courseCanApproveDept: false,
        ))->render();

        $this->assertStringContainsString('ติกสลับได้ที่แถวแรกของวิชา', $html);
        $this->assertStringContainsString('แบบรายงานผลการสอบไล่-Sec1', $html);
        $this->assertSame(1, substr_count($html, 'data-status-control="1"'));
        $this->assertSame(1, preg_match_all('/class="[^"]*btn-faculty-status[^"]*"/', $html));
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(
        int $status = 1,
        bool $courseCanApproveDept = true,
        bool $courseCanApproveFaculty = false,
    ): array {
        $shared = [
            'COURSECODE' => 'SC203001',
            'COURSENAMEENG' => 'Calculus',
            'ACADYEAR' => '2568',
            'SEMESTER' => '2',
            'officers' => 'อาจารย์ ทดสอบ',
            'status' => $status,
            'grade_id' => 101,
            'file_id' => 11,
            'file_name' => 'exam.pdf',
            'approv' => $status === 2 ? 1 : 0,
            'section_count' => 2,
            'has_multi_section' => true,
            'course_grade_id' => 101,
            'course_can_approve_dept' => $courseCanApproveDept,
            'course_can_revert_dept' => false,
            'course_can_approve_faculty' => $courseCanApproveFaculty,
            'course_can_revert_faculty' => false,
            'program_types' => [],
        ];

        $sec1 = (object) array_merge($shared, [
            'SECTION' => '1',
            'is_course_start' => true,
            'attached_files' => collect([
                (object) [
                    'file_id' => 11,
                    'file_name' => '2568_2_SC203001_01.pdf',
                    'file_type' => 'exam_report',
                    'type_label' => 'แบบรายงานผลการสอบไล่-Sec1',
                ],
            ]),
        ]);

        $sec2 = (object) array_merge($shared, [
            'SECTION' => '2',
            'is_course_start' => false,
            'attached_files' => collect([
                (object) [
                    'file_id' => 21,
                    'file_name' => 'REG_2568_2_SC203001_02.pdf',
                    'file_type' => 'registrar',
                    'type_label' => 'ใบส่งผลการศึกษา (REG)-Sec2',
                ],
            ]),
        ]);

        return [
            'departments' => collect(),
            'courses' => collect([$sec1, $sec2]),
            'summary' => [0 => 0, 1 => 2, 2 => 0, 3 => 0],
            'term' => 2,
            'year' => 2568,
            'departmentId' => 9,
            'statusFilter' => 'all',
            'years' => [2568],
        ];
    }
}
