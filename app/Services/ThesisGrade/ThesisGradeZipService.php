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
     * ชุดเอกสารสมบูรณ์: ใบจากสาขา (ถ้ามี) ไม่เช่นนั้นใบอาจารย์ + หนังสือ S=0
     *
     * @param  Collection<int, ThesisGrade>  $reports
     */
    public function downloadCompleteReports(Collection $reports, string $downloadName): BinaryFileResponse
    {
        $keyed = $reports->keyBy('thesis_grade_id');
        $files = $keyed->flatMap(fn (ThesisGrade $report) => $report->completePacketFiles());

        return $this->downloadFiles($files, $keyed, $downloadName);
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
            $entry = $this->zipEntryNameFor($file, $report, $usedNames);
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

    /**
     * โครงสร้าง ZIP: TS/ · S0/ · สาขา/ (ชั้นเดียวต่อประเภท)
     * ชื่อไฟล์: TS-รหัสวิชา-section-ภาค-ปี.pdf (ชนกันในโฟลเดอร์เดียวกันเติม _1, _2, …)
     *
     * @param  array<string, true>  $usedNames
     */
    public function zipEntryNameFor(ThesisGradeFile $file, ?ThesisGrade $report, array &$usedNames = []): string
    {
        return $this->uniqueName($this->entryName($file, $report), $usedNames);
    }

    private function entryName(ThesisGradeFile $file, ?ThesisGrade $report): string
    {
        $folder = $this->typeFolder($file);
        $filename = $report
            ? $report->tsFilename()
            : $this->fallbackFilename($file);

        return $folder.'/'.$filename;
    }

    private function typeFolder(ThesisGradeFile $file): string
    {
        if ($file->isS0Letter()) {
            return 'S0';
        }

        if ($file->isChairSigned()) {
            return 'สาขา';
        }

        return 'TS';
    }

    private function fallbackFilename(ThesisGradeFile $file): string
    {
        $name = $file->original_name !== '' ? $file->original_name : basename((string) $file->stored_path);
        $name = str_replace(['\\', '/'], '-', $name);

        return $name !== '' ? $name : 'file.pdf';
    }

    /**
     * @param  array<string, true>  $usedNames
     */
    private function uniqueName(string $name, array &$usedNames): string
    {
        $candidate = $name;
        $i = 1;
        while (isset($usedNames[$candidate])) {
            $candidate = preg_replace('/(\.[^.]+)$/', '_'.$i.'$1', $name) ?: $name.'_'.$i;
            $i++;
        }
        $usedNames[$candidate] = true;

        return $candidate;
    }
}
