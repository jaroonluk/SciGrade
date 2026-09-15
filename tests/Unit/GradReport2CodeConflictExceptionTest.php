<?php

namespace Tests\Unit;

use App\Exceptions\GradReport2CodeConflictException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GradReport2CodeConflictExceptionTest extends TestCase
{
    #[Test]
    public function message_lists_conflict_groups_and_focus_hint(): void
    {
        $e = new GradReport2CodeConflictException([
            [
                'code' => 'SC002104',
                'group_code' => '300109',
                'subject' => 'PHYSICAL SCIENCE',
            ],
        ], 'member_codes', '999001');

        $this->assertSame('300109', $e->focusGroup());
        $this->assertStringContainsString('SC002104', $e->getMessage());
        $this->assertStringContainsString('300109', $e->getMessage());
        $this->assertStringContainsString('PHYSICAL SCIENCE', $e->getMessage());
        $this->assertStringContainsString('999001', $e->getMessage());
        $this->assertStringContainsString('กด «ตกลง»', $e->getMessage());
    }
}
