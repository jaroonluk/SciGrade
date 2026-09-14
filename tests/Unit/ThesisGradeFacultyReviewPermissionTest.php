<?php

namespace Tests\Unit;

use App\Models\TblPrivilege;
use App\Support\SciGradeRole;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThesisGradeFacultyReviewPermissionTest extends TestCase
{
    #[Test]
    public function graduate_service_staff_and_super_admin_can_review(): void
    {
        $this->assertTrue(SciGradeRole::allowsThesisGradeFacultyReview(
            TblPrivilege::LEVEL_SERVICE_GRADUATE,
            false,
            SciGradeRole::FACULTY_ADMIN,
        ));
        $this->assertTrue(SciGradeRole::allowsThesisGradeFacultyReview(
            TblPrivilege::LEVEL_SUPER,
            true,
            SciGradeRole::SUPER_ADMIN,
        ));
        $this->assertTrue(SciGradeRole::allowsThesisGradeFacultyReview(
            null,
            true,
            SciGradeRole::SUPER_ADMIN,
        ));
    }

    #[Test]
    public function bachelor_and_generic_service_staff_cannot_review(): void
    {
        $this->assertFalse(SciGradeRole::allowsThesisGradeFacultyReview(
            TblPrivilege::LEVEL_SERVICE_BACHELOR,
            false,
            SciGradeRole::FACULTY_ADMIN,
        ));
        $this->assertFalse(SciGradeRole::allowsThesisGradeFacultyReview(
            TblPrivilege::LEVEL_SERVICE,
            false,
            SciGradeRole::FACULTY_ADMIN,
        ));
        $this->assertFalse(SciGradeRole::allowsThesisGradeFacultyReview(
            TblPrivilege::LEVEL_DEPT,
            false,
            SciGradeRole::FACULTY_ADMIN,
        ));
        $this->assertFalse(SciGradeRole::allowsThesisGradeFacultyReview(
            null,
            false,
            SciGradeRole::FACULTY_ADMIN,
        ));
    }

    #[Test]
    public function super_privilege_does_not_show_the_menu_while_acting_as_faculty_admin(): void
    {
        $this->assertFalse(SciGradeRole::allowsThesisGradeFacultyReview(
            TblPrivilege::LEVEL_SUPER,
            true,
            SciGradeRole::FACULTY_ADMIN,
        ));
        $this->assertFalse(SciGradeRole::allowsThesisGradeFacultyReview(
            TblPrivilege::LEVEL_SERVICE_BACHELOR,
            true,
            SciGradeRole::FACULTY_ADMIN,
        ));
    }
}
