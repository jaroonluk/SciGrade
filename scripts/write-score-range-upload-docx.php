<?php

declare(strict_types=1);

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;

require __DIR__.'/../vendor/autoload.php';

// สำคัญ: ค่าเริ่มต้นของ PhpWord ปิด escaping ไว้ ทำให้ &, <, > ในข้อความทำไฟล์ .docx พัง
Settings::setOutputEscapingEnabled(true);

$outDir = __DIR__.'/../storage/app/test-reports';
$jsonPath = $outDir.'/mk11-score-ranges-term1-2567.json';
$data = json_decode((string) file_get_contents($jsonPath), true) ?: [];

$docxPath = $outDir.'/สรุปทดสอบช่วงคะแนน-มข11-เทอม1-2567.docx';
$docxCopy = 'D:\\ข้อมูลระบบ\\SCiGRADE\\สรุปทดสอบช่วงคะแนน-มข11-เทอม1-2567.docx';

$total = (int) ($data['total'] ?? 0);
$parseOk = (int) ($data['parse_ok'] ?? 0);
$parseFail = (int) ($data['parse_fail'] ?? 0);
$withRanges = (int) ($data['with_manual_ranges'] ?? 0);
$withoutRanges = (int) ($data['without_manual_ranges'] ?? 0);
$results = $data['results'] ?? [];

$noRangeFiles = array_values(array_filter($results, static fn ($r) => ($r['ok'] ?? false) && empty($r['has_manual_ranges'])));
$failFiles = array_values(array_filter($results, static fn ($r) => ! ($r['ok'] ?? false)));
$rangeFilesSample = array_values(array_filter($results, static fn ($r) => ! empty($r['has_manual_ranges'])));

$phpWord = new PhpWord;
$phpWord->setDefaultFontName('TH Sarabun New');
$phpWord->setDefaultFontSize(14);
$sec = $phpWord->addSection([
    'marginTop' => 800,
    'marginBottom' => 800,
    'marginLeft' => 900,
    'marginRight' => 900,
]);
$phpWord->addTableStyle('ResultTable', [
    'borderSize' => 6,
    'borderColor' => '999999',
    'cellMargin' => 50,
]);

$sec->addText('สรุปทดสอบอัปโหลด มข.11 และการอ่านช่วงคะแนน (MANUAL)', ['bold' => true, 'size' => 20], ['alignment' => Jc::CENTER]);
$sec->addText('หน้า: /grade-reports/upload?term=1&year=2567', ['size' => 14], ['alignment' => Jc::CENTER]);
$sec->addTextBreak(1);

$sec->addText('ส่วนที่ 1: ขอบเขตการทดสอบ', ['bold' => true, 'size' => 16]);
$sec->addText('วันที่ทดสอบ: '.($data['tested_at'] ?? date('Y-m-d H:i:s')));
$sec->addText('โฟลเดอร์: '.($data['folder'] ?? ''));
$sec->addText('เงื่อนไข: ภาคการศึกษาที่ '.($data['term'] ?? 1).'/'.($data['year'] ?? 2567));
$sec->addText('จุดตรวจเพิ่มจากรอบก่อน: ต้องอ่านช่วงคะแนนคอลัมน์ MANUAL ท้ายใบ มข.11 ได้ เพื่อใช้ในขั้นตอนตัดเกรด');
$sec->addTextBreak(1);

$sec->addText('ส่วนที่ 2: สรุปผลตัวเลข', ['bold' => true, 'size' => 16]);
$sec->addText('จำนวนไฟล์ทั้งหมด: '.$total.' ไฟล์');
$sec->addText('อ่านเนื้อหา PDF ได้: '.$parseOk.' ไฟล์');
$sec->addText('อ่านเนื้อหาไม่ได้ (เช่น PDF ภาพล้วน): '.$parseFail.' ไฟล์');
$sec->addText('อ่านช่วงคะแนน MANUAL ได้ (ผ่านเกณฑ์อัปโหลด): '.$withRanges.' ไฟล์');
$sec->addText('อ่านเนื้อหาได้แต่ไม่มีช่วงคะแนน MANUAL ใช้ได้: '.$withoutRanges.' ไฟล์');
if ($total > 0) {
    $sec->addText('อัตราที่พร้อมอัปโหลดตามกฎใหม่: '.number_format(($withRanges / $total) * 100, 1).'%');
}
$sec->addTextBreak(1);

