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
        $this->actingAs(new User(['name' => 'งานบริการ ปริญญาตรี', 'email' => 'bachelor@kku.ac.th']));

        $html = $this->homeHtml(canReviewThesisGrades: false);

        $this->assertStringNotContainsString('faculty-admin/thesis-grades', $html);
        $this->assertStringNotContainsString('งานบริการ บัณฑิตศึกษา', $html);
    }

    #[Test]
    public function faculty_home_shows_thesis_menu_for_graduate_service_and_super_admin(): void
    {
        $this->actingAs(new User(['name' => 'งานบริการ บัณฑิตศึกษา', 'email' => 'graduate@kku.ac.th']));

        $html = $this->homeHtml(canReviewThesisGrades: true);

        $this->assertStringContainsString('รับผลการเรียนวิทยานิพนธ์', $html);
        $this->assertStringContainsString('faculty-admin/thesis-grades', $html);
        $this->assertStringContainsString('งานบริการ บัณฑิตศึกษา', $html);
        $this->assertStringContainsString('favicon.svg', $html);
    }

    #[Test]
    public function layout_uses_graduate_grade_favicon(): void
    {
        $this->assertFileExists(public_path('favicon.svg'));
        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertFileExists(public_path('favicon-32x32.png'));
        $this->assertFileExists(public_path('apple-touch-icon.png'));
        $svg = (string) file_get_contents(public_path('favicon.svg'));
        $this->assertStringContainsString('graduate grade submission', $svg);
        $this->assertStringContainsString('#8B4513', $svg);
        $this->assertStringContainsString('#F3D27A', $svg);
        $this->assertStringContainsString("partials.favicon", (string) file_get_contents(resource_path('views/layouts/scigrad.blade.php')));
        $this->assertStringContainsString("partials.favicon", (string) file_get_contents(resource_path('views/layouts/app.blade.php')));
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
