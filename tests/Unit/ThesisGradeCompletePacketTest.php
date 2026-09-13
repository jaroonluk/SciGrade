<?php

namespace Tests\Unit;

use App\Models\ThesisGrade;
use App\Models\ThesisGradeFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThesisGradeCompletePacketTest extends TestCase
{
    #[Test]
    public function complete_packet_prefers_department_chair_file_over_instructor_ts(): void
    {
        $report = $this->reportWithFiles([
            ThesisGradeFile::TYPE_TS_REPORT => 'instructor-ts.pdf',
            ThesisGradeFile::TYPE_CHAIR_SIGNED => 'dept-chair.pdf',
            ThesisGradeFile::TYPE_S0_LETTER => 's0.pdf',
        ]);

        $this->assertSame('dept', $report->completeExamSource());
        $this->assertSame(['dept-chair.pdf'], $report->completeExamFiles()->pluck('original_name')->all());
        $this->assertSame(['dept-chair.pdf', 's0.pdf'], $report->completePacketFiles()->pluck('original_name')->all());
    }

    #[Test]
    public function complete_packet_uses_instructor_ts_when_department_did_not_upload(): void
    {
        $report = $this->reportWithFiles([
            ThesisGradeFile::TYPE_TS_REPORT => 'instructor-ts.pdf',
        ]);

        $this->assertSame('instructor', $report->completeExamSource());
        $this->assertSame(['instructor-ts.pdf'], $report->completePacketFiles()->pluck('original_name')->all());
    }

    /**
     * @param  array<string, string>  $files
     */
    private function reportWithFiles(array $files): ThesisGrade
    {
        $report = new ThesisGrade(['subject_code' => 'SC899001', 'section' => '1']);
        $report->thesis_grade_id = 22;
        $id = 1;
        $report->setRelation('files', collect($files)->map(function (string $name, string $type) use (&$id, $report) {
            $file = new ThesisGradeFile([
                'thesis_grade_id' => $report->thesis_grade_id,
                'file_type' => $type,
                'original_name' => $name,
            ]);
            $file->file_id = $id++;

            return $file;
        })->values());

        return $report;
    }
}
