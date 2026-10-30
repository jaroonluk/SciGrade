<?php

/**
 * ทดสอบอ่านไฟล์ มข.11 ทั้งโฟลเดอร์ ตามเงื่อนไขหน้า /grade-reports/upload
 * แล้วสรุปผลเป็นไฟล์ Word (.docx)
 *
 * Usage:
 *   php scripts/test-registrar-upload-folder.php
 */

declare(strict_types=1);

use App\Services\Instructor\InstructorRegistrarUploadBatchService;
use App\Services\RegistrarGradePdfParser;
use App\Services\RegistrarPdfParseException;
use App\Support\ImageOnlyPdfMessage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$folder = 'D:\\ข้อมูลระบบ\\SCiGRADE\\ทดสอบระบบเกรดใหม่-20261022T082348Z-1-001\\ทดสอบระบบเกรดใหม่\\ทดสอบ เทอม 1-2567';
$term = 1;
$year = 2567;
$outDir = storage_path('app/test-reports');
@mkdir($outDir, 0777, true);
$jsonPath = $outDir.'/mk11-upload-test-term1-2567.json';
$docxPath = $outDir.'/สรุปทดสอบอัปโหลด-มข11-เทอม1-2567.docx';

if (! is_dir($folder)) {
    fwrite(STDERR, "Folder not found: {$folder}\n");
    exit(1);
}

/** @var RegistrarGradePdfParser $parser */
$parser = app(RegistrarGradePdfParser::class);
/** @var InstructorRegistrarUploadBatchService $batch */
$batch = app(InstructorRegistrarUploadBatchService::class);

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS)
);

$pdfs = [];
foreach ($files as $file) {
    if (! $file->isFile()) {
        continue;
    }
    if (strtolower($file->getExtension()) !== 'pdf') {
        continue;
    }
    $pdfs[] = $file->getPathname();
}
natcasesort($pdfs);
$pdfs = array_values($pdfs);

$total = count($pdfs);
echo "Found {$total} PDF files\n";
echo "Target term/year: {$term}/{$year}\n";

$passed = [];
$failed = [];
$byReason = [];

$termLabel = static function (int $t, int $y) use ($batch): string {
    return $batch->termYearLabel($t, $y);
};

foreach ($pdfs as $index => $path) {
    $name = basename($path);
    $n = $index + 1;
    if ($n === 1 || $n % 25 === 0 || $n === $total) {
        echo "[{$n}/{$total}] {$name}\n";
    }

    $row = [
        'file' => $name,
        'path' => $path,
        'ok' => false,
        'reason' => null,
        'error_code' => null,
        'subject_code' => null,
        'subject' => null,
        'section' => null,
        'file_term' => null,
        'file_year' => null,
    ];

    try {
        if (! is_readable($path)) {
            throw new RegistrarPdfParseException('อ่านไฟล์ไม่สำเร็จ (unreadable)');
        }

        $parsed = $parser->parse($path, $name, $term, $year);
        $subject = strtoupper(trim((string) ($parsed['subject_code'] ?? '')));
        $section = (int) ($parsed['grade_stds'][0]['sec'] ?? 0);
        $fileTerm = (int) ($parsed['term'] ?? $term);
        $fileYear = (int) ($parsed['year'] ?? $year);
        $subjectName = trim((string) ($parsed['subject'] ?? ''));

        $row['subject_code'] = $subject !== '' ? $subject : null;
        $row['subject'] = $subjectName !== '' ? $subjectName : null;
        $row['section'] = $section > 0 ? $section : null;
        $row['file_term'] = $fileTerm;
        $row['file_year'] = $fileYear;

        if ($subject === '') {
            $row['error_code'] = 'missing_subject_code';
            $row['reason'] = 'ไม่พบรหัสวิชาในไฟล์ PDF';
            $failed[] = $row;
            $byReason[$row['error_code']][] = $name;
            continue;
        }

        if ($section <= 0) {
            $row['error_code'] = 'missing_section';
            $row['reason'] = 'ไม่พบกลุ่มเรียน (Section) ในไฟล์ PDF';
            $failed[] = $row;
            $byReason[$row['error_code']][] = $name;
            continue;
        }

        if ($fileTerm !== $term || $fileYear !== $year) {
            $row['error_code'] = 'term_year_mismatch';
            $row['reason'] = 'ภาค/ปีในไฟล์ไม่ตรงกับที่เลือกบนหน้าจอ (เลือก: '
                .$termLabel($term, $year).' / ในไฟล์: '.$termLabel($fileTerm, $fileYear).')';
            $failed[] = $row;
            $byReason[$row['error_code']][] = $name;
            continue;
        }

        $row['ok'] = true;
        $row['reason'] = null;
        $row['error_code'] = null;
        $passed[] = $row;
    } catch (RegistrarPdfParseException $e) {
        $message = trim($e->getMessage());
        $code = ImageOnlyPdfMessage::matches($message) ? 'image_only_pdf' : 'parse_failed';
        $row['error_code'] = $code;
        $row['reason'] = $message !== '' ? $message : 'อ่านไฟล์ PDF ไม่สำเร็จ';
        $failed[] = $row;
        $byReason[$code][] = $name;
    } catch (Throwable $e) {
        $row['error_code'] = 'unexpected_error';
        $row['reason'] = 'ข้อผิดพลาดระบบ: '.$e->getMessage();
        $failed[] = $row;
        $byReason['unexpected_error'][] = $name;
    }
}

