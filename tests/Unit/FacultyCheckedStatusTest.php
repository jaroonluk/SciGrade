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

    #[Test]
    public function it_allows_dept_revert_only_while_department_approved(): void
    {
        $saved = new GradeReport(['approv' => GradeApprovalStatus::Saved->value]);
        $deptApproved = new GradeReport(['approv' => GradeApprovalStatus::DepartmentApproved->value]);
        $checked = new GradeReport(['approv' => GradeApprovalStatus::FacultyChecked->value]);
        $central = new GradeReport(['approv' => GradeApprovalStatus::CentralApproved->value]);

        $this->assertFalse($saved->canDeptRevertToSaved());
        $this->assertTrue($deptApproved->canDeptRevertToSaved());
        $this->assertFalse($checked->canDeptRevertToSaved());
        $this->assertFalse($central->canDeptRevertToSaved());
    }

    #[Test]
    public function it_allows_dept_registrar_attach_before_faculty_takes_over(): void
    {
        $saved = new GradeReport(['approv' => GradeApprovalStatus::Saved->value]);
        $deptApproved = new GradeReport(['approv' => GradeApprovalStatus::DepartmentApproved->value]);
        $checked = new GradeReport(['approv' => GradeApprovalStatus::FacultyChecked->value]);
        $rejected = new GradeReport(['approv' => GradeApprovalStatus::DepartmentRejected->value]);

        $this->assertTrue($saved->canDeptAttachRegistrar());
        $this->assertTrue($deptApproved->canDeptAttachRegistrar());
        $this->assertFalse($checked->canDeptAttachRegistrar());
        $this->assertFalse($rejected->canDeptAttachRegistrar());
        $this->assertTrue($saved->canDeptDeleteRegistrar());
        $this->assertTrue($deptApproved->canDeptDeleteRegistrar());
        $this->assertFalse($checked->canDeptDeleteRegistrar());
    }
}
