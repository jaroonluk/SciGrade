<?php

namespace Tests\Unit;

use App\Services\GradReport2Service;
use App\Services\SuperAdmin\GradReport2GroupService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GradReport2PasteImportTest extends TestCase
{
    #[Test]
    public function it_parses_tab_separated_rows_and_merges_same_group(): void
    {
        $raw = "รหัสกลุ่ม\tชื่อวิชา (ENG)\tรหัสวิชาในกลุ่ม\n"
            ."300109\tPHYSICAL SCIENCE\tSC002104\n"
            ."300109\tPHYSICAL SCIENCE\tSC002105\n";

        $groups = $this->service()->parsePasteIntoGroups($raw);

        $this->assertCount(1, $groups);
        $this->assertSame('300109', $groups[0]['group_code']);
        $this->assertSame('PHYSICAL SCIENCE', $groups[0]['subject']);
        $this->assertSame(['SC002104', 'SC002105'], $groups[0]['member_codes']);
    }

    #[Test]
    public function it_parses_multiple_member_codes_in_one_cell(): void
    {
        $raw = "300109\tPHYSICAL SCIENCE\tSC002104, SC002105";

        $groups = $this->service()->parsePasteIntoGroups($raw);

        $this->assertSame(['SC002104', 'SC002105'], $groups[0]['member_codes']);
    }

    #[Test]
    public function it_rejects_rows_missing_required_columns(): void
    {
        $this->expectException(ValidationException::class);

        $this->service()->parsePasteIntoGroups("300109\t\tSC002104");
    }

    #[Test]
    public function paste_route_is_registered(): void
    {
        $this->assertSame(
            url('/faculty-admin/grad-report2-groups/paste'),
            route('faculty-admin.grad-report2-groups.paste'),
        );
    }

    #[Test]
    public function index_view_keeps_classic_form_and_adds_paste_area(): void
    {
        $blade = (string) file_get_contents(resource_path('views/faculty-admin/grad-report2-groups/index.blade.php'));

        $this->assertStringContainsString('id="form-create-group"', $blade);
        $this->assertStringContainsString('สร้างกลุ่มใหม่ / เพิ่มหลายรหัสเข้ากลุ่ม', $blade);
        $this->assertStringContainsString('เพิ่มแบบวางจาก Excel', $blade);
        $this->assertStringContainsString('id="paste-text"', $blade);
        $this->assertStringContainsString('1. รหัสกลุ่ม *', $blade);
        $this->assertStringContainsString('2. ชื่อวิชา (ENG) *', $blade);
        $this->assertStringContainsString('3. รหัสวิชาในกลุ่ม *', $blade);
        $this->assertStringContainsString('faculty-admin.grad-report2-groups.paste', $blade);
    }

    private function service(): GradReport2GroupService
    {
        return new GradReport2GroupService(new GradReport2Service);
    }
}
