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
    public function my_page_hides_exam_report_column_and_print_for_instructors(): void
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
            'staffUsername' => 'teacher01',
        ])->render();

        $this->assertStringNotContainsString('แบบรายงานผลการสอบไล่(1)', $html);
        $this->assertStringNotContainsString('แบบรายงานผลการสอบไล่(2)', $html);
        $this->assertStringNotContainsString('data-file-type="exam_report"', $html);
        $this->assertStringNotContainsString('grade-reports/305955/print', $html);
        $this->assertStringContainsString('ใบส่งผลการศึกษา (REG)', $html);
        $this->assertStringContainsString('จัดทำโดย Admin สาขา', $html);
    }

    #[Test]
    public function my_page_lists_i_letter_after_instructor_registrar_files(): void
    {
        $this->actingAs(new User(['name' => 'อาจารย์ ทดสอบ', 'email' => 'teacher@kku.ac.th']));

        $report = new GradeReport([
            'subject_code' => 'SC101011',
            'subject' => 'Test',
            'term' => '1',
            'year' => '2568',
            'approv' => 0,
            'username' => 'teacher01',
        ]);
        $report->grade_id = 305942;
        $report->setRelation('gradeStds', collect([
            new GradeStd(['sec' => '1', 'total_std' => 10, 'num_i' => 1]),
        ]));
        $report->setRelation('approvalLogs', collect());

        $reg = new GradeReportFile([
            'grade_id' => 305942,
            'file_type' => GradeReportFile::TYPE_REGISTRAR,
            'original_name' => 'REG_2568_1_SC101011_01.pdf',
            'username' => 'teacher01',
        ]);
        $reg->file_id = 21;

        $iLetter = new GradeReportFile([
            'grade_id' => 305942,
            'file_type' => GradeReportFile::TYPE_I_LETTER,
            'original_name' => 'I_2568_1_SC101011.pdf',
            'username' => 'teacher01',
        ]);
        $iLetter->file_id = 22;

        $report->setRelation('files', collect([$reg, $iLetter]));

        $html = view('grade-reports.my', [
            'reports' => collect([$report]),
            'term' => 1,
            'year' => 2568,
            'years' => [2568],
            'staffUsername' => 'teacher01',
            'fillerNames' => [],
        ])->render();

        $this->assertStringContainsString('อาจารย์อัปโหลด', $html);
        $this->assertStringContainsString('REG-SC101011-01', $html);
        $this->assertStringContainsString('data-file-type="i_letter"', $html);
        $this->assertStringContainsString('I_2568_1_SC101011.pdf', $html);

        $regPos = strpos($html, 'REG-SC101011-01');
        $iPos = strpos($html, 'data-file-type="i_letter"');
        $this->assertNotFalse($regPos);
        $this->assertNotFalse($iPos);
        $this->assertLessThan($iPos, $regPos, 'เอกสารติด I ต้องแสดงต่อจาก มข.11 ของอาจารย์');
    }
}
