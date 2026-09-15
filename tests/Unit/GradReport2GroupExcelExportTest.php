<?php

namespace Tests\Unit;

use App\Services\GradReport2Service;
use App\Services\SuperAdmin\GradReport2GroupExcelExportService;
use App\Services\SuperAdmin\GradReport2GroupService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ZipArchive;

class GradReport2GroupExcelExportTest extends TestCase
{
    #[Test]
    public function spreadsheet_uses_paste_columns_and_thai_sarabun_font(): void
    {
        $service = new GradReport2GroupExcelExportService(
            new GradReport2GroupService(new GradReport2Service),
        );

        $binary = $service->buildXlsxBinary([
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

        $this->assertNotSame('', $binary);
        $this->assertSame('PK', substr($binary, 0, 2));

        $tmp = tempnam(sys_get_temp_dir(), 'gr2test');
        $this->assertNotFalse($tmp);
        file_put_contents($tmp, $binary);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tmp) === true);

        $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $styles = (string) $zip->getFromName('xl/styles.xml');
        $zip->close();
        @unlink($tmp);

        $this->assertStringContainsString('รหัสกลุ่ม', $sheet);
        $this->assertStringContainsString('ชื่อวิชา (ENG)', $sheet);
        $this->assertStringContainsString('รหัสวิชาในกลุ่ม', $sheet);
        $this->assertStringContainsString('300109', $sheet);
        $this->assertStringContainsString('PHYSICAL SCIENCE', $sheet);
        $this->assertStringContainsString('SC002104', $sheet);
        $this->assertStringContainsString('SC002105', $sheet);
        $this->assertStringContainsString('TH Sarabun New', $styles);
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
        $this->assertStringContainsString('sheet-source-badge', $blade);
        $this->assertStringContainsString('สถานะ', $blade);
    }
}
