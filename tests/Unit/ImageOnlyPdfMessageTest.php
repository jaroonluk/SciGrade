<?php

namespace Tests\Unit;

use App\Services\RegistrarGradePdfParser;
use App\Services\ThesisGrade\ThesisGradePdfParser;
use App\Support\ImageOnlyPdfMessage;
use PHPUnit\Framework\TestCase;

class ImageOnlyPdfMessageTest extends TestCase
{
    public function test_shared_message_matches_user_guidance(): void
    {
        $text = ImageOnlyPdfMessage::TEXT;

        $this->assertStringContainsString('ไฟล์นี้เป็น PDF แบบภาพ', $text);
        $this->assertStringContainsString('ระบบไม่สามารถอ่านเนื้อหาเพื่อมาแสดงข้อมูลได้', $text);
        $this->assertStringContainsString('ใบ มข.11 ที่ส่งออกจากระบบ REG โดยตรง', $text);
        $this->assertStringContainsString('มีข้อความเลือกได้', $text);
        $this->assertStringContainsString('ไม่ใช่ไฟล์สแกนหรือพิมพ์เป็นรูปภาพ', $text);
        $this->assertStringContainsString('แล้วค่อยอัปโหลดใหม่', $text);
    }

    public function test_parsers_use_the_same_message(): void
    {
        $this->assertSame(ImageOnlyPdfMessage::TEXT, ThesisGradePdfParser::IMAGE_PDF_MESSAGE);
        $this->assertSame(ImageOnlyPdfMessage::TEXT, (new RegistrarGradePdfParser)->imagePdfMessage());
    }

    public function test_matches_helper(): void
    {
        $this->assertTrue(ImageOnlyPdfMessage::matches(ImageOnlyPdfMessage::TEXT));
        $this->assertFalse(ImageOnlyPdfMessage::matches('รูปแบบไฟล์ไม่ถูกต้อง'));
    }
}
