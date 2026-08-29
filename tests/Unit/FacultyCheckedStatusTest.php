<?php

namespace Tests\Unit;

use App\Enums\GradeApprovalStatus;
use App\Models\GradeReport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FacultyCheckedStatusTest extends TestCase
{
    #[Test]
    public function it_labels_faculty_checked_status(): void
    {
        $this->assertSame('ตรวจแล้ว', GradeApprovalStatus::FacultyChecked->shortLabel());
        $this->assertSame('ตรวจแล้ว — รอกรรมการคณะฯ', GradeApprovalStatus::FacultyChecked->label());
        $this->assertSame([1, 3], GradeApprovalStatus::facultyReviewableValues());
    }

    #[Test]
    public function it_allows_faculty_actions_for_checked_and_dept_approved(): void
    {
        $deptApproved = new GradeReport(['approv' => GradeApprovalStatus::DepartmentApproved->value]);
        $checked = new GradeReport(['approv' => GradeApprovalStatus::FacultyChecked->value]);
        $central = new GradeReport(['approv' => GradeApprovalStatus::CentralApproved->value]);

        $this->assertTrue($deptApproved->canMarkFacultyChecked());
        $this->assertTrue($deptApproved->canFacultyApprove());

        $this->assertFalse($checked->canMarkFacultyChecked());
        $this->assertTrue($checked->canFacultyApprove());
        $this->assertSame('ตรวจแล้ว', $checked->workflowStatusLabel());

        $this->assertFalse($central->canMarkFacultyChecked());
        $this->assertFalse($central->canFacultyApprove());
    }
}
