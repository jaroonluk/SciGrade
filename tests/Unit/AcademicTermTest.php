<?php

namespace Tests\Unit;

use App\Models\GradeTerm;
use App\Support\AcademicTerm;
use Illuminate\Http\Request;
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

    #[Test]
    public function it_reads_term_and_year_from_the_request(): void
    {
        $selected = AcademicTerm::fromRequest(Request::create('/dashboard', 'GET', [
            'term' => 2,
            'year' => 2568,
        ]));

        $this->assertSame(['term' => 2, 'year' => 2568], $selected);
    }
}
