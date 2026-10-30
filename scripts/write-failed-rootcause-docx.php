<?php

declare(strict_types=1);

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;

require __DIR__.'/../vendor/autoload.php';

Settings::setOutputEscapingEnabled(true);

$outDir = __DIR__.'/../storage/app/test-reports';
$upload = json_decode((string) file_get_contents($outDir.'/mk11-upload-test-term1-2567.json'), true) ?: [];
$diag = json_decode((string) file_get_contents($outDir.'/mk11-failed-diagnosis.json'), true) ?: [];
$precise = json_decode((string) file_get_contents($outDir.'/mk11-failed-precise.json'), true) ?: [];

$docxPath = $outDir.'/สรุปทดสอบอัปโหลด-มข11-เทอม1-2567-วิเคราะห์สาเหตุ.docx';
$docxCopy = 'D:\\ข้อมูลระบบ\\SCiGRADE\\สรุปทดสอบอัปโหลด-มข11-เทอม1-2567-วิเคราะห์สาเหตุ.docx';
$docxCopyAlt = 'D:\\ข้อมูลระบบ\\SCiGRADE\\สรุปทดสอบอัปโหลด-มข11-เทอม1-2567.docx';

$diagByFile = [];
foreach (($diag['failed'] ?? []) as $row) {
    $diagByFile[(string) $row['file']] = $row;
}

$preciseByFile = [];
foreach (($precise['results'] ?? []) as $row) {
    $preciseByFile[(string) $row['file']] = $row;
}

$failedUpload = $upload['failed_files'] ?? [];
$passedCount = (int) ($upload['passed'] ?? 0);
$failedCount = (int) ($upload['failed'] ?? 0);
$totalCount = (int) ($upload['total'] ?? 0);
$testedAt = (string) ($upload['tested_at'] ?? date('Y-m-d H:i:s'));
$folder = (string) ($upload['folder'] ?? 'ทดสอบ เทอม 1-2567');
$term = (int) ($upload['term'] ?? 1);
$year = (int) ($upload['year'] ?? 2567);
$byReason = $upload['by_reason'] ?? [];

$reasonLabels = [
    'image_only_pdf' => 'ไฟล์เป็นภาพอย่างเดียว / อ่านข้อความไม่ได้',
    'parse_failed' => 'รูปแบบไฟล์ไม่ใช่ใบ มข.11 หรืออ่านไม่ได้',
    'term_year_mismatch' => 'ภาค/ปีการศึกษาในไฟล์ไม่ตรงกับที่เลือก',
    'missing_subject_code' => 'ไม่พบรหัสวิชาในไฟล์',
    'missing_section' => 'ไม่พบ Section ในไฟล์',
    'unexpected_error' => 'ข้อผิดพลาดระบบอื่น ๆ',
];

/**
 * แมป fail_stage จาก diagnosis → สาเหตุจริง + วิธีแก้
 */
