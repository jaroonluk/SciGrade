<?php

namespace App\Services\ThesisGrade;

use App\Models\ThesisGrade;
use App\Models\ThesisGradeFile;
use App\Models\ThesisGradeStudent;
use App\Support\UploadStorage;
use Illuminate\Http\UploadedFile;

class ThesisGradeAttachmentNameService
{
    public function tsReportName(ThesisGrade $report, int $sequence = 1): string
    {
        $base = $this->tsBase($report);

        if ($sequence <= 1) {
            return $base.'.pdf';
        }

        return $base.'_'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT).'.pdf';
    }

    public function s0LetterName(ThesisGrade $report, ?ThesisGradeStudent $student = null, int $sequence = 1): string
    {
        $base = $this->tsBase($report).'-S0';
        $code = $student ? preg_replace('/[^A-Za-z0-9]/', '', (string) $student->student_code) : '';
        if ($code !== '') {
            $base .= '-'.$code;
        }

        if ($sequence <= 1) {
            return $base.'.pdf';
        }

        return $base.'_'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT).'.pdf';
    }

    public function nextTsReportName(ThesisGrade $report): string
    {
        return $this->tsReportName($report, $this->nextSequence($report, ThesisGradeFile::TYPE_TS_REPORT, $this->tsBase($report)));
    }

    public function nextS0LetterName(ThesisGrade $report, ?ThesisGradeStudent $student = null): string
    {
        $base = pathinfo($this->s0LetterName($report, $student), PATHINFO_FILENAME);

        return $this->s0LetterName(
            $report,
            $student,
            $this->nextSequence($report, ThesisGradeFile::TYPE_S0_LETTER, $base, $student?->student_id),
        );
    }

    public function storeUploadedFile(
        ThesisGrade $report,
        UploadedFile $uploaded,
        string $fileType,
        ?ThesisGradeStudent $student = null,
    ): string {
        $directory = 'thesis-grade-files/'.$report->thesis_grade_id;
        if ($fileType === ThesisGradeFile::TYPE_S0_LETTER) {
            $directory .= '/s0';
        }

        $filename = $fileType === ThesisGradeFile::TYPE_S0_LETTER
            ? $this->nextS0LetterName($report, $student)
            : $this->nextTsReportName($report);

        $disk = UploadStorage::disk();
        while ($disk->exists($directory.'/'.$filename)) {
            $filename = $this->bumpFilename($filename);
        }

        return $uploaded->storeAs($directory, $filename, UploadStorage::diskName());
    }

    public function tsBase(ThesisGrade $report): string
    {
        return sprintf(
            'TS-%s-%s-%d-%d',
            $report->displayCode(),
            $report->paddedSection(),
            (int) $report->term,
            (int) $report->year,
        );
    }

    private function nextSequence(ThesisGrade $report, string $fileType, string $base, ?int $studentId = null): int
    {
        $query = ThesisGradeFile::query()
            ->where('thesis_grade_id', $report->thesis_grade_id)
            ->where('file_type', $fileType);

        if ($studentId !== null) {
            $query->where('student_id', $studentId);
        }

        $existing = $query->pluck('original_name');
        if ($existing->isEmpty()) {
            return 1;
        }

        $max = 0;
        foreach ($existing as $name) {
            if (strcasecmp((string) $name, $base.'.pdf') === 0) {
                $max = max($max, 1);

                continue;
            }

            if (preg_match('/^'.preg_quote($base, '/').'_(\d+)\.pdf$/i', (string) $name, $match)) {
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

        return $match[1].'_'.str_pad((string) ((int) $match[2] + 1), 2, '0', STR_PAD_LEFT).'.pdf';
    }
}
