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
    public function it_shows_only_registrar_files_for_the_matching_section(): void
    {
        $report = $this->multiSectionReport();
        $service = $this->makeService();

        $sec1 = $service->attachedFilesForSection($report, 1);
        $sec2 = $service->attachedFilesForSection($report, '02');

        $this->assertSame(['ใบส่งผลการศึกษา (REG)-Sec1'], $sec1->pluck('type_label')->all());
        $this->assertSame(['ใบส่งผลการศึกษา (REG-Admin)-Sec2'], $sec2->pluck('type_label')->all());
        $this->assertSame([12], $sec1->pluck('file_id')->all());
        $this->assertSame([21], $sec2->pluck('file_id')->all());
        $this->assertSame([500], $sec1->pluck('grade_id')->all());
    }

    #[Test]
    public function it_does_not_show_another_section_file_on_an_empty_row(): void
    {
        $report = new GradeReport([
            'subject_code' => 'SC203001',
            'username' => 'teacher01',
        ]);
        $report->grade_id = 501;
        $report->setRelation('gradeStds', collect([
            new GradeStd(['sec' => '2', 'total_std' => 25]),
        ]));

        $regSec2 = new GradeReportFile([
            'grade_id' => 501,
            'file_type' => GradeReportFile::TYPE_REGISTRAR,
            'original_name' => 'REG_2568_2_SC203001_02.pdf',
            'username' => 'teacher01',
        ]);
        $regSec2->file_id = 22;
        $regSec2->setRelation('gradeReport', $report);
        $report->setRelation('files', collect([$regSec2]));

        $sec1 = $this->makeService()->attachedFilesForSection($report, 1);

        $this->assertSame([], $sec1->pluck('file_id')->all());
    }

    #[Test]
    public function it_leaves_a_section_without_its_own_file_empty(): void
    {
        $report = $this->multiSectionReport();
        $report->setRelation('gradeStds', collect([
            new GradeStd(['sec' => '1', 'total_std' => 20]),
            new GradeStd(['sec' => '5', 'total_std' => 18]),
        ]));

        $sec7 = $this->makeService()->attachedFilesForSection($report, 7);

        $this->assertTrue($sec7->isEmpty());
    }

    #[Test]
    public function it_does_not_drop_section_one_when_a_later_section_file_is_newer(): void
    {
        $report = $this->multiSectionReport();
        $service = $this->makeService();

        $sec1Ids = $service->attachedFilesForSection($report, 1)->pluck('file_id')->all();

        $this->assertContains(12, $sec1Ids);
        $this->assertNotContains(11, $sec1Ids);
        $this->assertNotContains(21, $sec1Ids);
        $this->assertNotContains(30, $sec1Ids);
    }

    #[Test]
    public function it_lists_all_exam_files_for_the_course_and_not_on_later_sections(): void
    {
        $report = $this->multiSectionReport();
        $service = $this->makeService();

        $exams = $service->examFilesForReports(collect([$report]));
        $sec2 = $service->attachedFilesForSection($report, 2);

        $this->assertSame([11, 30], $exams->pluck('file_id')->all());
        $this->assertSame([500, 500], $exams->pluck('grade_id')->all());
        $this->assertTrue($exams->every(fn (object $file) => $file->file_type === GradeReportFile::TYPE_EXAM_REPORT));
        $this->assertNotContains(11, $sec2->pluck('file_id')->all());
        $this->assertNotContains(30, $sec2->pluck('file_id')->all());
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