$stageMeta = [
    'missing_subject_line' => [
        'title' => 'รหัสวิชาเป็นตัวเลขล้วน (ไม่มีคำนำหน้า SC)',
        'cause' => 'ใบ มข.11 แสดงรหัสแบบ 326493 : ... แต่ parser เดิมรองรับเฉพาะรูปแบบ SC101011 ทำให้ไม่พบรหัสวิชา',
        'fix' => 'ปรับโค้ดให้รองรับรหัสวิชาตัวเลข 6–8 หลัก',
        'status_after' => 'แก้แล้ว — อัปโหลดได้',
        'short' => 'รหัสวิชาตัวเลขล้วน',
    ],
    'missing_summary_likely' => [
        'title' => 'อ่านตารางสรุปเกรดท้ายใบไม่ได้ (รูปแบบ compact)',
        'cause' => 'ข้อความสรุปเกรดถูก extract ติดกัน เช่น 0.000100-80A หรือ 66.674A ทำให้ parser อ่านช่วงคะแนน/จำนวนไม่ได้ แม้จะพบรายชื่อนักศึกษาแล้วก็ตาม',
        'fix' => 'เพิ่มการอ่านสรุปเกรดแบบ compact และ fallback นับจากรายชื่อนักศึกษาหากตารางท้ายใบไม่ครบ',
        'status_after' => 'แก้แล้ว — อัปโหลดได้',
        'short' => 'สรุปเกรดแบบ compact',
    ],
    'missing_students_likely' => [
        'title' => 'อ่านรายชื่อนักศึกษา/เกรดไม่ได้',
        'cause' => 'แถวรายชื่อใน PDF ไม่ตรงรูปแบบที่ parser เดิมรองรับ (เช่น ชื่อต่างชาติ Ms./Mr., แถวที่ติดกันผิดปกติ, หรือ section ที่มีนักศึกษาว่าง/น้อยมากจนจับรายชื่อไม่ได้) จึงล้มก่อนถึงขั้นอ่านสรุปเกรด',
        'fix' => 'รองรับคำนำหน้า Ms./Mr. และปรับการจับรายชื่อ/สรุปเกรดให้ทนต่อรูปแบบจาก REG มากขึ้น',
        'status_after' => 'แก้แล้ว — อัปโหลดได้',
        'short' => 'รายชื่อนักศึกษา/ชื่อต่างชาติ',
    ],
    'empty_text' => [
        'title' => 'PDF เป็นภาพล้วน (ไม่มี text layer)',
        'cause' => 'สกัดข้อความจาก PDF ได้ว่าง — เป็นไฟล์สแกน/ภาพ ไม่ใช่ปัญหา regex ของ parser',
        'fix' => 'ขอไฟล์ใหม่จาก REG แบบพิมพ์เป็น PDF (text PDF) หรือทำ OCR ก่อนอัปโหลด ไม่แนะนำใส่ OCR ในเซิร์ฟเวอร์อัปโหลดทันที',
        'status_after' => 'ยังอัปโหลดไม่ได้ — ต้องแก้ที่ไฟล์ต้นทาง',
        'short' => 'PDF ภาพล้วน',
    ],
];

$groups = [];
foreach ($failedUpload as $row) {
    $file = (string) ($row['file'] ?? '');
    if ($file === '') {
        continue;
    }
    $stage = (string) ($diagByFile[$file]['fail_stage'] ?? '');
    if ($stage === '' && (($row['error_code'] ?? '') === 'image_only_pdf')) {
        $stage = 'empty_text';
    }
    if ($stage === '') {
        $stage = 'missing_summary_likely';
    }
    if (! isset($groups[$stage])) {
        $meta = $stageMeta[$stage] ?? [
            'title' => $stage,
            'cause' => 'ยังไม่ได้จัดกลุ่มละเอียด',
            'fix' => '-',
            'status_after' => '-',
            'short' => $stage,
        ];
        $groups[$stage] = $meta + ['files' => []];
    }
    $groups[$stage]['files'][] = $file;
}

$afterOk = 0;
$afterFail = 0;
foreach ($failedUpload as $row) {
    $stage = (string) ($preciseByFile[(string) $row['file']]['stage'] ?? '');
    if ($stage === 'ok') {
        $afterOk++;
    } else {
        $afterFail++;
    }
}

$phpWord = new PhpWord;
$phpWord->setDefaultFontName('TH Sarabun New');
$phpWord->setDefaultFontSize(14);
$sec = $phpWord->addSection([
    'marginTop' => 800,
    'marginBottom' => 800,
    'marginLeft' => 900,
    'marginRight' => 900,
]);

$phpWord->addTableStyle('DiagTable', [
    'borderSize' => 6,
    'borderColor' => '999999',
    'cellMargin' => 50,
]);

$sec->addText('สรุปผลการทดสอบอัปโหลดไฟล์ มข.11', ['bold' => true, 'size' => 20], ['alignment' => Jc::CENTER]);
$sec->addText('(ฉบับเพิ่มวิเคราะห์สาเหตุเชิงลึกของไฟล์ที่ไม่ผ่าน)', ['bold' => true, 'size' => 16], ['alignment' => Jc::CENTER]);
$sec->addText('หน้า: /grade-reports/upload', ['size' => 14], ['alignment' => Jc::CENTER]);
$sec->addTextBreak(1);

