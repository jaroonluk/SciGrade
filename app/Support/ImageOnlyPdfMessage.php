<?php

namespace App\Support;

/**
 * ข้อความแจ้งเมื่ออัปโหลด PDF ที่เป็นภาพสแกน/พิมพ์เป็นรูป ไม่มีข้อความตารางฝัง
 */
final class ImageOnlyPdfMessage
{
    public const TEXT = 'ไฟล์นี้เป็น PDF แบบภาพ ระบบไม่สามารถอ่านเนื้อหาเพื่อมาแสดงข้อมูลได้ '
        .'กรุณาใช้ใบ มข.11 ที่ส่งออกจากระบบ REG โดยตรง (มีข้อความเลือกได้) '
        .'ไม่ใช่ไฟล์สแกนหรือพิมพ์เป็นรูปภาพ แล้วค่อยอัปโหลดใหม่';

    public static function matches(string $message): bool
    {
        return str_contains($message, 'ไฟล์นี้เป็น PDF แบบภาพ');
    }
}
