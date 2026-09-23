<?php

namespace App\Services\ThesisGrade;

use App\Models\ThesisGrade;
use App\Models\ThesisGradeStudent;
use App\Support\ThesisGradeS0Letter;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ThesisGradeDocxExportService
{
    /**
     * Export S=0 explanation letter matching the official 2023 memo template.
     */
    public function downloadS0Letter(ThesisGrade $report, ?ThesisGradeStudent $student = null): BinaryFileResponse
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('TH SarabunPSK');
        $phpWord->setDefaultFontSize(16);

        $section = $phpWord->addSection([
            'paperSize' => 'A4',
            'marginTop' => 1008,
            'marginBottom' => 1440,
            'marginLeft' => 1440,
            'marginRight' => 1440,
        ]);

        $fields = ThesisGradeS0Letter::fields($report, $student);
        $font = ['name' => 'TH SarabunPSK', 'size' => 16];
        $bold = ['name' => 'TH SarabunPSK', 'size' => 16, 'bold' => true];
        $title = ['name' => 'TH SarabunPSK', 'size' => 22];

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
        $office->addText('      คณะวิทยาศาสตร์   สาขาวิชา'.($fields['department'] !== '' ? $fields['department'] : '..........'), $font);
        $office->addText('      หมายเลขโทรศัพท์  .....................', $font);

        $meta = $section->addTextRun(['spaceAfter' => 0]);
        $meta->addText('ที่', $bold);
        $meta->addText('  '.$fields['memo_no'], $font);
        $meta->addText('                      วันที่  '.$fields['letter_date'], $font);

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

        $section->addText('จึงเรียนมาเพื่อโปรดพิจารณา', $font, [
            'alignment' => Jc::BOTH,
            'indentation' => ['firstLine' => 1080],
            'spaceAfter' => 400,
        ]);

        $sign = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 40]);
        $emptyCell = ['borderSize' => 0, 'borderColor' => 'FFFFFF'];
        $right = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];
        $teacher = $fields['teacher'] !== '' ? $fields['teacher'] : '..........................................................';

        $sign->addRow();
        $sign->addCell(4200, $emptyCell);
        $sign->addCell(5000, $emptyCell)->addText('('.$teacher.')', $font, $right);

        $sign->addRow();
        $sign->addCell(4200, $emptyCell);
        $sign->addCell(5000, $emptyCell)->addText($fields['advisor_title'], $font, $right);

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
            // รูปถูกครอปเหลือแถบบนเนอร์แล้ว — กว้างเต็มเนื้อหา ประมาณ 160mm
            $footer->addImage($fields['footer_path'], [
                'width' => 454,
                'height' => 41,
                'alignment' => Jc::CENTER,
            ]);
        }

        $suffix = $student?->student_code
            ? preg_replace('/\W+/', '', (string) $student->student_code)
            : 'course';

        return $this->streamDocx(
            $phpWord,
            'S0-'.$report->displayCode().'-'.$report->paddedSection().'-'.$suffix.'.docx'
        );
    }

    /**
     * Summary table matching 3.1 ผลการเรียนวิทยานิพนธ์.pdf
     *
     * @param  Collection<int, ThesisGrade>  $reports
     */
    public function downloadSummary(Collection $reports, int $term, int $year): BinaryFileResponse
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('TH Sarabun New');
        $phpWord->setDefaultFontSize(16);

        $section = $phpWord->addSection([
            'paperSize' => 'A4',
            'orientation' => 'portrait',
            'marginTop' => 1134,
            'marginBottom' => 1134,
            'marginLeft' => 1134,
            'marginRight' => 1134,
        ]);

        $termLabel = match ($term) {
            1 => 'ภาคต้น',
            2 => 'ภาคปลาย',
            default => 'ภาคการศึกษาพิเศษ',
        };

        $section->addText('ผลการเรียนวิทยานิพนธ์ / การศึกษาอิสระ', ['bold' => true, 'size' => 20], ['alignment' => Jc::CENTER]);
        $section->addText($termLabel.' ปีการศึกษา '.$year, ['size' => 16], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 60,
            'width' => 9638,
            'unit' => TblWidth::TWIP,
        ]);

        $widths = [800, 4038, 1800, 1500, 1500];
        $header = ['ที่', 'รหัส-ชื่อวิชา', 'ภาค/ปีการศึกษา', 'กลุ่มที่', 'จำนวน (คน)'];
        $table->addRow();
        foreach ($header as $index => $label) {
            $table->addCell($widths[$index])->addText($label, ['bold' => true]);
        }

        $i = 1;
        foreach ($reports as $report) {
            /** @var ThesisGrade $report */
            $kind = $report->courseKindLabel() ?: ($report->subject ?: '');
            $codeLabel = $report->displayCode().' : '.$kind;
            if ($report->subject && ! str_contains($codeLabel, (string) $report->subject)) {
                $codeLabel = $report->displayCode().' : '.$report->subject;
            }

            $table->addRow();
            $table->addCell($widths[0])->addText((string) $i++);
            $table->addCell($widths[1])->addText($codeLabel);
            $table->addCell($widths[2])->addText($report->term.'/'.$report->year);
            $table->addCell($widths[3])->addText($report->paddedSection());
            $table->addCell($widths[4])->addText((string) $report->students->count());
        }

        $dots = str_repeat('.', 90);
        $font = ['name' => 'TH Sarabun New', 'size' => 16];
        $section->addTextBreak(1);
        $section->addText('จึงเสนอที่ประชุมเพื่อโปรดพิจารณา', $font);
        $section->addText($dots, $font);
        $section->addText('มติที่ประชุม'.$dots, $font);
        $section->addText($dots, $font);

        return $this->streamDocx($phpWord, sprintf('thesis-summary-%d-%d.docx', $term, $year));
    }

    private function streamDocx(PhpWord $phpWord, string $downloadName): BinaryFileResponse
    {
        $dir = storage_path('app/tmp');
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์ชั่วคราวได้');
        }

        $path = $dir.'/'.uniqid('thesis-docx-', true).'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return response()->download($path, $downloadName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}
