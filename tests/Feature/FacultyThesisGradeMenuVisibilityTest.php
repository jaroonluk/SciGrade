<?php

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacultyThesisGradeMenuVisibilityTest extends TestCase
{
    #[Test]
    public function faculty_home_hides_thesis_menu_for_bachelor_admin(): void
    {
        $this->actingAs(new User(['name' => 'งานบริการ ป.ตรี', 'email' => 'bachelor@kku.ac.th']));

        $html = $this->homeHtml(canReviewThesisGrades: false);

        $this->assertStringNotContainsString('faculty-admin/thesis-grades', $html);
        $this->assertStringNotContainsString('งานบริการ ป.บัณฑิต', $html);
    }

    #[Test]
    public function faculty_home_shows_thesis_menu_for_graduate_service_and_super_admin(): void
    {
        $this->actingAs(new User(['name' => 'งานบริการ ป.บัณฑิต', 'email' => 'graduate@kku.ac.th']));

        $html = $this->homeHtml(canReviewThesisGrades: true);

        $this->assertStringContainsString('รับผลการเรียนวิทยานิพนธ์', $html);
        $this->assertStringContainsString('faculty-admin/thesis-grades', $html);
        $this->assertStringContainsString('งานบริการ ป.บัณฑิต', $html);
    }

    private function homeHtml(bool $canReviewThesisGrades): string
    {
        view()->share('errors', new \Illuminate\Support\ViewErrorBag);

        return view('home', [
            'role' => 'faculty_admin',
            'selectableRoles' => ['instructor', 'dept_admin', 'faculty_admin'],
            'canImpersonate' => false,
            'isImpersonating' => false,
            'staffDisplayName' => 'ผู้ทดสอบ',
            'term' => 2,
            'year' => 2568,
            'years' => [2568],
            'departments' => collect(),
            'deptDepartmentId' => null,
            'deptSubmissions' => [],
            'openDeptSubmissions' => collect(),
            'canReviewThesisGrades' => $canReviewThesisGrades,
        ])->render();
    }
}