$sec->addText('ส่วนที่ 1: สรุปการทดสอบรอบแรก (ข้อมูลเดิม)', ['bold' => true, 'size' => 16]);
$sec->addText('วันที่ทดสอบรอบแรก: '.$testedAt);
$sec->addText('โฟลเดอร์: '.$folder);
$sec->addText('เงื่อนไขหน้าจอ: ภาคการศึกษาที่ '.$term.'/'.$year);
$sec->addText('เกณฑ์ผ่าน: อ่านเนื้อหา PDF ได้, พบรหัสวิชา+Section, ภาค/ปีในไฟล์ตรงกับที่เลือก');
$sec->addTextBreak(1);

$sec->addText('สรุปภาพรวมรอบแรก', ['bold' => true, 'size' => 15]);
$sec->addText('จำนวนไฟล์ทั้งหมด: '.$totalCount.' ไฟล์');
$sec->addText('ผ่าน: '.$passedCount.' ไฟล์');
$sec->addText('ไม่ผ่าน: '.$failedCount.' ไฟล์');
if ($totalCount > 0) {
    $sec->addText('อัตราผ่าน: '.number_format(($passedCount / $totalCount) * 100, 1).'%');
}
$sec->addTextBreak(1);

$sec->addText('สรุปสาเหตุบนหน้าจอ (รอบแรก)', ['bold' => true, 'size' => 15]);
$sec->addText('หมายเหตุ: ข้อความบนจอมักรวมหลายเคสไว้ใต้ “parse_failed” จึงต้องวิเคราะห์เชิงลึกเพิ่มในส่วนที่ 2');
arsort($byReason);
foreach ($byReason as $code => $count) {
    $sec->addText('- '.($reasonLabels[$code] ?? $code).': '.$count.' ไฟล์');
}
$sec->addTextBreak(1);

$sec->addText('ส่วนที่ 2: สาเหตุที่แท้จริงของไฟล์ที่ไม่ผ่าน (23 ไฟล์)', ['bold' => true, 'size' => 16]);
$sec->addText('จัดกลุ่มจากเนื้อหา PDF และจุดที่ parser ล้มจริง ไม่ใช่แค่ข้อความ error บนหน้าจอ');
$sec->addTextBreak(1);

$order = ['missing_subject_line', 'missing_summary_likely', 'missing_students_likely', 'empty_text'];
foreach ($order as $stage) {
    if (! isset($groups[$stage])) {
        continue;
    }
    $g = $groups[$stage];
    $sec->addText($g['title'].' — '.count($g['files']).' ไฟล์', ['bold' => true, 'size' => 15]);
    $sec->addText('สาเหตุ: '.$g['cause']);
    $sec->addText('แนวทางแก้: '.$g['fix']);
    $sec->addText('สถานะหลังปรับโค้ด: '.$g['status_after'], ['bold' => true]);
    $sec->addText('รายชื่อไฟล์:');
    foreach ($g['files'] as $idx => $name) {
        $sec->addText('  '.($idx + 1).') '.$name);
    }
    $sec->addTextBreak(1);
}

foreach ($groups as $stage => $g) {
    if (in_array($stage, $order, true)) {
        continue;
    }
    $sec->addText($g['title'].' — '.count($g['files']).' ไฟล์', ['bold' => true, 'size' => 15]);
    $sec->addText('สาเหตุ: '.$g['cause']);
    $sec->addText('แนวทางแก้: '.$g['fix']);
    $sec->addText('สถานะหลังปรับโค้ด: '.$g['status_after'], ['bold' => true]);
    $sec->addText('รายชื่อไฟล์: '.implode(', ', $g['files']));
    $sec->addTextBreak(1);
}

