<?php

namespace Tests\Unit;

use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Models\GradeStd;
use App\Services\GradeReportAttachmentNameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GradeReportAttachmentNameServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_builds_first_filename_from_report_metadata(): void
    {
        try {
            $report = $this->makeReportWithSection();
        } catch (\Throwable $e) {
            $this->markTestSkipped('scigrad database not available: '.$e->getMessage());
        }

        $name = (new GradeReportAttachmentNameService)->generateDisplayName($report);

        $this->assertSame('2568_2_SC101011_01.pdf', $name);
    }

    #[Test]
    public function it_appends_sequence_for_additional_uploads(): void
    {
        try {
            $report = $this->makeReportWithSection();
            GradeReportFile::query()->create([
                'grade_id' => $report->grade_id,
                'original_name' => '2568_2_SC101011_01.pdf',
                'stored_path' => 'grade-report-files/'.$report->grade_id.'/2568_2_SC101011_01.pdf',
                'uploaded_at' => now(),
                'username' => 'test',
            ]);
        } catch (\Throwable $e) {
            $this->markTestSkipped('scigrad database not available: '.$e->getMessage());
        }

        $name = (new GradeReportAttachmentNameService)->generateDisplayName($report);

        $this->assertSame('2568_2_SC101011_01_02.pdf', $name);
    }

    private function makeReportWithSection(): GradeReport
    {
        $report = GradeReport::query()->create([
            'created' => now()->toDateString(),
            'term' => '2',
            'year' => '2568',
            'subject_code' => 'SC101011',
            'subject_code2' => 'SC101011',
            'subject' => 'Test Subject',
            'username' => 'test',
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
        ]);

        GradeStd::query()->create([
            'grade_id' => $report->grade_id,
            'sec' => 1,
            'fac' => 'SC',
            'total_std' => 10,
            'num_a' => 1,
            'num_bb' => 1,
            'num_b' => 1,
            'num_cc' => 1,
            'num_c' => 1,
            'num_dd' => 1,
            'num_d' => 1,
            'num_f' => 1,
            'num_ff' => 0,
            'num_i' => 0,
            'num_s' => 0,
            'num_v' => 0,
            'num_w' => 0,
            'num_out' => 0,
            'type_course' => 1,
        ]);

        return $report->fresh('gradeStds');
    }
}
