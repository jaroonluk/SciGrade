<?php

namespace App\Services;

use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Support\UploadStorage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class GradeReportFileZipService
{
    /**
     * @param  Collection<int, GradeReportFile>  $files
     */
    public function download(Collection $files, string $downloadName): BinaryFileResponse
    {
        if ($files->isEmpty()) {
            throw new RuntimeException('ไม่พบไฟล์แนบตามเงื่อนไข');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'grzip_');
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

        foreach ($files as $file) {
            $disk = UploadStorage::diskFor($file->stored_path);
            if (! $disk->exists($file->stored_path)) {
                continue;
            }

            $contents = $disk->get($file->stored_path);
            if ($contents === null) {
                continue;
            }

            $entry = $this->zipEntryPathFor($file, $usedNames);
            $zip->addFromString($entry, $contents);
        }

        $zip->close();

        if (! is_file($zipPath) || filesize($zipPath) === 0) {
            @unlink($zipPath);
            throw new RuntimeException('ไม่พบไฟล์ในคลังเก็บข้อมูล');
        }

        $safeName = Str::finish(preg_replace('/[^\w.\-ก-๙]+/u', '_', $downloadName) ?: 'grade_files', '.zip');

        return response()->download($zipPath, $safeName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * path ภายใน ZIP — ไฟล์ประเภทเดียวกันอยู่โฟลเดอร์เดียว (ไม่แยกตามรหัสวิชา)
     *
     * @param  array<string, true>  $usedNames
     */
    public function zipEntryPathFor(GradeReportFile $file, array &$usedNames = []): string
    {
        $report = $this->resolveReportFor($file);

        if ($file->isDeptAdminUpload($report) && $report instanceof GradeReport) {
            $typeFolder = 'REG-Admin';
            $filename = $file->deptRegistrarDownloadName($report);
        } elseif ($file->isRegistrar()) {
            $typeFolder = 'REG';
            $filename = $report instanceof GradeReport
                ? $file->deptRegistrarDownloadName($report)
                : $this->safeFileBasename((string) $file->original_name);
        } else {
            $typeFolder = 'exam_report';
            $filename = $this->safeFileBasename((string) $file->original_name);
        }

        $base = $typeFolder.'/'.$filename;

        if (! isset($usedNames[$base])) {
            $usedNames[$base] = true;

            return $base;
        }

        $n = 2;
        $pathInfo = pathinfo($filename);
        $stem = $pathInfo['filename'] ?? 'file';
        $ext = isset($pathInfo['extension']) ? '.'.$pathInfo['extension'] : '';

        do {
            $candidate = $typeFolder.'/'.$stem.'_'.$n.$ext;
            $n++;
        } while (isset($usedNames[$candidate]));

        $usedNames[$candidate] = true;

        return $candidate;
    }

    private function resolveReportFor(GradeReportFile $file): ?GradeReport
    {
        if ($file->relationLoaded('gradeReport') && $file->gradeReport instanceof GradeReport) {
            $report = $file->gradeReport;
        } else {
            $report = $file->gradeReport()->with('gradeStds')->first();
            if ($report instanceof GradeReport) {
                $file->setRelation('gradeReport', $report);
            }
        }

        if ($report instanceof GradeReport && ! $report->relationLoaded('gradeStds')) {
            $report->load('gradeStds');
        }

        return $report instanceof GradeReport ? $report : null;
    }

    private function safeFileBasename(string $name): string
    {
        $base = basename(str_replace('\\', '/', $name));
        $base = preg_replace('/[^\w.\-ก-๙]+/u', '_', $base) ?: 'file.pdf';

        return $base;
    }

    /**
     * @param  Collection<int, GradeReport>  $reports
     * @return Collection<int, GradeReportFile>
     */
    public function collectFiles(Collection $reports, ?string $fileType = null): Collection
    {
        $files = $reports
            ->flatMap(function (GradeReport $report) {
                if ($report->relationLoaded('files')) {
                    return $report->files->each(function (GradeReportFile $file) use ($report): void {
                        if (! $file->relationLoaded('gradeReport')) {
                            $file->setRelation('gradeReport', $report);
                        }
                    });
                }

                return $report->files()->get()->each(function (GradeReportFile $file) use ($report): void {
                    $file->setRelation('gradeReport', $report);
                });
            })
            ->filter(fn ($file) => $file instanceof GradeReportFile)
            ->values();

        if ($fileType === null || $fileType === '' || $fileType === 'all') {
            return $files;
        }

        return $files
            ->filter(function (GradeReportFile $file) use ($fileType) {
                $report = $file->relationLoaded('gradeReport') ? $file->gradeReport : null;

                return match ($fileType) {
                    GradeReportFile::TYPE_EXAM_REPORT => $file->resolvedType() === GradeReportFile::TYPE_EXAM_REPORT,
                    GradeReportFile::TYPE_REGISTRAR => $file->isRegistrar(),
                    'registrar_instructor' => $file->isRegistrar() && $file->isInstructorUpload($report),
                    'registrar_dept' => $file->isDeptAdminUpload($report),
                    default => false,
                };
            })
            ->values();
    }
}
