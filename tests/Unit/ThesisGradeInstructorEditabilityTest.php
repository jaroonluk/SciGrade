<?php

namespace Tests\Unit;

use App\Models\ThesisGrade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThesisGradeInstructorEditabilityTest extends TestCase
{
    #[Test]
    public function instructor_can_edit_and_delete_until_department_or_faculty_changes_status(): void
    {
        foreach ([
            ThesisGrade::STATUS_DRAFT,
            ThesisGrade::STATUS_SUBMITTED,
            ThesisGrade::STATUS_RETURNED,
        ] as $status) {
            $report = new ThesisGrade(['status' => $status]);
            $this->assertTrue($report->isEditable(), $status.' should be editable');
            $this->assertTrue($report->isDeletable(), $status.' should be deletable');
        }
    }

    #[Test]
    public function instructor_cannot_edit_or_delete_after_department_or_faculty_receives(): void
    {
        foreach ([
            ThesisGrade::STATUS_RECEIVED,
            ThesisGrade::STATUS_APPROVED,
        ] as $status) {
            $report = new ThesisGrade(['status' => $status]);
            $this->assertFalse($report->isEditable(), $status.' should not be editable');
            $this->assertFalse($report->isDeletable(), $status.' should not be deletable');
        }
    }
}