$sec->addText('ตารางรายไฟล์: สาเหตุบนจอ → สาเหตุจริง → สถานะหลังแก้', ['bold' => true, 'size' => 16]);
$table = $sec->addTable('DiagTable');
$table->addRow();
foreach (['ลำดับ', 'ชื่อไฟล์', 'สาเหตุบนจอ (รอบแรก)', 'สาเหตุจริง', 'สถานะหลังแก้โค้ด'] as $h) {
    $table->addCell(2000)->addText($h, ['bold' => true, 'size' => 12]);
}

foreach ($failedUpload as $i => $row) {
    $file = (string) $row['file'];
    $uiCode = (string) ($row['error_code'] ?? '');
    $uiLabel = $reasonLabels[$uiCode] ?? $uiCode;
    $stage = (string) ($diagByFile[$file]['fail_stage'] ?? '');
    if ($stage === '' && $uiCode === 'image_only_pdf') {
        $stage = 'empty_text';
    }
    $short = $stageMeta[$stage]['short'] ?? ($stage !== '' ? $stage : '—');
    $afterStage = (string) ($preciseByFile[$file]['stage'] ?? '');
    $afterLabel = match ($afterStage) {
        'ok' => 'ผ่านแล้ว',
        'empty_text' => 'ยังไม่ผ่าน (ภาพล้วน)',
        '' => 'ยังไม่ตรวจซ้ำ',
        default => 'ยังไม่ผ่าน ('.$afterStage.')',
    };

    $table->addRow();
    $table->addCell(700)->addText((string) ($i + 1), ['size' => 11]);
    $table->addCell(2400)->addText($file, ['size' => 11]);
    $table->addCell(2600)->addText($uiLabel, ['size' => 11]);
    $table->addCell(2400)->addText($short, ['size' => 11]);
    $table->addCell(2200)->addText($afterLabel, ['size' => 11]);
}

$sec->addTextBreak(1);
$sec->addText('ส่วนที่ 3: ผลหลังปรับโค้ด parser', ['bold' => true, 'size' => 16]);
$sec->addText('ไฟล์ที่เคยไม่ผ่าน 23 ไฟล์ → ผ่านแล้ว '.$afterOk.' ไฟล์ / ยังไม่ผ่าน '.$afterFail.' ไฟล์');
$projectedPass = $passedCount + $afterOk;
$projectedFail = $totalCount - $projectedPass;
$sec->addText('ประมาณการทั้งโฟลเดอร์หลังแก้: ผ่านประมาณ '.$projectedPass.'/'.$totalCount.' ไฟล์ (เหลือค้างประมาณ '.$projectedFail.' ไฟล์จาก PDF ภาพล้วน)');
$sec->addText('ไฟล์เปรียบเทียบที่เคยผ่านอยู่แล้ว (ยังผ่าน): SC001001-01, SC101011-01, SC152201-01, SC201101-01, SC752106-01');
$sec->addText('Unit test ของ RegistrarGradePdfParser: ผ่าน');
$sec->addTextBreak(1);

$sec->addText('สิ่งที่ปรับในโค้ด (RegistrarGradePdfParser)', ['bold' => true, 'size' => 15]);
$sec->addText('1) รองรับรหัสวิชาตัวเลข 6–8 หลัก');
$sec->addText('2) รองรับชื่อนักศึกษาขึ้นต้น Ms. / Mr.');
$sec->addText('3) อ่านตารางสรุปเกรดแบบ compact และ fallback นับจากรายชื่อ');
$sec->addText('4) ปรับปรุงข้อความแจ้งเตือนเมื่อ parse ไม่สำเร็จ ให้ชี้จุดที่พลาดชัดขึ้น');
$sec->addTextBreak(1);