$sec->addText('ส่วนที่ 3: กติกาที่ระบบใช้หลังปรับโค้ด', ['bold' => true, 'size' => 16]);
$sec->addText('1) ถ้าอ่านคอลัมน์ MANUAL / ช่วงคะแนนไม่ได้', ['bold' => true]);
$sec->addText('   - แจ้งผู้ใช้ชัดเจนว่าอ่านช่วงคะแนนไม่ได้');
$sec->addText('   - ไม่อนุญาตให้อัปโหลดผ่าน / ไม่ให้กดไปขั้นตอนถัดไป');
$sec->addText('   - ข้อความหลัก: ระบบอ่านคอลัมน์ช่วงคะแนน (MANUAL) จากใบ มข.11 ไม่ได้ จึงยังไปขั้นตอนตัดเกรดต่อไม่ได้ — กรุณาตรวจใน REG ว่ากำหนดช่วงคะแนนครบ แล้วดาวน์โหลดใบใหม่');
$sec->addTextBreak(1);
$sec->addText('2) ถ้าอ่านคอลัมน์ MANUAL ได้', ['bold' => true]);
$sec->addText('   - อนุญาตให้อัปโหลดและไปขั้นตอนถัดไปได้');
$sec->addText('   - แสดงช่วงคะแนนที่อ่านได้ในขั้นตอนที่ 3');
$sec->addText('   - แจ้งเตือน: ระบบไม่สามารถตรวจสอบความถูกต้องของช่วงคะแนนการพิจารณาผลการเรียนได้ กรุณาตรวจสอบข้อมูลก่อนนำ มข.11 เข้าสู่ระบบ');
$sec->addTextBreak(1);
$sec->addText('3) เกณฑ์ “อ่าน MANUAL ได้”', ['bold' => true]);
$sec->addText('   - มีช่วงคะแนนเกรด A (เช่น 100-80) หรือ');
$sec->addText('   - มีช่วงคะแนนเกรดอื่นอย่างน้อย 2 ช่องในรูปแบบ ตัวเลข-ตัวเลข');
$sec->addText('   - ใบที่คอลัมน์ MANUAL ว่าง (แสดงเป็น <<->> ในข้อความ PDF) ถือว่าอ่านไม่ได้');
$sec->addTextBreak(1);

$sec->addText('ส่วนที่ 4: สาเหตุที่พบบ่อยเมื่ออ่าน MANUAL ไม่ได้', ['bold' => true, 'size' => 16]);
$sec->addText('- ในระบบ REG ไม่ได้กรอก/บันทึกช่วงคะแนนตัดเกรด ทำให้คอลัมน์ MANUAL ว่าง');
$sec->addText('- ใบ มข.11 เป็นไฟล์สแกน/ภาพล้วน ไม่มีข้อความให้ระบบอ่าน');
$sec->addText('- บางใบมีเฉพาะจำนวนนักศึกษาต่อเกรด แต่ไม่มีตัวเลขช่วงคะแนน');
$sec->addTextBreak(1);

$sec->addText('ส่วนที่ 5: ไฟล์ที่อ่านเนื้อหาไม่ได้', ['bold' => true, 'size' => 16]);
if ($failFiles === []) {
    $sec->addText('ไม่มี');
} else {
    $table = $sec->addTable('ResultTable');
    $table->addRow();
    foreach (['ลำดับ', 'ชื่อไฟล์', 'สาเหตุ'] as $h) {
        $table->addCell(3000)->addText($h, ['bold' => true, 'size' => 12]);
    }
    foreach ($failFiles as $i => $row) {
        $table->addRow();
        $table->addCell(700)->addText((string) ($i + 1), ['size' => 11]);
        $table->addCell(2800)->addText((string) $row['file'], ['size' => 11]);
        $table->addCell(4800)->addText(mb_substr(preg_replace("/\s+/u", ' ', (string) ($row['error'] ?? '')) ?? '', 0, 180), ['size' => 11]);
    }
}
$sec->addTextBreak(1);

