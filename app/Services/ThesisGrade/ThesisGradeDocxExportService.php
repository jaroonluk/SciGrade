<?php

namespace App\Services\ThesisGrade;

use App\Models\ThesisGrade;
use App\Models\ThesisGradeStudent;
use App\Support\ThaiDateTime;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ThesisGradeDocxExportService
{
    /**
     * Export S=0 explanation letter for one student.
     */
    public function downloadS0Letter(ThesisGrade $report, ThesisGradeStudent $student): BinaryFileResponse
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('TH Sarabun New');
        $phpWord->setDefaultFontSize(16);

        $section = $phpWord->addSection([
            'marginTop' => 1000,
            'marginBottom' => 1000,
            'marginLeft' => 1200,
            'marginRight' => 1200,
        ]);

        $section->addText('บันทึกข้อความ', ['bold' => true, 'size' => 20], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);

        $section->addText('ส่วนราชการ  คณะวิทยาศาสตร์  สาขาวิชา........................');
        $section->addText('ที่  ศบ  ................../........    วันที่ ......................');
        $section->addText('เรื่อง  ชี้แจงการให้เกรด S = 0 ในรายวิชา '.($report->displayCode().' '.$report->subject));
        $section->addTextBreak(1);
        $section->addText('เรียน  คณบดีคณะวิทยาศาสตร์ (ผ่านหัวหน้าสาขาวิชา..................)');
        $section->addTextBreak(1);

        $section->addText(
            'ด้วยข้าพเจ้า (นาย/นาง/นางสาว)...................................... รหัสประจำตัวนักศึกษา '
            .($student->student_code ?: '....................')
            .' นักศึกษาหลักสูตร (วิทยานิพนธ์/ดุษฎีนิพนธ์/การศึกษาอิสระ) สาขาวิชา................................ '
            .'ได้รับอนุมัติเค้าโครงวิทยานิพนธ์แล้ว / ยังไม่ได้รับอนุมัติเค้าโครง '
            .'และได้ลงทะเบียนรายวิชา '.$report->displayCode().' '.$report->subject
            .' ได้ S=0 ครั้งที่ .... ด้วยเหตุผลจาก...............................................',
            [],
            ['alignment' => Jc::BOTH]
        );
        $section->addTextBreak(1);
        $section->addText('จึงเรียนมาเพื่อโปรดพิจารณา', [], ['alignment' => Jc::BOTH]);
        $section->addTextBreak(2);

        $section->addText('(..........................................................)');
        $section->addText('อาจารย์ที่ปรึกษาวิทยานิพนธ์');
        $section->addTextBreak(1);
        $section->addText('(..........................................................)');
        $section->addText('หัวหน้าสาขาวิชา........');
        $section->addTextBreak(1);

        $section->addText('ข้อมูลระบบ SciGrade (เติมอัตโนมัติ)', ['bold' => true, 'size' => 14]);
        $section->addText('ภาคการศึกษา: '.$report->termLabel().' '.$report->year);
        $section->addText('กลุ่มที่: '.$report->paddedSection());
        $section->addText('อาจารย์ผู้ส่ง: '.($report->teacher ?: $report->username));
        $section->addText('นักศึกษา: '.trim($student->student_code.' '.$student->student_name));
        $section->addText('ระดับ: '.$student->degreeLabel().' · ภาคสะสม: '.$student->thesis_terms_count);
        $section->addText('เกรด/หน่วยกิต: '.($student->grade ?: '—').' / '.($student->progress_credits ?? '—'));
        if ($student->note) {
            $section->addText('หมายเหตุ: '.$student->note);
        }
        $section->addText('พิมพ์เมื่อ: '.ThaiDateTime::formatDateTime(now()));

        return $this->streamDocx(
            $phpWord,
            'S0-'.$report->displayCode().'-'.$report->paddedSection().'-'.preg_replace('/\W+/', '', (string) $student->student_code).'.docx'
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
            'orientation' => 'landscape',
            'marginTop' => 800,
            'marginBottom' => 800,
            'marginLeft' => 800,
            'marginRight' => 800,
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
            'width' => 14000,
            'unit' => 'twip',
        ]);

        $header = ['ที่', 'รหัส-ชื่อวิชา', 'ภาค/ปีการศึกษา', 'กลุ่มที่', 'จำนวน (คน)', 'หมายเหตุ (กรอกเพิ่ม)'];
        $table->addRow();
        foreach ($header as $label) {
            $table->addCell(2000)->addText($label, ['bold' => true]);
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
            $table->addCell(800)->addText((string) $i++);
            $table->addCell(4500)->addText($codeLabel);
            $table->addCell(1800)->addText($report->term.'/'.$report->year);
            $table->addCell(1200)->addText($report->paddedSection());
            $table->addCell(1200)->addText((string) $report->students->count());
            $table->addCell(3500)->addText('');
        }

        $section->addTextBreak(1);
        $section->addText('หมายเหตุ: คอลัมน์หมายเหตุเว้นว่างไว้ให้ Admin กลางกรอกเพิ่มนอกระบบ', ['italic' => true, 'size' => 12]);
        $section->addText('ส่งออกจาก SciGrade เมื่อ '.ThaiDateTime::formatDateTime(now()), ['size' => 12]);

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
