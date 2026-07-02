<?php

namespace Tests\Unit;

use App\Models\GradeTerm;
use App\Support\AcademicTerm;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AcademicTermTest extends TestCase
{
    #[Test]
    public function it_uses_grade_term_table_when_available(): void
    {
        try {
            $row = GradeTerm::query()->orderBy('id')->first();
        } catch (\Throwable $e) {
            $this->markTestSkipped('scigrad database not available: '.$e->getMessage());
        }

        if ($row === null) {
            $this->markTestSkipped('grade_term table has no rows.');
        }

        $this->assertSame((int) $row->term, AcademicTerm::defaultTerm());
        $this->assertSame((int) $row->year, AcademicTerm::defaultYear());
    }
}
