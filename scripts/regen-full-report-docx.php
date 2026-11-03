<?php

declare(strict_types=1);

/**
 * สร้าง Word ใหม่จาก JSON ผลการทดสอบที่มีอยู่แล้ว (ไม่ต้อง parse PDF ซ้ำ)
 */

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Settings::setOutputEscapingEnabled(true);

$outDir = storage_path('app/test-reports');
$jsonPath = $outDir.'/mk11-upload-full-term1-2567.json';
$docxPath = $outDir.'/สรุปทดสอบอัปโหลด-มข11-เทอม1-2567-ฉบับเต็ม.docx';
$docxCopy = 'D:\\ข้อมูลระบบ\\SCiGRADE\\สรุปทดสอบอัปโหลด-มข11-เทอม1-2567-ฉบับเต็ม-มีอาจารย์.docx';
$docxCopyAlt = 'D:\\ข้อมูลระบบ\\SCiGRADE\\สรุปทดสอบอัปโหลด-มข11-เทอม1-2567-ฉบับเต็ม-ชื่อวิชา.docx';

$summary = json_decode((string) file_get_contents($jsonPath), true);
if (! is_array($summary)) {
    fwrite(STDERR, "JSON not found/invalid: {$jsonPath}\n");
    exit(1);
}

$passed = $summary['passed_files'] ?? [];
$failed = $summary['failed_files'] ?? [];
$term = (int) ($summary['term'] ?? 1);
$year = (int) ($summary['year'] ?? 2567);

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
$sec->addText('วันที่ทดสอบ: '.($summary['tested_at'] ?? ''));
$sec->addText('โฟลเดอร์: '.($summary['folder'] ?? ''));
$sec->addText('เงื่อนไขหน้าจอ: '.($summary['term_label'] ?? '').' (term='.$term.', year='.$year.')');
$sec->addText('เกณฑ์ผ่าน: อ่าน PDF ได้ + พบรหัสวิชา/Section + ภาคปีตรง + อ่านคอลัมน์ MANUAL (ช่วงคะแนน) ได้');
$sec->addTextBreak(1);

$sec->addText('ส่วนที่ 2: สรุปภาพรวม', ['bold' => true, 'size' => 16]);
$sec->addText('จำนวนไฟล์ทั้งหมด: '.($summary['total'] ?? 0).' ไฟล์');
$sec->addText('ผ่าน (อัปโหลดได้): '.($summary['passed'] ?? 0).' ไฟล์');
$sec->addText('ไม่ผ่าน: '.($summary['failed'] ?? 0).' ไฟล์');
if (($summary['total'] ?? 0) > 0) {
    $sec->addText('อัตราผ่าน: '.number_format((($summary['passed'] ?? 0) / $summary['total']) * 100, 1).'%');
}
$sec->addTextBreak(1);

$sec->addText('สรุปสาเหตุที่ไม่ผ่าน', ['bold' => true, 'size' => 15]);
$byReason = $summary['by_reason'] ?? [];
if ($byReason === []) {
    $sec->addText('ไม่มีไฟล์ที่ไม่ผ่าน');
} else {
    arsort($byReason);
    foreach ($byReason as $code => $count) {
        $sec->addText('- '.($reasonLabels[$code] ?? $code).': '.$count.' ไฟล์');
    }
}
$sec->addTextBreak(1);

$sec->addText('ส่วนที่ 3: ไฟล์ที่อ่านได้และอัปโหลดได้ (พร้อมช่วงคะแนน)', ['bold' => true, 'size' => 16]);
$sec->addText('จำนวน '.($summary['passed'] ?? 0).' ไฟล์ — แสดงรหัสวิชา ชื่อวิชา อาจารย์ผู้สอน Section และช่วงคะแนน MANUAL');
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
        $table->addCell(2200)->addText((string) ($row['file'] ?? ''), ['size' => 10]);
        $table->addCell(1400)->addText((string) ($row['subject_code'] ?? ''), ['size' => 10]);
        $table->addCell(3200)->addText($subject !== '' ? $subject : '—', ['size' => 10]);
        $table->addCell(2400)->addText($teacher !== '' ? $teacher : '—', ['size' => 10]);
        $table->addCell(700)->addText(str_pad((string) ($row['section'] ?? 0), 2, '0', STR_PAD_LEFT), ['size' => 10]);
        $table->addCell(4800)->addText((string) ($row['ranges_text'] ?? ''), ['size' => 9]);
    }
}

$sec->addTextBreak(1);
$sec->addText('ส่วนที่ 4: ไฟล์ที่อัปโหลดไม่ได้ / อ่านไม่ได้', ['bold' => true, 'size' => 16]);
$sec->addText('จำนวน '.($summary['failed'] ?? 0).' ไฟล์ — ระบุชื่อไฟล์และปัญหา');
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
        $table2->addCell(2200)->addText((string) ($row['file'] ?? ''), ['size' => 10]);
        $table2->addCell(1400)->addText($codeSec, ['size' => 10]);
        $table2->addCell(2800)->addText($subject !== '' ? $subject : '—', ['size' => 10]);
        $table2->addCell(2200)->addText($teacher !== '' ? $teacher : '—', ['size' => 10]);
        $table2->addCell(2200)->addText($reasonLabels[$row['error_code'] ?? ''] ?? (string) ($row['error_code'] ?? ''), ['size' => 10]);
        $table2->addCell(4000)->addText(mb_substr($reason, 0, 200), ['size' => 9]);
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

foreach ([$docxCopy, $docxCopyAlt] as $dest) {
    if (is_dir(dirname($dest)) && @copy($docxPath, $dest)) {
        echo "DOCX copied: {$dest}\n";
    } else {
        echo "Copy skipped/failed: {$dest}\n";
    }
}
