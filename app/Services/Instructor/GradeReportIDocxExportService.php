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
            'marginTop' => 1008,
            'marginBottom' => 1440,
            'marginLeft' => 1440,
            'marginRight' => 1440,
        ]);

        $font = ['name' => 'TH SarabunPSK', 'size' => 16];
        $bold = ['name' => 'TH SarabunPSK', 'size' => 16, 'bold' => true];
        $title = ['name' => 'TH SarabunPSK', 'size' => 22];
        $small = ['name' => 'TH SarabunPSK', 'size' => 14];

        $header = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 0]);
        $header->addRow();
        $logoCell = $header->addCell(1400, ['valign' => 'center', 'borderSize' => 0, 'borderColor' => 'FFFFFF']);
        if (is_file($fields['emblem_path'])) {
            $logoCell->addImage($fields['emblem_path'], [
                'width' => 41,
                'height' => 71,
            ]);
        }
        $titleCell = $header->addCell(8600, ['valign' => 'center', 'borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $titleCell->addText('บันทึกข้อความ', $title, ['spaceAfter' => 0]);

        $section->addTextBreak(1);

        $office = $section->addTextRun(['spaceAfter' => 0]);
        $office->addText('ส่วนงาน', $bold);
        $office->addText(
            '      คณะวิทยาศาสตร์   สาขาวิชา'
            .($fields['department'] !== '' ? $fields['department'] : '...........................')
            .'  หมายเลขโทรศัพท์ …………………….',
            $font
        );

        $meta = $section->addTextRun(['spaceAfter' => 0]);
        $meta->addText('ที่', $bold);
        $meta->addText('  '.$fields['memo_no'], $font);
        $meta->addText('                         วันที่  '.$fields['letter_date'], $font);

        $subject = $section->addTextRun(['spaceAfter' => 120]);
        $subject->addText('เรื่อง', $bold);
        $subject->addText('  '.$fields['subject_line'], $font);

        $to = $section->addTextRun(['spaceAfter' => 200]);
        $to->addText('เรียน', $bold);
        $to->addText('  '.$fields['to_line'], $font);

        $section->addText($fields['body'], $font, [
            'alignment' => Jc::BOTH,
            'indentation' => ['firstLine' => 1080],
            'spaceAfter' => 200,
        ]);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 60,
            'width' => 9360,
            'unit' => TblWidth::TWIP,
        ]);

        $widths = [900, 1800, 3200, 3460];
        $headers = ['ลำดับที่', 'รหัสประจำตัว', 'ชื่อ-สกุล', 'สาเหตุ'];
        $table->addRow();
        foreach ($headers as $i => $label) {
            $table->addCell($widths[$i])->addText($label, $bold, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }

        foreach ($fields['students'] as $index => $student) {
            $table->addRow();
            $table->addCell($widths[0])->addText((string) ($index + 1), $font, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            $table->addCell($widths[1])->addText($student['student_code'], $font, ['spaceAfter' => 0]);
            $table->addCell($widths[2])->addText($student['name'], $font, ['spaceAfter' => 0]);
            $table->addCell($widths[3])->addText($student['reason'] !== '' ? $student['reason'] : '................................', $small, ['spaceAfter' => 0]);
        }

        $section->addTextBreak(1);
        $section->addText('จึงเรียนมาเพื่อโปรดพิจารณา', $font, [
            'alignment' => Jc::BOTH,
            'indentation' => ['firstLine' => 1080],
            'spaceAfter' => 400,
        ]);

        $emptyCell = ['borderSize' => 0, 'borderColor' => 'FFFFFF'];
        $right = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];
        $teacher = $fields['teacher'] !== '' ? $fields['teacher'] : '..........................................................';

        $sign = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 40]);
        $sign->addRow();
        $sign->addCell(4200, $emptyCell);
        $sign->addCell(5000, $emptyCell)->addText('('.$teacher.')', $font, $right);

        $sign->addRow();
        $sign->addCell(4200, $emptyCell);
        $sign->addCell(5000, $emptyCell)->addText('อาจารย์ประจำวิชา/อาจารย์ที่ปรึกษาโครงงาน', $font, $right);

        $sign->addRow();
        $sign->addCell(4200, $emptyCell)->addText('', $font, ['spaceAfter' => 400]);
        $sign->addCell(5000, $emptyCell)->addText('', $font, ['spaceAfter' => 400]);

        $sign->addRow();
        $sign->addCell(4200, $emptyCell);
        $sign->addCell(5000, $emptyCell)->addText('(..........................................................)', $font, $right);

        $sign->addRow();
        $sign->addCell(4200, $emptyCell);
        $sign->addCell(5000, $emptyCell)->addText($fields['chair_title'], $font, $right);

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
