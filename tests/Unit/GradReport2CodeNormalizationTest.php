<?php

namespace Tests\Unit;

use App\Models\GradReport2;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GradReport2CodeNormalizationTest extends TestCase
{
    #[Test]
    public function it_strips_spaces_and_uppercases_subject_codes(): void
    {
        $this->assertSame('SC213501', GradReport2::normalizeCode(' sc213501 '));
        $this->assertSame('SC213501', GradReport2::normalizeCode("SC213501\t"));
        $this->assertSame('SC213501', GradReport2::normalizeCode("SC\u{00A0}213501"));
    }

    #[Test]
    public function it_builds_sql_only_for_code_columns(): void
    {
        $sql = GradReport2::normalizedCodeSql('subject_code2');

        $this->assertStringContainsString('TRIM(`subject_code2`)', $sql);
        $this->assertStringContainsString('UPPER', $sql);

        $this->expectException(\InvalidArgumentException::class);
        GradReport2::normalizedCodeSql('subject');
    }
}
