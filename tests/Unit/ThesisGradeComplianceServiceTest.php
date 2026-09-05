<?php

namespace Tests\Unit;

use App\Services\ThesisGrade\ThesisGradeComplianceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThesisGradeComplianceServiceTest extends TestCase
{
    #[Test]
    public function it_flags_master_overdue_after_two_terms_without_proposal(): void
    {
        $this->assertTrue(ThesisGradeComplianceService::isProposalOverdue('master', 2, false));
        $this->assertFalse(ThesisGradeComplianceService::isProposalOverdue('master', 1, false));
        $this->assertFalse(ThesisGradeComplianceService::isProposalOverdue('master', 5, true));
    }

    #[Test]
    public function it_flags_doctoral_overdue_after_four_terms_without_proposal(): void
    {
        $this->assertTrue(ThesisGradeComplianceService::isProposalOverdue('doctoral', 4, false));
        $this->assertFalse(ThesisGradeComplianceService::isProposalOverdue('doctoral', 3, false));
    }

    #[Test]
    public function it_treats_s_with_zero_or_empty_credits_as_s0(): void
    {
        $this->assertTrue(ThesisGradeComplianceService::isS0('S', 0));
        $this->assertTrue(ThesisGradeComplianceService::isS0('s', null));
        $this->assertFalse(ThesisGradeComplianceService::isS0('S', 3));
        $this->assertFalse(ThesisGradeComplianceService::isS0('U', 0));
    }

    #[Test]
    public function submit_requires_ts_file_checks_s0_letter_and_defense_date(): void
    {
        $errors = (new ThesisGradeComplianceService)->errorsForSubmit(false, false, false, [
            [
                'student_code' => '653020001-1',
                'student_name' => 'สมชาย',
                'degree' => 'master',
                'thesis_terms_count' => 3,
                'proposal_approved' => false,
                'grade' => 'S',
                'progress_credits' => 0,
                'completed' => true,
                'defense_date' => null,
                'has_s0_letter' => false,
            ],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertTrue(collect($errors)->contains(fn ($e) => str_contains($e, 'ไฟล์ TS')));
        $this->assertTrue(collect($errors)->contains(fn ($e) => str_contains($e, 'เค้าโครง')));
        $this->assertTrue(collect($errors)->contains(fn ($e) => str_contains($e, 'ลายมือชื่อดิจิทัล')));
        $this->assertTrue(collect($errors)->contains(fn ($e) => str_contains($e, 'หนังสือชี้แจง')));
        $this->assertTrue(collect($errors)->contains(fn ($e) => str_contains($e, 'วันที่สอบ')));
    }

    #[Test]
    public function complete_submission_has_no_errors(): void
    {
        $errors = (new ThesisGradeComplianceService)->errorsForSubmit(true, true, true, [
            [
                'student_code' => '653020001-1',
                'student_name' => 'สมชาย',
                'degree' => 'master',
                'thesis_terms_count' => 3,
                'proposal_approved' => false,
                'grade' => 'S',
                'progress_credits' => 0,
                'completed' => true,
                'defense_date' => '2026-03-01',
                'has_s0_letter' => true,
            ],
        ]);

        $this->assertSame([], $errors);
    }
}
