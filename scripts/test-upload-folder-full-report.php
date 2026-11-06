<?php

declare(strict_types=1);

/**
 * ทดสอบอ่านไฟล์ มข.11 ทั้งโฟลเดอร์ ตามเงื่อนไขหน้า /grade-reports/upload
 * แล้วสรุปเป็น Word (landscape: รหัสวิชา ชื่อวิชา อาจารย์ Sec ช่วงคะแนน)
 *
 * Usage:
 *   php scripts/test-upload-folder-full-report.php
 *   php scripts/test-upload-folder-full-report.php --term=1 --year=2568 --folder="D:\...\ทดสอบ เทอม 2-2567"
 */

use App\Services\RegistrarGradePdfParser;
use App\Services\RegistrarPdfParseException;
use App\Support\ImageOnlyPdfMessage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;

require __DIR__.'/../vendor/autoload.php';

Settings::setOutputEscapingEnabled(true);

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$opts = getopt('', ['term::', 'year::', 'folder::', 'label::']);
$term = isset($opts['term']) ? (int) $opts['term'] : 1;
$year = isset($opts['year']) ? (int) $opts['year'] : 2567;
$folder = isset($opts['folder']) && is_string($opts['folder']) && $opts['folder'] !== ''
    ? $opts['folder']
    : 'D:\\ข้อมูลระบบ\\SCiGRADE\\ทดสอบระบบเกรดใหม่-20261022T082348Z-1-001\\ทดสอบระบบเกรดใหม่\\ทดสอบ เทอม 1-2567';

$slug = 'term'.$term.'-'.$year;
$outDir = storage_path('app/test-reports');
@mkdir($outDir, 0777, true);

$jsonPath = $outDir.'/mk11-upload-full-'.$slug.'.json';
$docxPath = $outDir.'/สรุปทดสอบอัปโหลด-มข11-'.$slug.'-ฉบับเต็ม-ชื่อวิชา.docx';
$docxCopy = 'D:\\ข้อมูลระบบ\\SCiGRADE\\สรุปทดสอบอัปโหลด-มข11-'.$slug.'-ฉบับเต็ม-ชื่อวิชา.docx';

if (! is_dir($folder)) {
    fwrite(STDERR, "Folder not found: {$folder}\n");
    exit(1);
}

/** @var RegistrarGradePdfParser $parser */
$parser = app(RegistrarGradePdfParser::class);

$scoreLabels = [
    'score_a' => 'A',
    'score_bb' => 'B+',
    'score_b' => 'B',
    'score_cc' => 'C+',
    'score_c' => 'C',
    'score_dd' => 'D+',
    'score_d' => 'D',
    'score_f' => 'F',
    'score_s' => 'S',
    'score_u' => 'U',
];

$termLabel = static function (int $t, int $y): string {
    $name = match ($t) {
        1 => 'ภาคต้น',
        2 => 'ภาคปลาย',
        3 => 'ภาคฤดูร้อน',
        default => 'ภาค '.$t,
    };

    return $name.' '.$y;
};

$files = glob($folder.DIRECTORY_SEPARATOR.'*.pdf') ?: [];
sort($files, SORT_NATURAL | SORT_FLAG_CASE);

$passed = [];
$failed = [];

