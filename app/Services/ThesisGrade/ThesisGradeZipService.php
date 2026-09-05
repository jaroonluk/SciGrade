<?php

namespace App\Services\ThesisGrade;

use App\Models\ThesisGrade;
use App\Models\ThesisGradeFile;
use App\Support\UploadStorage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ThesisGradeZipService
{
    /**
     * @param  Collection<int, ThesisGrade>  $reports
     */
    public function downloadReports(Collection $reports, string $downloadName): BinaryFileResponse
    {
        $files = $reports->flatMap(fn (ThesisGrade $report) => $report->files);

        return $this->downloadFiles($files, $reports->keyBy('thesis_grade_id'), $downloadName);
    }

    /**
     * @param  Collection<int, ThesisGradeFile>  $files
     * @param  Collection<int, ThesisGrade>|null  $reports
     */
    public function downloadFiles(Collection $files, ?Collection $reports, string $downloadName): BinaryFileResponse
    {
        if ($files->isEmpty()) {
            throw new RuntimeException('ไม่พบไฟล์แนบตามเงื่อนไข');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'tszip_');
        if ($tmp === false) {
            throw new RuntimeException('ไม่สามารถสร้างไฟล์ชั่วคราวได้');
        }

        $zipPath = $tmp.'.zip';
        @unlink($tmp);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('ไม่สามารถสร้างไฟล์ ZIP ได้');
        }

        $usedNames = [];
        $added = 0;

        foreach ($files as $file) {
            $disk = UploadStorage::diskFor($file->stored_path);
            if (! $disk->exists($file->stored_path)) {
                continue;
            }

            $contents = $disk->get($file->stored_path);
            if ($contents === null) {
                continue;
            }

            $report = $reports?->get($file->thesis_grade_id) ?? $file->report;
            $entry = $this->uniqueName($this->entryName($file, $report), $usedNames);
            $zip->addFromString($entry, $contents);
            $added++;
        }

        $zip->close();

        if ($added === 0 || ! is_file($zipPath) || filesize($zipPath) === 0) {
            @unlink($zipPath);
            throw new RuntimeException('ไม่พบไฟล์ในคลังเก็บข้อมูล');
        }

        $safeName = Str::finish(preg_replace('/[^\w.\-ก-๙]+/u', '_', $downloadName) ?: 'thesis_files', '.zip');

        return response()->download($zipPath, $safeName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    private function entryName(ThesisGradeFile $file, ?ThesisGrade $report): string
    {
        $folder = $file->isS0Letter() ? 'S0' : 'TS';
        $name = $file->original_name !== '' ? $file->original_name : basename($file->stored_path);

        if ($report) {
            $prefix = $report->displayCode().'-'.$report->paddedSection();
            if (! str_starts_with(strtoupper($name), strtoupper($prefix)) && ! str_starts_with(strtoupper($name), 'TS-')) {
                $name = $prefix.'-'.$name;
            }
        }

        return $folder.'/'.$name;
    }

    /**
     * @param  array<string, true>  $usedNames
     */
    private function uniqueName(string $name, array &$usedNames): string
    {
        $candidate = $name;
        $i = 2;
        while (isset($usedNames[$candidate])) {
            $candidate = preg_replace('/(\.[^.]+)$/', '_'.$i.'$1', $name) ?: $name.'_'.$i;
            $i++;
        }
        $usedNames[$candidate] = true;

        return $candidate;
    }
}
