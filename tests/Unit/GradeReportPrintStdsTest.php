<?php

namespace Tests\Unit;

use App\Models\GradeReport;
use App\Models\GradeStd;
use App\Support\GradeReportPrintStds;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GradeReportPrintStdsTest extends TestCase
{
    #[Test]
    public function my_page_without_sections_query_returns_every_filled_section(): void
    {
        $secs = GradeReportPrintStds::forRequest(
            Request::create('/grade-reports/1/print', 'GET', ['scope' => 'all']),
            $this->reportWithSections([1, 5, 7])
        )->pluck('sec')->all();

        $this->assertSame(['1', '5', '7'], $secs);
    }

    #[Test]
    public function dept_or_list_print_without_sections_returns_all(): void
    {
        $secs = GradeReportPrintStds::forRequest(
            Request::create('/grade-reports/1/print', 'GET'),
            $this->reportWithSections([1, 5])
        )->pluck('sec')->all();

        $this->assertSame(['1', '5'], $secs);
    }

    #[Test]
    public function wizard_print_keeps_only_the_sections_just_entered(): void
    {
        $secs = GradeReportPrintStds::forRequest(
            Request::create('/grade-reports/1/print', 'GET', ['sections' => ['5']]),
            $this->reportWithSections([1, 5, 7])
        )->pluck('sec')->all();

        $this->assertSame(['5'], $secs);
    }

    #[Test]
    public function wizard_print_with_empty_sections_does_not_fall_back_to_all(): void
    {
        $request = Request::create('/grade-reports/1/print?sections=', 'GET');

        $secs = GradeReportPrintStds::forRequest(
            $request,
            $this->reportWithSections([1, 5])
        );

        $this->assertTrue($secs->isEmpty());
    }

    /**
     * @param  list<int>  $sections
     */
    private function reportWithSections(array $sections): GradeReport
    {
        $report = new GradeReport([
            'subject_code' => 'SC700001',
            'subject' => 'SEMINAR',
        ]);
        $report->setRelation('gradeStds', collect($sections)->map(
            fn (int $sec) => new GradeStd(['sec' => (string) $sec, 'fac' => 'SC'])
        ));

        return $report;
    }
}