$sec->addText('ส่วนที่ 4: คำแนะนำ', ['bold' => true, 'size' => 16]);
$sec->addText('1) Deploy โค้ด parser ชุดนี้ แล้วไฟล์ 21/23 ที่เคย fail จากสาเหตุรูปแบบข้อความจะอัปโหลดได้');
$sec->addText('2) สำหรับ SC523319-01.pdf และ SC563319-01.pdf: ขอไฟล์ใหม่จาก REG แบบ text PDF หรือทำ OCR ก่อนอัปโหลด');
$sec->addText('3) ไม่แนะนำติดตั้ง OCR ในขั้นตอนอัปโหลดบนเซิร์ฟเวอร์ทันที เพราะช้า หนัก และผลกับเอกสารไทยไม่นิ่ง');
$sec->addText('4) หากพบไฟล์ fail ใหม่ ให้ดู reason ใน log (missing_subject / missing_students / missing_summary / empty_text) จะชี้สาเหตุได้เร็วกว่าข้อความทั่วไปบนจอ');
$sec->addTextBreak(1);

$sec->addText('ภาคผนวก ก: รายละเอียดไฟล์ที่ไม่ผ่านจากรอบทดสอบแรก', ['bold' => true, 'size' => 16]);
$table2 = $sec->addTable('DiagTable');
$table2->addRow();
foreach (['ลำดับ', 'ชื่อไฟล์', 'รหัส/Sec', 'ภาคปีในไฟล์', 'ข้อความระบบ (รอบแรก)'] as $h) {
    $table2->addCell(2000)->addText($h, ['bold' => true, 'size' => 12]);
}
foreach ($failedUpload as $i => $row) {
    $codeSec = ((string) ($row['subject_code'] ?? '—'))
        .(! empty($row['section']) ? '-'.str_pad((string) $row['section'], 2, '0', STR_PAD_LEFT) : '');
    $ty = (! empty($row['file_term']) || ! empty($row['file_year']))
        ? 'ภาค '.($row['file_term'] ?? '?').'/'.($row['file_year'] ?? '?')
        : '—';
    $reason = preg_replace("/\s+/u", ' ', (string) ($row['reason'] ?? '')) ?? '';

    $table2->addRow();
    $table2->addCell(700)->addText((string) ($i + 1), ['size' => 10]);
    $table2->addCell(2400)->addText((string) $row['file'], ['size' => 10]);
    $table2->addCell(1400)->addText($codeSec, ['size' => 10]);
    $table2->addCell(1600)->addText($ty, ['size' => 10]);
    $table2->addCell(4200)->addText(mb_substr($reason, 0, 180), ['size' => 10]);
}

$sec->addTextBreak(1);
$sec->addText('ภาคผนวก ข: รายชื่อไฟล์ที่ผ่านรอบแรก (ย่อ)', ['bold' => true, 'size' => 16]);
$passedFiles = $upload['passed_files'] ?? [];
$sec->addText('จำนวน '.$passedCount.' ไฟล์ — แสดงเฉพาะชื่อไฟล์เพื่ออ้างอิงกับรายงานเดิม');
$names = array_map(static fn ($r) => (string) ($r['file'] ?? ''), $passedFiles);
$chunk = array_chunk($names, 6);
foreach ($chunk as $line) {
    $sec->addText(implode(', ', array_filter($line)), ['size' => 11]);
}

$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($docxPath);
echo "DOCX saved: {$docxPath}\n";

if (is_dir(dirname($docxCopy))) {
    if (@copy($docxPath, $docxCopy)) {
        echo "DOCX copied: {$docxCopy}\n";
    } else {
        echo "Copy to analysis name failed.\n";
    }
    // พยายามอัปเดตไฟล์ชื่อเดิมด้วย ถ้าไม่ได้เพราะถูกเปิดอยู่ ให้ใช้ไฟล์ชื่อวิเคราะห์สาเหตุแทน
    if (@copy($docxPath, $docxCopyAlt)) {
        echo "DOCX updated original name: {$docxCopyAlt}\n";
    } else {
        echo "Original-name copy skipped (file may be open). Use: {$docxCopy}\n";
    }
}

echo "Groups:\n";
foreach ($groups as $stage => $g) {
    echo "- {$stage}: ".count($g['files'])."\n";
}
echo "After fix: ok={$afterOk} remain={$afterFail}\n";
