<?php

namespace Tests\Feature;

use App\Models\GradeReport;
use App\Models\GradeStd;
use App\Models\User;
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

    #[Test]
    public function contributor_can_open_wizard_print_for_shared_draft(): void
    {
        try {
            $report = $this->persistReport('owner01', 0);
        } catch (\Throwable $e) {
            $this->markTestSkipped('scigrad database not available: '.$e->getMessage());
        }

        $this->actingAs(new User(['id' => 1, 'name' => 'อาจารย์ ร่วม', 'email' => 'contrib@kku.ac.th']))
            ->withSession([
                'staff_username' => 'teacher02',
                'scigrade_role' => 'instructor',
            ])
            ->get('/grade-reports/'.$report->grade_id.'/print?sections[]=5')
            ->assertOk()
            ->assertSee('แบบรายงานผลการสอบไล่', false);
    }

    #[Test]
    public function contributor_cannot_open_print_for_approved_shared_report(): void
    {
        try {
            $report = $this->persistReport('owner01', 1);
        } catch (\Throwable $e) {
            $this->markTestSkipped('scigrad database not available: '.$e->getMessage());
        }

        $this->actingAs(new User(['id' => 1, 'name' => 'อาจารย์ ร่วม', 'email' => 'contrib@kku.ac.th']))
            ->withSession([
                'staff_username' => 'teacher02',
                'scigrade_role' => 'instructor',
            ])
            ->get('/grade-reports/'.$report->grade_id.'/print')
            ->assertForbidden();
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

    private function persistReport(string $username, int $approv): GradeReport
    {
        $report = GradeReport::query()->create([
            'created' => now()->toDateString(),
            'term' => '2',
            'year' => '2568',
            'subject_code' => 'SC101011',
            'subject_code2' => 'SC101011',
            'subject' => 'Test Subject',
            'username' => $username,
            'teacher' => 'อาจารย์ ทดสอบ',
            'score_a' => '0',
            'score_bb' => '0',
            'score_b' => '0',
            'score_cc' => '0',
            'score_c' => '0',
            'score_dd' => '0',
            'score_d' => '0',
            'score_f' => '0',
            'mean' => '0',
            'sd' => '0',
            'reason' => '',
            'programid' => '',
            'degree' => 0,
            'selecttype' => 1,
            'intflag' => 0,
            'approv' => $approv,
        ]);

        GradeStd::query()->create([
            'grade_id' => $report->grade_id,
            'sec' => 5,
            'fac' => 'SC',
            'total_std' => 10,
            'num_a' => 10,
            'type_course' => 1,
        ]);

        return $report->fresh(['gradeStds', 'approvalLogs']) ?? $report;
    }
}
