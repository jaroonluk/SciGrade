<?php

namespace Tests\Feature;

use App\Models\GradeReport;
use App\Models\GradeStd;
use App\Support\ThaiDateTime;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GradeReportPrintSectionsTest extends TestCase
{
    #[Test]
    public function wizard_print_shows_only_requested_sections(): void
    {
        $report = $this->reportWithSections([1, 5, 7]);

        $html = view('grade-reports.print', [
            'gradeReport' => $report,
            'printStds' => $report->gradeStds->where('sec', '5')->values(),
            'teacherSignName' => 'อาจารย์ ทดสอบ',
            'printedAt' => ThaiDateTime::formatPrintFooter(),
        ])->render();

        $this->assertStringContainsString('<td>5 SC', $html);
        $this->assertStringNotContainsString('<td>1 SC', $html);
        $this->assertStringNotContainsString('<td>7 SC', $html);
    }

    #[Test]
    public function list_print_shows_every_filled_section_together(): void
    {
        $report = $this->reportWithSections([1, 5, 7]);

        $html = view('grade-reports.print', [
            'gradeReport' => $report,
            'printStds' => $report->gradeStds->sortBy(fn ($row) => (int) $row->sec)->values(),
            'teacherSignName' => 'อาจารย์ ทดสอบ',
            'printedAt' => ThaiDateTime::formatPrintFooter(),
        ])->render();

        $this->assertStringContainsString('<td>1 SC', $html);
        $this->assertStringContainsString('<td>5 SC', $html);
        $this->assertStringContainsString('<td>7 SC', $html);
    }

    /**
     * @param  list<int>  $sections
     */
    private function reportWithSections(array $sections): GradeReport
    {
        $report = new GradeReport([
            'subject_code' => 'SC700001',
            'subject' => 'SEMINAR',
            'teacher' => 'อาจารย์ ทดสอบ',
            'term' => '2',
            'year' => '2568',
            'reason' => '',
            'mean' => 0,
            'sd' => 0,
        ]);
        $report->grade_id = 305955;
        $report->setRelation('gradeStds', collect($sections)->map(function (int $sec) {
            return new GradeStd([
                'sec' => (string) $sec,
                'fac' => 'SC',
                'total_std' => 10,
                'num_a' => 10,
                'type_course' => 1,
            ]);
        }));

        return $report;
    }
}
