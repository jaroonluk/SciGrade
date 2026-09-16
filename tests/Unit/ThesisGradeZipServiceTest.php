<?php

namespace Tests\Unit;

use App\Models\ThesisGrade;
use App\Models\ThesisGradeFile;
use App\Services\ThesisGrade\ThesisGradeZipService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThesisGradeZipServiceTest extends TestCase
{
    #[Test]
    public function zip_groups_same_file_types_into_single_type_folders(): void
    {
        $service = new ThesisGradeZipService;
        $report = new ThesisGrade([
            'subject_code' => 'SC899001',
            'section' => '1',
            'term' => 2,
            'year' => 2568,
        ]);
        $report->thesis_grade_id = 10;

        $ts = new ThesisGradeFile([
            'thesis_grade_id' => 10,
            'file_type' => ThesisGradeFile::TYPE_TS_REPORT,
            'original_name' => 'anything.pdf',
        ]);
        $s0 = new ThesisGradeFile([
            'thesis_grade_id' => 10,
            'file_type' => ThesisGradeFile::TYPE_S0_LETTER,
            'original_name' => 'memo.pdf',
        ]);
        $chair = new ThesisGradeFile([
            'thesis_grade_id' => 10,
            'file_type' => ThesisGradeFile::TYPE_CHAIR_SIGNED,
            'original_name' => 'chair.pdf',
        ]);

        $used = [];
        $this->assertSame('TS/TS-SC899001-01-2-2568.pdf', $service->zipEntryNameFor($ts, $report, $used));
        $this->assertSame('S0/TS-SC899001-01-2-2568.pdf', $service->zipEntryNameFor($s0, $report, $used));
        $this->assertSame('สาขา/TS-SC899001-01-2-2568.pdf', $service->zipEntryNameFor($chair, $report, $used));
    }

    #[Test]
    public function same_type_files_from_different_courses_share_one_folder(): void
    {
        $service = new ThesisGradeZipService;
        $a = new ThesisGrade(['subject_code' => 'SC899001', 'section' => '1', 'term' => 2, 'year' => 2568]);
        $a->thesis_grade_id = 1;
        $b = new ThesisGrade(['subject_code' => 'SC899002', 'section' => '2', 'term' => 2, 'year' => 2568]);
        $b->thesis_grade_id = 2;

        $fileA = new ThesisGradeFile(['thesis_grade_id' => 1, 'file_type' => ThesisGradeFile::TYPE_TS_REPORT]);
        $fileB = new ThesisGradeFile(['thesis_grade_id' => 2, 'file_type' => ThesisGradeFile::TYPE_TS_REPORT]);

        $used = [];
        $this->assertSame('TS/TS-SC899001-01-2-2568.pdf', $service->zipEntryNameFor($fileA, $a, $used));
        $this->assertSame('TS/TS-SC899002-02-2-2568.pdf', $service->zipEntryNameFor($fileB, $b, $used));
    }

    #[Test]
    public function duplicate_names_in_same_folder_get_numeric_suffix(): void
    {
        $service = new ThesisGradeZipService;
        $report = new ThesisGrade(['subject_code' => 'SC899001', 'section' => '1', 'term' => 2, 'year' => 2568]);
        $report->thesis_grade_id = 1;

        $first = new ThesisGradeFile(['thesis_grade_id' => 1, 'file_type' => ThesisGradeFile::TYPE_TS_REPORT]);
        $second = new ThesisGradeFile(['thesis_grade_id' => 1, 'file_type' => ThesisGradeFile::TYPE_TS_REPORT]);

        $used = [];
        $this->assertSame('TS/TS-SC899001-01-2-2568.pdf', $service->zipEntryNameFor($first, $report, $used));
        $this->assertSame('TS/TS-SC899001-01-2-2568_1.pdf', $service->zipEntryNameFor($second, $report, $used));
    }
}
