<?php

namespace Tests\Unit;

use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Models\GradeStd;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use App\Services\FacultyAdmin\RegGradeDepartmentService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegGradeAttachedFilesForSectionTest extends TestCase
{
    #[Test]
    public function it_shows_section_specific_and_course_level_files_on_each_row(): void
    {
        $report = $this->multiSectionReport();

        $service = $this->makeService();

        $sec1 = $service->attachedFilesForSection($report, 1);
        $sec2 = $service->attachedFilesForSection($report, '02');

        $this->assertSame(
            [
                'แบบรายงานผลการสอบไล่-Sec1',
                'ใบส่งผลการศึกษา (REG)-Sec1',
                'แบบรายงานผลการสอบไล่',
            ],
            $sec1->pluck('type_label')->all(),
        );
        $this->assertSame(
            [
                'ใบส่งผลการศึกษา (REG-Admin)-Sec2',
                'แบบรายงานผลการสอบไล่',
            ],
            $sec2->pluck('type_label')->all(),
        );

        $this->assertSame([11, 12, 30], $sec1->pluck('file_id')->all());
        $this->assertSame([21, 30], $sec2->pluck('file_id')->all());
    }

    #[Test]
    public function it_does_not_drop_section_one_when_a_later_section_file_is_newer(): void
    {
        $report = $this->multiSectionReport();
        $service = $this->makeService();

        $sec1Ids = $service->attachedFilesForSection($report, 1)->pluck('file_id')->all();

        $this->assertContains(11, $sec1Ids);
        $this->assertContains(12, $sec1Ids);
        $this->assertNotContains(21, $sec1Ids);
    }

    private function makeService(): RegGradeDepartmentService
    {
        return new RegGradeDepartmentService($this->createMock(DepartmentSubjectFilter::class));
    }

    private function multiSectionReport(): GradeReport
    {
        $report = new GradeReport([
            'subject_code' => 'SC203001',
            'username' => 'teacher01',
        ]);
        $report->grade_id = 500;
        $report->setRelation('gradeStds', collect([
            new GradeStd(['sec' => '1', 'total_std' => 20]),
            new GradeStd(['sec' => '2', 'total_std' => 25]),
        ]));

        $examSec1 = new GradeReportFile([
            'grade_id' => 500,
            'file_type' => GradeReportFile::TYPE_EXAM_REPORT,
            'original_name' => '2568_2_SC203001_01.pdf',
            'username' => 'teacher01',
        ]);
        $examSec1->file_id = 11;

        $regSec1 = new GradeReportFile([
            'grade_id' => 500,
            'file_type' => GradeReportFile::TYPE_REGISTRAR,
            'original_name' => 'REG_2568_2_SC203001_01.pdf',
            'username' => 'teacher01',
        ]);
        $regSec1->file_id = 12;

        $regSec2 = new GradeReportFile([
            'grade_id' => 500,
            'file_type' => GradeReportFile::TYPE_REGISTRAR,
            'original_name' => 'REG_2568_2_SC203001_02.pdf',
            'username' => 'deptadmin',
        ]);
        $regSec2->file_id = 21;

        $courseLevelExam = new GradeReportFile([
            'grade_id' => 500,
            'file_type' => GradeReportFile::TYPE_EXAM_REPORT,
            'original_name' => 'exam-report.pdf',
            'username' => 'teacher01',
        ]);
        $courseLevelExam->file_id = 30;

        $files = collect([$examSec1, $regSec1, $regSec2, $courseLevelExam]);
        $files->each(fn (GradeReportFile $file) => $file->setRelation('gradeReport', $report));
        $report->setRelation('files', $files);

        return $report;
    }
}
