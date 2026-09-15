<?php

namespace App\Services\SuperAdmin;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradReport2GroupExcelExportService
{
    public function __construct(
        private readonly GradReport2GroupService $groups,
    ) {}

    public function download(?string $q = null): StreamedResponse
    {
        $rows = $this->groups->exportPasteRows($q);
        $spreadsheet = $this->buildSpreadsheet($rows);
        $filename = 'grad-report2-groups-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  list<array{group_code: string, subject: string, member_code: string}>  $rows
     */
    public function buildSpreadsheet(array $rows): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('จัดกลุ่มรายวิชา');

        $headers = ['รหัสกลุ่ม', 'ชื่อวิชา (ENG)', 'รหัสวิชาในกลุ่ม'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }

        $rowIndex = 2;
        foreach ($rows as $row) {
            $sheet->setCellValueExplicit([1, $rowIndex], $row['group_code'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit([2, $rowIndex], $row['subject'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit([3, $rowIndex], $row['member_code'], DataType::TYPE_STRING);
            $rowIndex++;
        }

        $lastRow = max(1, $rowIndex - 1);
        $range = 'A1:C'.$lastRow;

        $sheet->getStyle($range)->getFont()
            ->setName('TH Sarabun New')
            ->setSize(14);

        $sheet->getStyle('A1:C1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1:C1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FAF0E6');
        $sheet->getStyle('A1:C1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB('E8C4B8');

        $sheet->getStyle('A2:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C2:C'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        foreach (['A' => 16, 'B' => 36, 'C' => 20] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $sheet->freezePane('A2');
        $spreadsheet->getDefaultStyle()->getFont()->setName('TH Sarabun New')->setSize(14);

        return $spreadsheet;
    }
}
