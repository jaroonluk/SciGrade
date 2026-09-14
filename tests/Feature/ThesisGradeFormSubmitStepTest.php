<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThesisGradeFormSubmitStepTest extends TestCase
{
    #[Test]
    public function submit_button_is_reserved_for_step_three_and_lists_required_ticks(): void
    {
        $blade = (string) file_get_contents(resource_path('views/thesis-grades/form.blade.php'));
        $js = (string) file_get_contents(public_path('js/thesis-grade-form.js'));

        $this->assertStringContainsString('id="wizard-stepper"', $blade);
        $this->assertStringContainsString('wizard-trail', $blade);
        $this->assertStringContainsString('wizard-chevron', $blade);
        $this->assertStringContainsString('data-wizard-dot="1"', $blade);
        $this->assertStringContainsString('el.classList.toggle(\'is-current\', n === step)', $js);
        $this->assertStringContainsString('el.classList.toggle(\'is-done\', n < step)', $js);
        $this->assertStringContainsString('id="submit-to-dept"', $blade);
        $this->assertStringContainsString("(int) \$step === 3 ? '' : 'hidden'", $blade);
        $this->assertStringContainsString('ยังส่งเข้าสาขาไม่ได้ — กรุณาติ๊กยืนยันข้อที่ยังขาด', $blade);
        $this->assertStringContainsString('ตรวจสอบข้อมูลนักศึกษาที่ครบกำหนดอนุมัติเค้าโครงแล้ว (ป.โท ภายใน 2 ภาค / ป.เอก ภายใน 4 ภาค)', $blade);
        $this->assertStringContainsString('ไฟล์ใบส่งเกรดได้ลงนามด้วยลายมือชื่อดิจิทัลแล้ว', $blade);

        $this->assertStringContainsString("submitBtn.classList.toggle('hidden', step !== 3)", $js);
        $this->assertStringContainsString('missingSubmitChecks', $js);
        $this->assertStringContainsString('e.preventDefault()', $js);
        $this->assertStringContainsString('ติ๊ก «', $js);
    }
}