foreach ($files as $path) {
    $name = basename($path);
    $row = [
        'file' => $name,
        'ok' => false,
        'upload_ok' => false,
        'subject_code' => null,
        'subject' => null,
        'section' => null,
        'file_term' => null,
        'file_year' => null,
        'teacher' => null,
        'has_manual_ranges' => false,
        'ranges' => [],
        'ranges_text' => '',
        'counts' => [],
        'error_code' => null,
        'reason' => null,
    ];

    try {
        $parsed = $parser->parse($path, $name, $term, $year);
    } catch (RegistrarPdfParseException $e) {
        $msg = $e->getMessage();
        $row['reason'] = $msg;
        if (ImageOnlyPdfMessage::matches($msg)) {
            $row['error_code'] = 'image_only_pdf';
        } elseif (str_contains($msg, 'MANUAL') || str_contains($msg, 'ช่วงคะแนน')) {
            $row['error_code'] = 'missing_manual_ranges';
        } else {
            $row['error_code'] = 'parse_failed';
        }
        $failed[] = $row;
        echo "FAIL | {$name} | {$row['error_code']}\n";
        continue;
    } catch (Throwable $e) {
        $row['error_code'] = 'unexpected_error';
        $row['reason'] = $e->getMessage();
        $failed[] = $row;
        echo "ERR | {$name} | ".$e->getMessage()."\n";
        continue;
    }

    $row['ok'] = true;
    $row['subject_code'] = (string) ($parsed['subject_code'] ?? '');
    $row['subject'] = (string) ($parsed['subject'] ?? '');
    $row['section'] = (int) ($parsed['grade_stds'][0]['sec'] ?? 0);
    $row['file_term'] = (int) ($parsed['term'] ?? $term);
    $row['file_year'] = (int) ($parsed['year'] ?? $year);
    $row['teacher'] = (string) ($parsed['teacher'] ?? '');

    $ranges = [];
    $parts = [];
    foreach ($scoreLabels as $key => $label) {
        $val = trim((string) ($parsed[$key] ?? ''));
        if ($val !== '') {
            $ranges[$key] = $val;
            $parts[] = $label.': '.$val;
        }
    }
    $row['ranges'] = $ranges;
    $row['ranges_text'] = implode(' | ', $parts);
    $row['has_manual_ranges'] = $parser->hasManualScoreRanges($parsed);

    $std = $parsed['grade_stds'][0] ?? [];
    $row['counts'] = [
        'A' => (int) ($std['num_a'] ?? 0),
        'B+' => (int) ($std['num_bb'] ?? 0),
        'B' => (int) ($std['num_b'] ?? 0),
        'C+' => (int) ($std['num_cc'] ?? 0),
        'C' => (int) ($std['num_c'] ?? 0),
        'D+' => (int) ($std['num_dd'] ?? 0),
        'D' => (int) ($std['num_d'] ?? 0),
        'F' => (int) ($std['num_f'] ?? 0),
        'W' => (int) ($std['num_w'] ?? 0),
    ];

    if ($row['file_term'] !== $term || $row['file_year'] !== $year) {
        $row['error_code'] = 'term_year_mismatch';
        $row['reason'] = 'ภาค/ปีในไฟล์ไม่ตรงกับที่เลือกบนหน้าจอ (ไฟล์เป็น '
            .$termLabel($row['file_term'], $row['file_year'])
            .' แต่หน้าจอเลือก '.$termLabel($term, $year).')';
        $failed[] = $row;
        echo "FAIL | {$name} | term_year_mismatch file=".$row['file_term'].'/'.$row['file_year']."\n";
        continue;
    }

    if (! $row['has_manual_ranges']) {
        $row['error_code'] = 'missing_manual_ranges';
        $row['reason'] = $parser->missingManualScoreRangesMessage($name);
        $failed[] = $row;
        echo "FAIL | {$name} | missing_manual_ranges\n";
        continue;
    }

    $row['upload_ok'] = true;
    $passed[] = $row;
    echo "OK | {$name} | {$row['subject_code']} sec={$row['section']}\n";
}

$summary = [
    'tested_at' => now()->toDateTimeString(),
    'folder' => $folder,
    'page' => '/grade-reports/upload?term='.$term.'&year='.$year,
    'term' => $term,
    'year' => $year,
    'term_label' => $termLabel($term, $year),
    'total' => count($files),
    'passed' => count($passed),
    'failed' => count($failed),
    'by_reason' => array_count_values(array_map(static fn ($r) => (string) ($r['error_code'] ?? 'unknown'), $failed)),
    'passed_files' => $passed,
    'failed_files' => $failed,
];

