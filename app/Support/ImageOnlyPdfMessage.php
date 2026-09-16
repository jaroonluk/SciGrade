<?php

namespace App\Support;

/**
 * ข้อความแจ้งเมื่ออัปโหลด PDF ที่เป็นภาพสแกน/พิมพ์เป็นรูป ไม่มีข้อความตารางฝัง
 */
final class ImageOnlyPdfMessage
{
    public const REG_URL = 'https://reg.kku.ac.th/';

    public const TITLE = 'ไฟล์นี้เป็น PDF แบบภาพ — อ่านเนื้อหาไม่ได้';

    public const TEXT = 'ไฟล์นี้เป็น PDF แบบภาพ ระบบไม่สามารถอ่านเนื้อหาเพื่อมาแสดงข้อมูลได้ '
        .'กรุณาใช้ใบ มข.11 ที่ส่งออกจากระบบ REG โดยตรง (มีข้อความเลือกได้) '
        .'ไม่ใช่ไฟล์สแกนหรือพิมพ์เป็นรูปภาพ แล้วค่อยอัปโหลดใหม่';

    public static function matches(string $message): bool
    {
        return str_contains($message, 'ไฟล์นี้เป็น PDF แบบภาพ');
    }

    /**
     * @return array{title: string, body: string, reg_url: string, image_pdf: true}
     */
    public static function payload(): array
    {
        return [
            'title' => self::TITLE,
            'body' => self::TEXT,
            'reg_url' => self::REG_URL,
            'image_pdf' => true,
        ];
    }
}
