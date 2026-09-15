<?php

namespace Tests\Unit;

use App\Services\GradReport2Service;
use App\Services\SuperAdmin\GradReport2GroupExcelExportService;
use App\Services\SuperAdmin\GradReport2GroupService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GradReport2GroupExcelExportTest extends TestCase
{
    #[Test]
    public function spreadsheet_uses_paste_columns_and_thai_sarabun_font(): void
    {
        $service = new GradReport2GroupExcelExportService(
            new GradReport2GroupService(new GradReport2Service),
        );

        $spreadsheet = $service->buildSpreadsheet([
            [
                'group_code' => '300109',
                'subject' => 'PHYSICAL SCIENCE',
                'member_code' => 'SC002104',
            ],
            [
                'group_code' => '300109',
                'subject' => 'PHYSICAL SCIENCE',
                'member_code' => 'SC002105',
            ],
        ]);

        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('รหัสกลุ่ม', $sheet->getCell('A1')->getValue());
        $this->assertSame('ชื่อวิชา (ENG)', $sheet->getCell('B1')->getValue());
        $this->assertSame('รหัสวิชาในกลุ่ม', $sheet->getCell('C1')->getValue());
        $this->assertSame('300109', $sheet->getCell('A2')->getValue());
        $this->assertSame('PHYSICAL SCIENCE', $sheet->getCell('B2')->getValue());
        $this->assertSame('SC002104', $sheet->getCell('C2')->getValue());
        $this->assertSame('SC002105', $sheet->getCell('C3')->getValue());
        $this->assertSame('TH Sarabun New', $sheet->getStyle('A1')->getFont()->getName());
        $this->assertSame('TH Sarabun New', $sheet->getStyle('A2')->getFont()->getName());
        $this->assertSame('TH Sarabun New', $spreadsheet->getDefaultStyle()->getFont()->getName());
    }

    #[Test]
    public function export_route_and_button_are_wired(): void
    {
        $this->assertSame(
            url('/faculty-admin/grad-report2-groups/export'),
            route('faculty-admin.grad-report2-groups.export'),
        );

        $blade = (string) file_get_contents(resource_path('views/faculty-admin/grad-report2-groups/index.blade.php'));
        $this->assertStringContainsString('Export Excel', $blade);
        $this->assertStringContainsString('faculty-admin.grad-report2-groups.export', $blade);
        $this->assertStringContainsString('sheet-table', $blade);
        $this->assertStringContainsString('data-tip="แก้ไขชื่อ"', $blade);
        $this->assertStringContainsString('data-tip="เพิ่มรหัส"', $blade);
        $this->assertStringContainsString('data-tip="ลบกลุ่ม"', $blade);
    }
}
