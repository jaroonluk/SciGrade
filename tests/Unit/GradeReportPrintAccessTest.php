<?php

namespace Tests\Unit;

use App\Models\GradeReport;
use App\Support\GradeReportPrintAccess;
use App\Support\SciGradeRole;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GradeReportPrintAccessTest extends TestCase
{
    #[Test]
    public function owner_may_print_own_report(): void
    {
        $this->assertTrue(GradeReportPrintAccess::allows(
            SciGradeRole::INSTRUCTOR,
            'teacher01',
            $this->report('teacher01', 0),
        ));
    }

    #[Test]
    public function contributor_may_print_shared_draft_report(): void
    {
        $this->assertTrue(GradeReportPrintAccess::allows(
            SciGradeRole::INSTRUCTOR,
            'teacher02',
            $this->report('teacher01', 0),
        ));
    }

    #[Test]
    public function contributor_may_not_print_approved_report(): void
    {
        $this->assertFalse(GradeReportPrintAccess::allows(
            SciGradeRole::INSTRUCTOR,
            'teacher02',
            $this->report('teacher01', 1),
        ));
    }

    #[Test]
    public function instructor_without_staff_username_may_not_print(): void
    {
        $this->assertFalse(GradeReportPrintAccess::allows(
            SciGradeRole::INSTRUCTOR,
            null,
            $this->report('teacher01', 0),
        ));
    }

    #[Test]
    public function dept_admin_may_print_another_instructors_report(): void
    {
        $this->assertTrue(GradeReportPrintAccess::allows(
            SciGradeRole::DEPT_ADMIN,
            'dept01',
            $this->report('teacher01', 1),
        ));
    }

    private function report(string $username, int $approv): GradeReport
    {
        $report = new GradeReport([
            'username' => $username,
            'approv' => $approv,
        ]);
        $report->setRelation('approvalLogs', collect());

        return $report;
    }
}
