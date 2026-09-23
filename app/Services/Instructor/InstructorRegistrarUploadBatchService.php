<?php

namespace App\Services\Instructor;

use App\Services\RegistrarGradePdfParser;
use App\Services\RegistrarPdfParseException;
use App\Support\UploadStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class InstructorRegistrarUploadBatchService
{
    public const MAX_FILES = 20;

    public function __construct(
        private readonly RegistrarGradePdfParser $pdfParser,
        private readonly InstructorPendingRegistrarService $pendingRegistrar,
    ) {}

    /**
     * อ่านหลายไฟล์ PDF ใบส่งผล — ต้องเป็นวิชาเดียวกัน
     * ไฟล์ซ้ำ (เนื้อหาหรือกลุ่มเรียนเดียวกัน) อ่านเพียงไฟล์แรก
     *
     * @param  list<UploadedFile>  $files
     * @return array{
     *     merged: array<string, mixed>,
     *     accepted: list<array{name: string, section: int|null, subject_code: string}>,
     *     duplicates: list<array{name: string, duplicate_of: string, reason: string}>,
     * }
     *
     * @throws RegistrarPdfParseException
     * @throws \InvalidArgumentException
     */
    public function process(array $files, int $term, int $year, ?int $ownerId): array
    {
        if ($files === []) {
            throw new \InvalidArgumentException('กรุณาเลือกไฟล์ PDF อย่างน้อย 1 ไฟล์');
        }

        if (count($files) > self::MAX_FILES) {
            throw new \InvalidArgumentException('อัปโหลดได้สูงสุด '.self::MAX_FILES.' ไฟล์ต่อครั้ง');
        }

        /** @var list<array{file: UploadedFile, parsed: array<string, mixed>, hash: string, name: string, section: int, subject: string}> $parsedRows */
        $parsedRows = [];
        $subjectCodes = [];

        foreach ($files as $uploaded) {
            $name = $uploaded->getClientOriginalName();
            $tmpPath = $uploaded->getRealPath() ?: $uploaded->getPathname();

            if (! is_string($tmpPath) || $tmpPath === '' || ! is_readable($tmpPath)) {
                throw new RegistrarPdfParseException($this->pdfParser->invalidFormatMessage().' (ไฟล์: '.$name.')');
            }

            try {
                $parsed = $this->pdfParser->parse($tmpPath, $name, $term, $year);
            } catch (RegistrarPdfParseException $e) {
                $message = $e->getMessage();
                if (! str_contains($message, $name)) {
                    $message .= ' (ไฟล์: '.$name.')';
                }
                throw new RegistrarPdfParseException($message, previous: $e);
            }

            $subject = Str::upper(trim((string) ($parsed['subject_code'] ?? '')));
            if ($subject === '') {
                throw new RegistrarPdfParseException(
                    'ไม่พบรหัสวิชาในไฟล์ «'.$name.'» — ตรวจสอบว่าเป็นใบส่งผลการศึกษาจากสำนักทะเบียน'
                );
            }

            $section = (int) ($parsed['grade_stds'][0]['sec'] ?? 0);
            $hash = hash_file('sha256', $tmpPath) ?: (uniqid('file_', true));

            $parsedRows[] = [
                'file' => $uploaded,
                'parsed' => $parsed,
                'hash' => $hash,
                'name' => $name,
                'section' => $section,
                'subject' => $subject,
            ];
            $subjectCodes[$subject] = true;
        }

        if (count($subjectCodes) > 1) {
            $lines = [];
            foreach ($parsedRows as $row) {
                $lines[] = '• '.$row['name'].' → '.$row['subject'];
            }
            throw new \InvalidArgumentException(
                "ไฟล์ที่เลือกไม่ใช่วิชาเดียวกัน กรุณาอัปโหลดเฉพาะไฟล์ของรายวิชาเดียว:\n".implode("\n", $lines)
            );
        }

        $accepted = [];
        $duplicates = [];
        $seenHashes = [];
        $seenSections = [];

        foreach ($parsedRows as $row) {
            if (isset($seenHashes[$row['hash']])) {
                $duplicates[] = [
                    'name' => $row['name'],
                    'duplicate_of' => $seenHashes[$row['hash']],
                    'reason' => 'เนื้อหาไฟล์ซ้ำกับ «'.$seenHashes[$row['hash']].'»',
                ];
                continue;
            }

            if ($row['section'] > 0 && isset($seenSections[$row['section']])) {
                $duplicates[] = [
                    'name' => $row['name'],
                    'duplicate_of' => $seenSections[$row['section']],
                    'reason' => 'กลุ่มเรียน Sec '.str_pad((string) $row['section'], 2, '0', STR_PAD_LEFT)
                        .' ซ้ำกับ «'.$seenSections[$row['section']].'»',
                ];
                continue;
            }

            $seenHashes[$row['hash']] = $row['name'];
            if ($row['section'] > 0) {
                $seenSections[$row['section']] = $row['name'];
            }
            $accepted[] = $row;
        }

        if ($accepted === []) {
            throw new \InvalidArgumentException('ไฟล์ทั้งหมดซ้ำกัน กรุณาเลือกไฟล์ที่แตกต่างกัน');
        }

        $this->pendingRegistrar->forgetAll();

        $merged = null;
        $gradeStds = [];
        $acceptedMeta = [];

        foreach ($accepted as $row) {
            $parsed = $row['parsed'];
            $section = $row['section'] > 0 ? $row['section'] : 1;
            $canonicalName = $this->pdfParser->canonicalFilename(
                (string) ($parsed['subject_code'] ?? 'SUBJECT'),
                $section,
            );

            $path = $row['file']->store('grade-uploads/'.$ownerId, UploadStorage::diskName());

            $this->pendingRegistrar->remember([
                'path' => $path,
                'name' => $canonicalName,
                'term' => (int) ($parsed['term'] ?? $term),
                'year' => (int) ($parsed['year'] ?? $year),
                'subject_code' => (string) ($parsed['subject_code'] ?? ''),
                'section' => $row['section'] > 0 ? $row['section'] : null,
                'owner' => $ownerId,
            ]);

            if ($merged === null) {
                $merged = $parsed;
            }

            foreach ($parsed['grade_stds'] ?? [] as $std) {
                $gradeStds[] = $std;
            }

            $acceptedMeta[] = [
                'name' => $row['name'],
                'section' => $row['section'] > 0 ? $row['section'] : null,
                'subject_code' => $row['subject'],
            ];
        }

        usort($gradeStds, fn ($a, $b) => ((int) ($a['sec'] ?? 0)) <=> ((int) ($b['sec'] ?? 0)));
        $merged['grade_stds'] = $gradeStds;

        return [
            'merged' => $merged,
            'accepted' => $acceptedMeta,
            'duplicates' => $duplicates,
        ];
    }
}
