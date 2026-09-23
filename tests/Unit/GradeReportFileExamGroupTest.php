<?php

namespace Tests\Unit;

use App\Models\GradeReportFile;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GradeReportFileExamGroupTest extends TestCase
{
    private function makeExamFile(int $fileId, string $username, Carbon $uploadedAt, string $name): GradeReportFile
    {
        $file = new GradeReportFile;
        $file->forceFill([
            'file_type' => GradeReportFile::TYPE_EXAM_REPORT,
            'username' => $username,
            'uploaded_at' => $uploadedAt,
            'original_name' => $name,
        ]);
        $file->setAttribute('file_id', $fileId);
        $file->syncOriginal();

        return $file;
    }

    #[Test]
    public function it_groups_same_upload_across_sections_as_one_display_item(): void
    {
        $stamp = Carbon::parse('2026-09-23 14:00:00', config('app.timezone'));

        $files = collect([
            $this->makeExamFile(10, 'teacher01', $stamp, '2568_2_SC101011_01.pdf'),
            $this->makeExamFile(11, 'teacher01', $stamp, '2568_2_SC101011_02.pdf'),
            $this->makeExamFile(12, 'teacher01', $stamp, '2568_2_SC101011_03.pdf'),
        ]);

        $groups = GradeReportFile::groupExamReportsForDisplay($files);

        $this->assertCount(1, $groups);
        $this->assertSame('แบบรายงานผลการสอบไล่(1)', $groups[0]['label']);
        $this->assertSame(10, (int) $groups[0]['file']->file_id);
        $this->assertSame([10, 11, 12], $groups[0]['file_ids']);
    }

    #[Test]
    public function it_shows_separate_items_for_separate_uploads(): void
    {
        $first = Carbon::parse('2026-09-23 14:00:00', config('app.timezone'));
        $second = Carbon::parse('2026-09-23 15:30:00', config('app.timezone'));

        $files = collect([
            $this->makeExamFile(21, 'teacher01', $first, '2568_2_SC101011_01.pdf'),
            $this->makeExamFile(22, 'teacher01', $first, '2568_2_SC101011_02.pdf'),
            $this->makeExamFile(30, 'teacher01', $second, '2568_2_SC101011_01_02.pdf'),
        ]);

        $groups = GradeReportFile::groupExamReportsForDisplay($files);

        $this->assertCount(2, $groups);
        $this->assertSame('แบบรายงานผลการสอบไล่(1)', $groups[0]['label']);
        $this->assertSame('แบบรายงานผลการสอบไล่(2)', $groups[1]['label']);
        $this->assertSame([21, 22], $groups[0]['file_ids']);
        $this->assertSame([30], $groups[1]['file_ids']);
    }
}
