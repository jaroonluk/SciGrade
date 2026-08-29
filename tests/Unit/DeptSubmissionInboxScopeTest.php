<?php

namespace Tests\Unit;

use App\Models\DeptSubmission;
use App\Models\TblPrivilege;
use App\Support\SciGradeRole;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeptSubmissionInboxScopeTest extends TestCase
{
    #[Test]
    public function it_maps_privilege_levels_to_inbox_scopes(): void
    {
        $this->assertSame(SciGradeRole::INBOX_ALL, SciGradeRole::inboxScopeForPrivilegeLevel(TblPrivilege::LEVEL_SERVICE));
        $this->assertSame(SciGradeRole::INBOX_ALL, SciGradeRole::inboxScopeForPrivilegeLevel(TblPrivilege::LEVEL_DEPT));
        $this->assertSame(SciGradeRole::INBOX_ALL, SciGradeRole::inboxScopeForPrivilegeLevel(TblPrivilege::LEVEL_SUPER));
        $this->assertSame(SciGradeRole::INBOX_ALL, SciGradeRole::inboxScopeForPrivilegeLevel(null));
        $this->assertSame(
            SciGradeRole::INBOX_BACHELOR,
            SciGradeRole::inboxScopeForPrivilegeLevel(TblPrivilege::LEVEL_SERVICE_BACHELOR),
        );
        $this->assertSame(
            SciGradeRole::INBOX_NON_BACHELOR,
            SciGradeRole::inboxScopeForPrivilegeLevel(TblPrivilege::LEVEL_SERVICE_GRADUATE),
        );
    }

    #[Test]
    public function it_labels_new_service_privilege_levels(): void
    {
        $this->assertSame('เจ้าหน้าที่งานบริการ', TblPrivilege::labelForLevel(TblPrivilege::LEVEL_SERVICE));
        $this->assertSame('เจ้าหน้าที่งานบริการ(ป.ตรี)', TblPrivilege::labelForLevel(TblPrivilege::LEVEL_SERVICE_BACHELOR));
        $this->assertSame('เจ้าหน้าที่งานบริการ(ป.บัณฑิต)', TblPrivilege::labelForLevel(TblPrivilege::LEVEL_SERVICE_GRADUATE));
    }

    #[Test]
    public function it_filters_submissions_by_education_level_scope(): void
    {
        $rows = [
            ['id' => 1, 'education_level' => DeptSubmission::EDUCATION_BACHELOR],
            ['id' => 2, 'education_level' => DeptSubmission::EDUCATION_GRADUATE],
            ['id' => 3, 'education_level' => 'bachelor'],
        ];

        $bachelor = DeptSubmission::filterRowsForInbox($rows, SciGradeRole::INBOX_BACHELOR);
        $this->assertSame([1, 3], array_column($bachelor, 'id'));

        $graduate = DeptSubmission::filterRowsForInbox($rows, SciGradeRole::INBOX_NON_BACHELOR);
        $this->assertSame([2], array_column($graduate, 'id'));

        $all = DeptSubmission::filterRowsForInbox($rows, SciGradeRole::INBOX_ALL);
        $this->assertCount(3, $all);
    }

    #[Test]
    public function it_normalizes_unknown_education_levels_to_bachelor(): void
    {
        $this->assertSame(DeptSubmission::EDUCATION_BACHELOR, DeptSubmission::normalizeEducationLevel(null));
        $this->assertSame(DeptSubmission::EDUCATION_BACHELOR, DeptSubmission::normalizeEducationLevel(''));
        $this->assertSame(DeptSubmission::EDUCATION_GRADUATE, DeptSubmission::normalizeEducationLevel('graduate'));
        $this->assertTrue(DeptSubmission::matchesInboxScope('graduate', SciGradeRole::INBOX_NON_BACHELOR));
        $this->assertFalse(DeptSubmission::matchesInboxScope('bachelor', SciGradeRole::INBOX_NON_BACHELOR));
    }
}
