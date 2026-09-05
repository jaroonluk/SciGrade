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
        ));
        $this->assertTrue(SciGradeRole::allowsThesisGradeFacultyReview(
            TblPrivilege::LEVEL_SUPER,
            true,
        ));
        $this->assertTrue(SciGradeRole::allowsThesisGradeFacultyReview(null, true));
    }

    #[Test]
    public function bachelor_and_generic_service_staff_cannot_review(): void
    {
        $this->assertFalse(SciGradeRole::allowsThesisGradeFacultyReview(
            TblPrivilege::LEVEL_SERVICE_BACHELOR,
            false,
        ));
        $this->assertFalse(SciGradeRole::allowsThesisGradeFacultyReview(
            TblPrivilege::LEVEL_SERVICE,
            false,
        ));
        $this->assertFalse(SciGradeRole::allowsThesisGradeFacultyReview(
            TblPrivilege::LEVEL_DEPT,
            false,
        ));
        $this->assertFalse(SciGradeRole::allowsThesisGradeFacultyReview(null, false));
    }
}