$sec->addText('ส่วนที่ 6: ไฟล์ที่อ่านได้แต่ไม่มีช่วงคะแนน MANUAL (จะถูกบล็อกอัปโหลด)', ['bold' => true, 'size' => 16]);
$sec->addText('จำนวน '.$withoutRanges.' ไฟล์ — รายการด้านล่าง');
$table2 = $sec->addTable('ResultTable');
$table2->addRow();
foreach (['ลำดับ', 'ชื่อไฟล์', 'รหัสวิชา', 'Sec', 'ช่วงที่อ่านได้'] as $h) {
    $table2->addCell(2000)->addText($h, ['bold' => true, 'size' => 12]);
}
foreach ($noRangeFiles as $i => $row) {
    $ranges = $row['ranges'] ?? [];
    $rangeText = $ranges === [] ? '—' : implode(', ', array_map(
        static fn ($k, $v) => str_replace('score_', '', (string) $k).'='.$v,
        array_keys($ranges),
        array_values($ranges),
    ));
    $table2->addRow();
    $table2->addCell(700)->addText((string) ($i + 1), ['size' => 10]);
    $table2->addCell(2800)->addText((string) $row['file'], ['size' => 10]);
    $table2->addCell(1400)->addText((string) ($row['subject_code'] ?? ''), ['size' => 10]);
    $table2->addCell(800)->addText((string) ($row['section'] ?? ''), ['size' => 10]);
    $table2->addCell(3600)->addText($rangeText, ['size' => 10]);
}
$sec->addTextBreak(1);

$sec->addText('ส่วนที่ 7: ตัวอย่างไฟล์ที่อ่านช่วงคะแนนได้ (แสดงบางส่วน)', ['bold' => true, 'size' => 16]);
$table3 = $sec->addTable('ResultTable');
$table3->addRow();
foreach (['ลำดับ', 'ชื่อไฟล์', 'รหัสวิชา', 'Sec', 'ตัวอย่างช่วง A'] as $h) {
    $table3->addCell(2000)->addText($h, ['bold' => true, 'size' => 12]);
}
foreach (array_slice($rangeFilesSample, 0, 40) as $i => $row) {
    $table3->addRow();
    $table3->addCell(700)->addText((string) ($i + 1), ['size' => 10]);
    $table3->addCell(2800)->addText((string) $row['file'], ['size' => 10]);
    $table3->addCell(1400)->addText((string) ($row['subject_code'] ?? ''), ['size' => 10]);
    $table3->addCell(800)->addText((string) ($row['section'] ?? ''), ['size' => 10]);
    $table3->addCell(3600)->addText((string) (($row['ranges']['score_a'] ?? '—')), ['size' => 10]);
}
if (count($rangeFilesSample) > 40) {
    $sec->addText('... และอีก '.(count($rangeFilesSample) - 40).' ไฟล์ (ดูรายละเอียดใน JSON mk11-score-ranges-term1-2567.json)');
}
$sec->addTextBreak(1);

$sec->addText('ส่วนที่ 8: คำแนะนำการใช้งาน', ['bold' => true, 'size' => 16]);
$sec->addText('1) ก่อนดาวน์โหลดใบ มข.11 จาก REG ให้กำหนดช่วงคะแนนตัดเกรดให้ครบ');
$sec->addText('2) ใช้ไฟล์ PDF ที่ส่งออกจาก REG โดยตรง (มีข้อความ) ไม่ใช้ไฟล์สแกน');
$sec->addText('3) เมื่ออัปโหลดผ่านแล้ว ให้อ่านข้อความเตือนและตรวจช่วงคะแนนในขั้นตอนที่ 3 ก่อนยืนยัน');
$sec->addText('4) หากถูกบล็อกด้วยข้อความ MANUAL ให้อัปโหลดใบใหม่หลังแก้ช่วงคะแนนใน REG');

$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($docxPath);
echo "DOCX saved: {$docxPath}\n";

if (is_dir(dirname($docxCopy)) && @copy($docxPath, $docxCopy)) {
    echo "DOCX copied: {$docxCopy}\n";
}

echo "with_ranges={$withRanges} without={$withoutRanges} fail={$parseFail}\n";
