<?php

namespace Tests\Feature;

use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Models\GradeStd;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GradeReportMyExamFileLabelTest extends TestCase
{
    #[Test]
    public function my_page_shows_exam_files_by_submission_order_without_section(): void
    {
        $this->actingAs(new User(['name' => 'อาจารย์ ทดสอบ', 'email' => 'teacher@kku.ac.th']));

        $report = new GradeReport([
            'subject_code' => 'SC700001',
            'subject' => 'SEMINAR',
            'term' => '2',
            'year' => '2568',
            'approv' => 0,
            'username' => 'teacher01',
        ]);
        $report->grade_id = 305955;
        $report->setRelation('gradeStds', collect([
            new GradeStd(['sec' => '1', 'total_std' => 10]),
            new GradeStd(['sec' => '5', 'total_std' => 8]),
        ]));
        $report->setRelation('approvalLogs', collect());

        $first = new GradeReportFile([
            'grade_id' => 305955,
            'file_type' => GradeReportFile::TYPE_EXAM_REPORT,
            'original_name' => '2568_2_SC700001_01.pdf',
        ]);
        $first->file_id = 11;
        $second = new GradeReportFile([
            'grade_id' => 305955,
            'file_type' => GradeReportFile::TYPE_EXAM_REPORT,
            'original_name' => '2568_2_SC700001_05.pdf',
        ]);
        $second->file_id = 30;
        $report->setRelation('files', collect([$second, $first]));

        $html = view('grade-reports.my', [
            'reports' => collect([$report]),
            'term' => 2,
            'year' => 2568,
            'years' => [2568],
        ])->render();

        $this->assertStringContainsString('แบบรายงานผลการสอบไล่(1)', $html);
        $this->assertStringContainsString('แบบรายงานผลการสอบไล่(2)', $html);
        $this->assertStringNotContainsString('2568_2_SC700001_01.pdf', $html);
        $this->assertStringNotContainsString('2568_2_SC700001_05.pdf', $html);
        $this->assertStringNotContainsString('Sec1', $html);
        $this->assertStringNotContainsString('Sec5', $html);
        $this->assertLessThan(
            strpos($html, 'แบบรายงานผลการสอบไล่(2)'),
            strpos($html, 'แบบรายงานผลการสอบไล่(1)'),
        );
    }
}
