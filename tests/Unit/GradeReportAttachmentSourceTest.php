<?php

namespace Tests\Unit;

use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Models\GradeStd;
use App\Services\GradeReportFileZipService;
use Tests\TestCase;

class GradeReportAttachmentSourceTest extends TestCase
{
    public function test_dept_registrar_download_name_uses_code_group_and_total(): void
    {
        $report = new GradeReport([
            'subject_code' => 'SC203001',
            'username' => 'teacher01',
        ]);
        $report->setRelation('gradeStds', collect([
            new GradeStd(['sec' => '1', 'total_std' => 20]),
            new GradeStd(['sec' => '2', 'total_std' => 25]),
        ]));

        $this->assertSame('SC203001-01_02-45.pdf', $report->deptRegistrarDownloadName());
    }

    public function test_instructor_vs_dept_upload_detection(): void
    {
        $report = new GradeReport(['username' => 'teacher01']);

        $instructorFile = new GradeReportFile([
            'file_type' => GradeReportFile::TYPE_REGISTRAR,
            'username' => 'teacher01',
        ]);
        $deptFile = new GradeReportFile([
            'file_type' => GradeReportFile::TYPE_REGISTRAR,
            'username' => 'deptadmin',
        ]);
        $legacyFile = new GradeReportFile([
            'file_type' => GradeReportFile::TYPE_REGISTRAR,
            'username' => null,
        ]);

        $this->assertTrue($instructorFile->isInstructorUpload($report));
        $this->assertFalse($instructorFile->isDeptAdminUpload($report));
        $this->assertTrue($deptFile->isDeptAdminUpload($report));
        $this->assertTrue($legacyFile->isInstructorUpload($report));
    }

    public function test_zip_service_filters_registrar_sources(): void
    {
        $report = new GradeReport([
            'subject_code' => 'SC101',
            'username' => 'teacher01',
        ]);
        $report->grade_id = 10;
        $report->setRelation('gradeStds', collect([
            new GradeStd(['sec' => '01', 'total_std' => 12]),
        ]));

        $exam = new GradeReportFile([
            'grade_id' => 10,
            'file_type' => GradeReportFile::TYPE_EXAM_REPORT,
            'username' => 'teacher01',
            'original_name' => 'exam.pdf',
            'stored_path' => 'x/exam.pdf',
        ]);
        $regInstructor = new GradeReportFile([
            'grade_id' => 10,
            'file_type' => GradeReportFile::TYPE_REGISTRAR,
            'username' => 'teacher01',
            'original_name' => 'reg_i.pdf',
            'stored_path' => 'x/reg_i.pdf',
        ]);
        $regDept = new GradeReportFile([
            'grade_id' => 10,
            'file_type' => GradeReportFile::TYPE_REGISTRAR,
            'username' => 'deptadmin',
            'original_name' => 'reg_d.pdf',
            'stored_path' => 'x/reg_d.pdf',
        ]);

        $report->setRelation('files', collect([$exam, $regInstructor, $regDept]));

        $service = new GradeReportFileZipService;
        $reports = collect([$report]);

        $this->assertCount(1, $service->collectFiles($reports, 'registrar_instructor'));
        $this->assertSame('reg_i.pdf', $service->collectFiles($reports, 'registrar_instructor')->first()->original_name);
        $this->assertCount(1, $service->collectFiles($reports, 'registrar_dept'));
        $this->assertSame('reg_d.pdf', $service->collectFiles($reports, 'registrar_dept')->first()->original_name);
        $this->assertCount(2, $service->collectFiles($reports, 'registrar'));
        $this->assertCount(3, $service->collectFiles($reports, 'all'));
    }

    public function test_batch_zip_puts_reg_admin_files_in_shared_folder_with_code_group_total_name(): void
    {
        $reportA = new GradeReport([
            'subject_code' => 'SC203001',
            'username' => 'teacher01',
        ]);
        $reportA->grade_id = 101;
        $reportA->setRelation('gradeStds', collect([
            new GradeStd(['sec' => '1', 'total_std' => 20]),
        ]));

        $reportB = new GradeReport([
            'subject_code' => 'SC203002',
            'username' => 'teacher02',
        ]);
        $reportB->grade_id = 102;
        $reportB->setRelation('gradeStds', collect([
            new GradeStd(['sec' => '2', 'total_std' => 30]),
        ]));

        $fileA = new GradeReportFile([
            'grade_id' => 101,
            'file_type' => GradeReportFile::TYPE_REGISTRAR,
            'username' => 'deptadmin',
            'original_name' => 'REG_2568_2_SC203001_01.pdf',
            'stored_path' => 'a/reg.pdf',
        ]);
        $fileA->setRelation('gradeReport', $reportA);

        $fileB = new GradeReportFile([
            'grade_id' => 102,
            'file_type' => GradeReportFile::TYPE_REGISTRAR,
            'username' => 'deptadmin',
            'original_name' => 'REG_2568_2_SC203002_02.pdf',
            'stored_path' => 'b/reg.pdf',
        ]);
        $fileB->setRelation('gradeReport', $reportB);

        $service = new GradeReportFileZipService;
        $used = [];

        $pathA = $service->zipEntryPathFor($fileA, $used);
        $pathB = $service->zipEntryPathFor($fileB, $used);

        $this->assertSame('REG-Admin/SC203001-01-20.pdf', $pathA);
        $this->assertSame('REG-Admin/SC203002-02-30.pdf', $pathB);
        $this->assertStringStartsWith('REG-Admin/', $pathA);
        $this->assertStringStartsWith('REG-Admin/', $pathB);
        $this->assertStringNotContainsString('SC203001_101', $pathA);
        $this->assertStringNotContainsString('SC203002_102', $pathB);
    }
}