file_put_contents($jsonPath, json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "JSON saved: {$jsonPath}\n";

$reasonLabels = [
    'image_only_pdf' => 'ไฟล์เป็นภาพอย่างเดียว / อ่านข้อความไม่ได้',
    'parse_failed' => 'รูปแบบไฟล์ไม่ใช่ใบ มข.11 หรืออ่านโครงสร้างไม่ได้',
    'missing_manual_ranges' => 'อ่านคอลัมน์ช่วงคะแนน (MANUAL) ไม่ได้',
    'term_year_mismatch' => 'ภาค/ปีการศึกษาในไฟล์ไม่ตรงกับที่เลือก',
    'unexpected_error' => 'ข้อผิดพลาดระบบอื่น ๆ',
];

$phpWord = new PhpWord;
$phpWord->setDefaultFontName('TH Sarabun New');
$phpWord->setDefaultFontSize(14);
$sec = $phpWord->addSection([
    'orientation' => 'landscape',
    'marginTop' => 600,
    'marginBottom' => 600,
    'marginLeft' => 600,
    'marginRight' => 600,
]);
$phpWord->addTableStyle('ReportTable', [
    'borderSize' => 6,
    'borderColor' => '999999',
    'cellMargin' => 40,
]);

$sec->addText('สรุปผลการทดสอบอัปโหลดไฟล์ มข.11 (ฉบับเต็ม)', ['bold' => true, 'size' => 20], ['alignment' => Jc::CENTER]);
$sec->addText('หน้า: /grade-reports/upload?term='.$term.'&year='.$year, ['size' => 13], ['alignment' => Jc::CENTER]);
$sec->addTextBreak(1);

$sec->addText('ส่วนที่ 1: ข้อมูลการทดสอบ', ['bold' => true, 'size' => 16]);
$sec->addText('วันที่ทดสอบ: '.$summary['tested_at']);
$sec->addText('โฟลเดอร์: '.$folder);
$sec->addText('เงื่อนไขหน้าจอ: '.$summary['term_label'].' (term='.$term.', year='.$year.')');
$sec->addText('เกณฑ์ผ่าน: อ่าน PDF ได้ + พบรหัสวิชา/Section + ภาคปีตรง + อ่านคอลัมน์ MANUAL (ช่วงคะแนน) ได้');
$sec->addTextBreak(1);

$sec->addText('ส่วนที่ 2: สรุปภาพรวม', ['bold' => true, 'size' => 16]);
$sec->addText('จำนวนไฟล์ทั้งหมด: '.$summary['total'].' ไฟล์');
$sec->addText('ผ่าน (อัปโหลดได้): '.$summary['passed'].' ไฟล์');
$sec->addText('ไม่ผ่าน: '.$summary['failed'].' ไฟล์');
if ($summary['total'] > 0) {
    $sec->addText('อัตราผ่าน: '.number_format(($summary['passed'] / $summary['total']) * 100, 1).'%');
}
$sec->addTextBreak(1);

$sec->addText('สรุปสาเหตุที่ไม่ผ่าน', ['bold' => true, 'size' => 15]);
if ($summary['by_reason'] === []) {
    $sec->addText('ไม่มีไฟล์ที่ไม่ผ่าน');
} else {
    arsort($summary['by_reason']);
    foreach ($summary['by_reason'] as $code => $count) {
        $sec->addText('- '.($reasonLabels[$code] ?? $code).': '.$count.' ไฟล์');
    }
}
$sec->addTextBreak(1);
$sec->addText('หมายเหตุสาเหตุจริงของ missing_manual_ranges', ['bold' => true, 'size' => 14]);
$sec->addText('เมื่อเปิด PDF อาจเห็นตารางสรุปเกรด/%/จำนวนคน ครบ แต่คอลัมน์ MANUAL ว่าง (ข้อความเป็น <<->>) หรือใช้วิธี T-SCORE — ระบบต้องการช่วงคะแนนตัดเกรดในคอลัมน์ MANUAL โดยเฉพาะ');
$sec->addText('ไฟล์ที่ MANUAL มีค่าจริง แต่ PDF ดึงข้อความแยกบรรทัด/ติด control code — parser ได้รองรับแล้วในรอบนี้');
$sec->addTextBreak(1);

$sec->addText('ส่วนที่ 3: ไฟล์ที่อ่านได้และอัปโหลดได้ (พร้อมช่วงคะแนน)', ['bold' => true, 'size' => 16]);
$sec->addText('จำนวน '.$summary['passed'].' ไฟล์ — แสดงรหัสวิชา ชื่อวิชา อาจารย์ผู้สอน Section และช่วงคะแนน MANUAL');
$sec->addTextBreak(1);

if ($passed === []) {
    $sec->addText('ไม่มี');
} else {
    $table = $sec->addTable('ReportTable');
    $table->addRow();
    $headers = [
        ['ลำดับ', 700],
        ['ชื่อไฟล์', 2200],
        ['รหัสวิชา', 1400],
        ['ชื่อวิชา', 3200],
        ['อาจารย์ผู้สอน', 2400],
        ['Sec', 700],
        ['ช่วงคะแนน (MANUAL)', 4800],
    ];
    foreach ($headers as [$h, $w]) {
        $table->addCell($w)->addText($h, ['bold' => true, 'size' => 11]);
    }
    foreach ($passed as $i => $row) {
        $subject = trim((string) ($row['subject'] ?? ''));
        $teacher = trim((string) ($row['teacher'] ?? ''));
        $table->addRow();
        $table->addCell(700)->addText((string) ($i + 1), ['size' => 10]);
        $table->addCell(2200)->addText((string) $row['file'], ['size' => 10]);
        $table->addCell(1400)->addText((string) $row['subject_code'], ['size' => 10]);
        $table->addCell(3200)->addText($subject !== '' ? $subject : '—', ['size' => 10]);
        $table->addCell(2400)->addText($teacher !== '' ? $teacher : '—', ['size' => 10]);
        $table->addCell(700)->addText(str_pad((string) $row['section'], 2, '0', STR_PAD_LEFT), ['size' => 10]);
        $table->addCell(4800)->addText((string) $row['ranges_text'], ['size' => 9]);
    }
}

$sec->addTextBreak(1);
$sec->addText('ส่วนที่ 4: ไฟล์ที่อัปโหลดไม่ได้ / อ่านไม่ได้', ['bold' => true, 'size' => 16]);
$sec->addText('จำนวน '.$summary['failed'].' ไฟล์ — ระบุชื่อไฟล์และปัญหา');
$sec->addTextBreak(1);

if ($failed === []) {
    $sec->addText('ไม่มี');
} else {
    $table2 = $sec->addTable('ReportTable');
    $table2->addRow();
    $failHeaders = [
        ['ลำดับ', 700],
        ['ชื่อไฟล์', 2200],
        ['รหัส/Sec', 1400],
        ['ชื่อวิชา', 2800],
        ['อาจารย์ผู้สอน', 2200],
        ['กลุ่มปัญหา', 2200],
        ['รายละเอียดปัญหา', 4000],
    ];
    foreach ($failHeaders as [$h, $w]) {
        $table2->addCell($w)->addText($h, ['bold' => true, 'size' => 11]);
    }
    foreach ($failed as $i => $row) {
        $codeSec = '—';
        if (! empty($row['subject_code'])) {
            $codeSec = (string) $row['subject_code'];
            if (! empty($row['section'])) {
                $codeSec .= '-'.str_pad((string) $row['section'], 2, '0', STR_PAD_LEFT);
            }
        }
        $subject = trim((string) ($row['subject'] ?? ''));
        $teacher = trim((string) ($row['teacher'] ?? ''));
        $reason = preg_replace("/\s+/u", ' ', (string) ($row['reason'] ?? '')) ?? '';
        $table2->addRow();
        $table2->addCell(700)->addText((string) ($i + 1), ['size' => 10]);
        $table2->addCell(2200)->addText((string) $row['file'], ['size' => 10]);
        $table2->addCell(1400)->addText($codeSec, ['size' => 10]);
        $table2->addCell(2800)->addText($subject !== '' ? $subject : '—', ['size' => 10]);
        $table2->addCell(2200)->addText($teacher !== '' ? $teacher : '—', ['size' => 10]);
        $table2->addCell(2200)->addText($reasonLabels[$row['error_code']] ?? (string) $row['error_code'], ['size' => 10]);
        $table2->addCell(4000)->addText(mb_substr($reason, 0, 220), ['size' => 9]);
    }
}

$sec->addTextBreak(1);
$sec->addText('ส่วนที่ 5: คำแนะนำ', ['bold' => true, 'size' => 16]);
$sec->addText('1) ไฟล์ที่ผ่าน: ระบบอ่านช่วงคะแนนได้แล้ว แต่ยังควรตรวจความถูกต้องของช่วงคะแนนก่อนยืนยันนำเข้า');
$sec->addText('2) ไฟล์ภาพล้วน: ขอไฟล์ใหม่จาก REG แบบ text PDF หรือทำ OCR ก่อน');
$sec->addText('3) ไฟล์ที่ MANUAL ว่าง: กำหนดช่วงคะแนนใน REG ให้ครบ แล้วดาวน์โหลดใบ มข.11 ใหม่');
$sec->addText('4) ภาค/ปีไม่ตรง: เลือกภาคปีบนหน้าจอให้ตรงกับไฟล์ หรือใช้ไฟล์ของภาคปีที่ต้องการ');

$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($docxPath);
echo "DOCX saved: {$docxPath}\n";

if (is_dir(dirname($docxCopy)) && @copy($docxPath, $docxCopy)) {
    echo "DOCX copied: {$docxCopy}\n";
} else {
    echo "Copy to D: skipped or failed\n";
}

echo 'PASS='.count($passed).' FAIL='.count($failed).' TOTAL='.count($files)."\n";