$summary = [
    'tested_at' => now('Asia/Bangkok')->format('Y-m-d H:i:s'),
    'folder' => $folder,
    'term' => $term,
    'year' => $year,
    'term_label' => $termLabel($term, $year),
    'total' => $total,
    'passed' => count($passed),
    'failed' => count($failed),
    'by_reason' => array_map('count', $byReason),
    'passed_files' => $passed,
    'failed_files' => $failed,
];

file_put_contents($jsonPath, json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "JSON saved: {$jsonPath}\n";

$reasonLabels = [
    'image_only_pdf' => 'ไฟล์เป็นภาพอย่างเดียว / อ่านข้อความไม่ได้',
    'parse_failed' => 'รูปแบบไฟล์ไม่ใช่ใบ มข.11 หรืออ่านไม่ได้',
    'term_year_mismatch' => 'ภาค/ปีการศึกษาในไฟล์ไม่ตรงกับที่เลือก',
    'missing_subject_code' => 'ไม่พบรหัสวิชาในไฟล์',
    'missing_section' => 'ไม่พบ Section ในไฟล์',
    'unexpected_error' => 'ข้อผิดพลาดระบบอื่น ๆ',
];

$phpWord = new PhpWord;
$phpWord->setDefaultFontName('TH Sarabun New');
$phpWord->setDefaultFontSize(14);

$sectionStyle = ['marginTop' => 800, 'marginBottom' => 800, 'marginLeft' => 900, 'marginRight' => 900];
$sec = $phpWord->addSection($sectionStyle);

$sec->addText('สรุปผลการทดสอบอัปโหลดไฟล์ มข.11', ['bold' => true, 'size' => 20], ['alignment' => Jc::CENTER]);
$sec->addText('หน้า: /grade-reports/upload', ['size' => 14], ['alignment' => Jc::CENTER]);
$sec->addTextBreak(1);

$sec->addText('ข้อมูลการทดสอบ', ['bold' => true, 'size' => 16]);
$sec->addText('วันที่ทดสอบ: '.$summary['tested_at']);
$sec->addText('โฟลเดอร์: '.$folder);
$sec->addText('เงื่อนไขหน้าจอ: '.$summary['term_label'].' (term='.$term.', year='.$year.')');
$sec->addText('เกณฑ์ผ่าน: อ่านเนื้อหา PDF ได้, พบรหัสวิชา+Section, ภาค/ปีในไฟล์ตรงกับที่เลือก');
$sec->addTextBreak(1);

$sec->addText('สรุปภาพรวม', ['bold' => true, 'size' => 16]);
$sec->addText('จำนวนไฟล์ทั้งหมด: '.$total.' ไฟล์');
$sec->addText('ผ่าน: '.count($passed).' ไฟล์');
$sec->addText('ไม่ผ่าน: '.count($failed).' ไฟล์');
if ($total > 0) {
    $sec->addText('อัตราผ่าน: '.number_format((count($passed) / $total) * 100, 1).'%');
}
$sec->addTextBreak(1);

$sec->addText('สรุปสาเหตุที่ไม่ผ่าน', ['bold' => true, 'size' => 16]);
if ($byReason === []) {
    $sec->addText('ไม่มีไฟล์ที่ไม่ผ่าน');
} else {
    arsort($byReason);
    foreach ($byReason as $code => $names) {
        $label = $reasonLabels[$code] ?? $code;
        $sec->addText('- '.$label.': '.count($names).' ไฟล์', ['bold' => true]);
    }
}
$sec->addTextBreak(1);

$tableStyle = ['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 50];
$phpWord->addTableStyle('ResultTable', $tableStyle);

$sec->addText('รายละเอียดไฟล์ที่ไม่ผ่าน', ['bold' => true, 'size' => 16]);
if ($failed === []) {
    $sec->addText('ไม่มี');
} else {
    $table = $sec->addTable('ResultTable');
    $table->addRow();
    foreach (['ลำดับ', 'ชื่อไฟล์', 'รหัส/Sec จากไฟล์', 'ภาคปีในไฟล์', 'สาเหตุ'] as $h) {
        $table->addCell(2000)->addText($h, ['bold' => true, 'size' => 12]);
    }
    foreach ($failed as $i => $row) {
        $table->addRow();
        $codeSec = ($row['subject_code'] ?? '—')
            .($row['section'] ? '-'.str_pad((string) $row['section'], 2, '0', STR_PAD_LEFT) : '');
        $ty = ($row['file_term'] || $row['file_year'])
            ? $termLabel((int) ($row['file_term'] ?? 0), (int) ($row['file_year'] ?? 0))
            : '—';
        $table->addCell(800)->addText((string) ($i + 1), ['size' => 11]);
        $table->addCell(2800)->addText((string) $row['file'], ['size' => 11]);
        $table->addCell(1800)->addText($codeSec, ['size' => 11]);
        $table->addCell(2200)->addText($ty, ['size' => 11]);
        $table->addCell(4200)->addText((string) ($row['reason'] ?? ''), ['size' => 11]);
    }
}

$sec->addTextBreak(1);
$sec->addText('รายละเอียดไฟล์ที่ผ่าน', ['bold' => true, 'size' => 16]);
if ($passed === []) {
    $sec->addText('ไม่มี');
} else {
    $table = $sec->addTable('ResultTable');
    $table->addRow();
    foreach (['ลำดับ', 'ชื่อไฟล์', 'รหัสวิชา', 'Section', 'ชื่อวิชา'] as $h) {
        $table->addCell(2200)->addText($h, ['bold' => true, 'size' => 12]);
    }
    foreach ($passed as $i => $row) {
        $table->addRow();
        $table->addCell(800)->addText((string) ($i + 1), ['size' => 11]);
        $table->addCell(2800)->addText((string) $row['file'], ['size' => 11]);
        $table->addCell(1600)->addText((string) ($row['subject_code'] ?? ''), ['size' => 11]);
        $table->addCell(1000)->addText(str_pad((string) ($row['section'] ?? 0), 2, '0', STR_PAD_LEFT), ['size' => 11]);
        $table->addCell(3600)->addText((string) ($row['subject'] ?? ''), ['size' => 11]);
    }
}

$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($docxPath);

echo "DOCX saved: {$docxPath}\n";
echo "PASS=".count($passed)." FAIL=".count($failed)." TOTAL={$total}\n";
