<?php

namespace Tests\Feature;

use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Models\GradeStd;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GradeReportFilesAdminPartialTest extends TestCase
{
    #[Test]
    public function it_lists_every_exam_file_before_registrar_files_sorted_by_section(): void
    {
        $this->actingAs(new User(['name' => 'Dept Admin', 'email' => 'dept@kku.ac.th']));

        $report = new GradeReport([
            'subject_code' => 'SC700001',
            'username' => 'teacher01',
        ]);
        $report->grade_id = 700;
        $report->setRelation('gradeStds', collect([
            new GradeStd(['sec' => '1', 'total_std' => 10]),
            new GradeStd(['sec' => '3', 'total_std' => 8]),
            new GradeStd(['sec' => '2', 'total_std' => 12]),
        ]));

        $exam1 = new GradeReportFile([
            'grade_id' => 700,
            'file_type' => GradeReportFile::TYPE_EXAM_REPORT,
            'original_name' => '2568_2_SC700001_01.pdf',
            'username' => 'teacher01',
        ]);
        $exam1->file_id = 1;

        $exam2 = new GradeReportFile([
            'grade_id' => 700,
            'file_type' => GradeReportFile::TYPE_EXAM_REPORT,
            'original_name' => '2568_2_SC700001_01_02.pdf',
            'username' => 'teacher01',
        ]);
        $exam2->file_id = 2;

        $reg3 = new GradeReportFile([
            'grade_id' => 700,
            'file_type' => GradeReportFile::TYPE_REGISTRAR,
            'original_name' => 'SC700001-03.pdf',
            'username' => 'teacher01',
        ]);
        $reg3->file_id = 13;

        $reg1 = new GradeReportFile([
            'grade_id' => 700,
            'file_type' => GradeReportFile::TYPE_REGISTRAR,
            'original_name' => 'SC700001-01.pdf',
            'username' => 'teacher01',
        ]);
        $reg1->file_id = 11;

        $reg2 = new GradeReportFile([
            'grade_id' => 700,
            'file_type' => GradeReportFile::TYPE_REGISTRAR,
            'original_name' => 'SC700001-02.pdf',
            'username' => 'teacher01',
        ]);
        $reg2->file_id = 12;

        $files = collect([$reg3, $exam2, $reg1, $exam1, $reg2]);
        $files->each(fn (GradeReportFile $file) => $file->setRelation('gradeReport', $report));
        $report->setRelation('files', $files);

        $html = view('partials.grade-report-files-admin', ['report' => $report])->render();

        $this->assertStringContainsString('2568_2_SC700001_01.pdf', $html);
        $this->assertStringContainsString('2568_2_SC700001_01_02.pdf', $html);

        $exam1Pos = strpos($html, '2568_2_SC700001_01.pdf');
        $exam2Pos = strpos($html, '2568_2_SC700001_01_02.pdf');
        $reg1Pos = strpos($html, 'ใบส่งผลการศึกษา (REG)-Sec1');
        $reg2Pos = strpos($html, 'ใบส่งผลการศึกษา (REG)-Sec2');
        $reg3Pos = strpos($html, 'ใบส่งผลการศึกษา (REG)-Sec3');

        $this->assertNotFalse($exam1Pos);
        $this->assertNotFalse($exam2Pos);
        $this->assertNotFalse($reg1Pos);
        $this->assertNotFalse($reg2Pos);
        $this->assertNotFalse($reg3Pos);
        $this->assertLessThan($exam2Pos, $exam1Pos);
        $this->assertLessThan($reg1Pos, $exam2Pos);
        $this->assertLessThan($reg2Pos, $reg1Pos);
        $this->assertLessThan($reg3Pos, $reg2Pos);
    }
}
