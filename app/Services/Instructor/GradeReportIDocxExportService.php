<?php

namespace App\Services\Instructor;

use App\Support\GradeReportILetter;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * สร้างแบบฟอร์มบันทึกชี้แจงให้เกรด I ตาม docs/บันทึกชี้แจงให้เกรด I.docx
 */
class GradeReportIDocxExportService
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function download(array $input): BinaryFileResponse
    {
        $fields = GradeReportILetter::fields($input);

        if ($fields['students'] === []) {
            throw new \InvalidArgumentException('ไม่พบรายชื่อนักศึกษาที่ติดเกรด I จากไฟล์ มข.11');
        }

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('TH SarabunPSK');
        $phpWord->setDefaultFontSize(16);

        $section = $phpWord->addSection([
            'paperSize' => 'A4',
            'marginTop' => 850,
            'marginBottom' => 1134,
            'marginLeft' => 1134,
            'marginRight' => 1134,
        ]);

        $font = ['name' => 'TH SarabunPSK', 'size' => 16];
        $bold = ['name' => 'TH SarabunPSK', 'size' => 16, 'bold' => true];
        $title = ['name' => 'TH SarabunPSK', 'size' => 22, 'bold' => true];
        $paraTight = ['spaceBefore' => 0, 'spaceAfter' => 40, 'lineHeight' => 1.15];
        $paraBody = [
            'alignment' => Jc::BOTH,
            'indentation' => ['firstLine' => 720],
            'spaceBefore' => 120,
            'spaceAfter' => 160,
            'lineHeight' => 1.25,
        ];
        $paraClose = [
            'alignment' => Jc::BOTH,
            'indentation' => ['firstLine' => 720],
            'spaceBefore' => 160,
            'spaceAfter' => 280,
            'lineHeight' => 1.25,
        ];
        $cellText = ['spaceBefore' => 20, 'spaceAfter' => 20, 'lineHeight' => 1.1];

        $header = $section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 40,
            'width' => 10080,
            'unit' => TblWidth::TWIP,
        ]);
        $header->addRow();
        $logoCell = $header->addCell(1200, ['valign' => 'center', 'borderSize' => 0, 'borderColor' => 'FFFFFF']);
        if (is_file($fields['emblem_path'])) {
            $logoCell->addImage($fields['emblem_path'], [
                'width' => 40,
                'height' => 70,
            ]);
        }
        $titleCell = $header->addCell(8880, ['valign' => 'center', 'borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $titleCell->addText('บันทึกข้อความ', $title, ['alignment' => Jc::LEFT, 'spaceAfter' => 0]);

        $section->addTextBreak(0);

        $officeDept = $fields['department'] !== ''
            ? $fields['department']
            : 'สาขาวิชา...........................';
        $office = $section->addTextRun($paraTight);
        $office->addText('ส่วนงาน', $bold);
        $office->addText('      คณะวิทยาศาสตร์   '.$officeDept.'  หมายเลขโทรศัพท์ …………………….', $font);

        $meta = $section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0,
            'width' => 10080,
            'unit' => TblWidth::TWIP,
        ]);
        $meta->addRow();
        $metaLeft = $meta->addCell(5600, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $metaRight = $meta->addCell(4480, ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $metaLeftRun = $metaLeft->addTextRun(['spaceAfter' => 0, 'lineHeight' => 1.15]);
        $metaLeftRun->addText('ที่', $bold);
        $metaLeftRun->addText('  '.$fields['memo_no'], $font);
        $metaRightRun = $metaRight->addTextRun(['spaceAfter' => 0, 'lineHeight' => 1.15]);
        $metaRightRun->addText('วันที่  ', $bold);
        $metaRightRun->addText($fields['letter_date'], $font);

        $subject = $section->addTextRun([
            'spaceBefore' => 80,
            'spaceAfter' => 60,
            'lineHeight' => 1.2,
            'alignment' => Jc::BOTH,
        ]);
        $subject->addText('เรื่อง', $bold);
        $subject->addText('  '.$fields['subject_line'], $font);

        $to = $section->addTextRun([
            'spaceBefore' => 40,
            'spaceAfter' => 120,
            'lineHeight' => 1.15,
        ]);
        $to->addText('เรียน', $bold);
        $to->addText('  '.$fields['to_line'], $font);

        $section->addText($fields['body'], $font, $paraBody);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'width' => 10080,
            'unit' => TblWidth::TWIP,
        ]);

        // ลำดับ / รหัส / ชื่อ-สกุล / สาเหตุ — รวมกว้างพอดีขอบกระดาษ
        $widths = [900, 2000, 3400, 3780];
        $headers = ['ลำดับที่', 'รหัสประจำตัว', 'ชื่อ-สกุล', 'สาเหตุ'];
        $table->addRow();
        foreach ($headers as $i => $label) {
            $table->addCell($widths[$i], ['valign' => 'center'])
                ->addText($label, $bold, array_merge($cellText, ['alignment' => Jc::CENTER]));
        }

        foreach ($fields['students'] as $index => $student) {
            $table->addRow();
            $table->addCell($widths[0], ['valign' => 'top'])
                ->addText((string) ($index + 1), $font, array_merge($cellText, ['alignment' => Jc::CENTER]));
            $table->addCell($widths[1], ['valign' => 'top'])
                ->addText($student['student_code'], $font, $cellText);
            $table->addCell($widths[2], ['valign' => 'top'])
                ->addText($student['name'], $font, $cellText);
            $reasonText = $student['reason'] !== '' ? $student['reason'] : '................................';
            $table->addCell($widths[3], ['valign' => 'top'])
                ->addText($reasonText, $font, array_merge($cellText, ['alignment' => Jc::BOTH]));
        }

        $section->addText('จึงเรียนมาเพื่อโปรดพิจารณา', $font, $paraClose);

        $emptyCell = ['borderSize' => 0, 'borderColor' => 'FFFFFF'];
        $right = ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.15];
        $teacher = $fields['teacher'] !== '' ? $fields['teacher'] : '..........................................................';

        $sign = $section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 40,
            'width' => 10080,
            'unit' => TblWidth::TWIP,
        ]);
        $sign->addRow();
        $sign->addCell(4200, $emptyCell);
        $sign->addCell(5880, $emptyCell)->addText('('.$teacher.')', $font, $right);

        $sign->addRow();
        $sign->addCell(4200, $emptyCell);
        // หลังตาราง: แสดงเฉพาะคำว่า «อาจารย์ประจำวิชา» ไม่ต่อด้วยชื่อ
        $sign->addCell(5880, $emptyCell)->addText('อาจารย์ประจำวิชา', $font, $right);

        $sign->addRow();
        $sign->addCell(4200, $emptyCell)->addText('', $font, ['spaceAfter' => 360]);
        $sign->addCell(5880, $emptyCell)->addText('', $font, ['spaceAfter' => 360]);

        $sign->addRow();
        $sign->addCell(4200, $emptyCell);
        $sign->addCell(5880, $emptyCell)->addText('(..........................................................)', $font, $right);

        $sign->addRow();
        $sign->addCell(4200, $emptyCell);
        $sign->addCell(5880, $emptyCell)->addText($fields['chair_title'], $font, $right);

        if (is_file($fields['footer_path'])) {
            $footer = $section->addFooter();
            $footer->addImage($fields['footer_path'], [
                'width' => 454,
                'height' => 41,
                'alignment' => Jc::CENTER,
            ]);
        }

        $code = preg_replace('/\W+/', '', $fields['subject_code']) ?: 'SUBJECT';

        return $this->streamDocx($phpWord, 'บันทึกชี้แจงเกรดI-'.$code.'.docx');
    }

    private function streamDocx(PhpWord $phpWord, string $downloadName): BinaryFileResponse
    {
        $dir = storage_path('app/tmp');
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์ชั่วคราวได้');
        }

        $path = $dir.'/'.uniqid('grade-i-docx-', true).'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return response()->download($path, $downloadName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}
