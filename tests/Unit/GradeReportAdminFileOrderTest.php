<?php

namespace Tests\Unit;

use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Models\GradeStd;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GradeReportAdminFileOrderTest extends TestCase
{
    #[Test]
    public function it_lists_every_exam_file_oldest_first_then_registrar_by_section(): void
    {
        $report = $this->reportWithMixedFiles();

        $exams = $report->sortedExamFiles();
        $regs = $report->sortedRegistrarFiles();

        $this->assertSame([10, 30], $exams->pluck('file_id')->all());
        $this->assertSame([12, 21, 22], $regs->pluck('file_id')->all());
        $this->assertSame(
            [1, 2, 2],
            $regs->map(fn (GradeReportFile $file) => $file->resolvedSection($report))->all(),
        );
        $this->assertFalse($regs[1]->isDeptAdminUpload($report));
        $this->assertTrue($regs[2]->isDeptAdminUpload($report));
    }

    private function reportWithMixedFiles(): GradeReport
    {
        $report = new GradeReport([
            'subject_code' => 'SC203001',
            'username' => 'teacher01',
        ]);
        $report->grade_id = 700;
        $report->setRelation('gradeStds', collect([
            new GradeStd(['sec' => '1', 'total_std' => 20]),
            new GradeStd(['sec' => '2', 'total_std' => 25]),
        ]));

        $examOld = new GradeReportFile([
            'grade_id' => 700,
            'file_type' => GradeReportFile::TYPE_EXAM_REPORT,
            'original_name' => '2568_2_SC203001_01.pdf',
            'username' => 'teacher01',
        ]);
        $examOld->file_id = 10;

        $examNew = new GradeReportFile([
            'grade_id' => 700,
            'file_type' => GradeReportFile::TYPE_EXAM_REPORT,
            'original_name' => '2568_2_SC203001_01_02.pdf',
            'username' => 'teacher01',
        ]);
        $examNew->file_id = 30;

        $regSec2Instructor = new GradeReportFile([
            'grade_id' => 700,
            'file_type' => GradeReportFile::TYPE_REGISTRAR,
            'original_name' => 'SC203001-02.pdf',
            'username' => 'teacher01',
        ]);
        $regSec2Instructor->file_id = 21;

        $regSec2Dept = new GradeReportFile([
            'grade_id' => 700,
            'file_type' => GradeReportFile::TYPE_REGISTRAR,
            'original_name' => 'REG_2568_2_SC203001_02.pdf',
            'username' => 'deptadmin',
        ]);
        $regSec2Dept->file_id = 22;

        $regSec1 = new GradeReportFile([
            'grade_id' => 700,
            'file_type' => GradeReportFile::TYPE_REGISTRAR,
            'original_name' => 'SC203001-01.pdf',
            'username' => 'teacher01',
        ]);
        $regSec1->file_id = 12;

        $files = collect([$examNew, $regSec2Dept, $regSec2Instructor, $examOld, $regSec1]);
        $files->each(fn (GradeReportFile $file) => $file->setRelation('gradeReport', $report));
        $report->setRelation('files', $files);

        return $report;
    }
}
