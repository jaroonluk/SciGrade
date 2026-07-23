<?php

namespace App\Services;

use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Support\UploadStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class GradeReportAttachmentNameService
{
    /**
     * รูปแบบ: {ปีการศึกษา}_{ภาคการศึกษา}_{รหัสวิชา}_{section}.pdf
     * ไฟล์ถัดไป: ..._02.pdf, ..._03.pdf
     */
    public function generateDisplayName(GradeReport $report): string
    {
        $base = $this->baseName($report);
        $sequence = $this->nextSequence($report->grade_id, $base);

        if ($sequence === 1) {
            return $base.'.pdf';
        }

        return $base.'_'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT).'.pdf';
    }

    public function storeUploadedFile(GradeReport $report, UploadedFile $uploaded): string
    {
        $directory = 'grade-report-files/'.$report->grade_id;
        $filename = $this->generateDisplayName($report);
        $disk = UploadStorage::disk();

        while ($disk->exists($directory.'/'.$filename)) {
            $filename = $this->bumpFilename($filename);
        }

        return $uploaded->storeAs($directory, $filename, UploadStorage::diskName());
    }

    private function baseName(GradeReport $report): string
    {
        $report->loadMissing('gradeStds');

        $year = preg_replace('/\D/', '', (string) $report->year) ?: '0000';
        $term = preg_replace('/\D/', '', (string) $report->term) ?: '0';
        $subjectCode = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', (string) $report->subject_code) ?: 'SUBJECT');

        $sectionValue = $report->gradeStds->sortBy(fn ($row) => (int) $row->sec)->first()?->sec ?? 0;
        $section = str_pad((string) (int) $sectionValue, 2, '0', STR_PAD_LEFT);

        return "{$year}_{$term}_{$subjectCode}_{$section}";
    }

    private function nextSequence(int $gradeId, string $base): int
    {
        $existing = GradeReportFile::query()
            ->where('grade_id', $gradeId)
            ->pluck('original_name');

        if ($existing->isEmpty()) {
            return 1;
        }

        $max = 0;
        foreach ($existing as $name) {
            if ($name === $base.'.pdf') {
                $max = max($max, 1);
                continue;
            }

            if (preg_match('/^'.preg_quote($base, '/').'_(\d+)\.pdf$/i', $name, $match)) {
                $max = max($max, (int) $match[1]);
            }
        }

        return $max + 1;
    }

    private function bumpFilename(string $filename): string
    {
        if (! preg_match('/^(.*)_(\d+)\.pdf$/i', $filename, $match)) {
            return preg_replace('/\.pdf$/i', '_02.pdf', $filename) ?? $filename.'_02.pdf';
        }

        $next = (int) $match[2] + 1;

        return $match[1].'_'.str_pad((string) $next, 2, '0', STR_PAD_LEFT).'.pdf';
    }
}
