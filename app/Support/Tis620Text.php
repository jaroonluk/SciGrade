<?php

namespace App\Support;

/**
 * eoffice.pdcourse (และตารางเก่าบางส่วน) ใช้ charset/collation tis620_thai_ci
 * ในขณะที่ Laravel เชื่อมด้วย utf8mb4 — ต้องตัดอักขระที่ TIS-620 ไม่รองรับก่อนบันทึก
 */
class Tis620Text
{
    /**
     * คืนค่าสตริง UTF-8 ที่ประกอบด้วยอักขระที่แปลงเป็น TIS-620 ได้เท่านั้น
     */
    public static function sanitize(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        // เครื่องหมายวรรคตอน Unicode ที่พบบ่อยในชื่อวิชา ENG จาก REG
        $value = strtr($value, [
            "\u{2018}" => "'", // ‘
            "\u{2019}" => "'", // ’
            "\u{201A}" => "'",
            "\u{201B}" => "'",
            "\u{2032}" => "'",
            "\u{201C}" => '"', // “
            "\u{201D}" => '"', // ”
            "\u{201E}" => '"',
            "\u{2033}" => '"',
            "\u{2013}" => '-', // –
            "\u{2014}" => '-', // —
            "\u{2212}" => '-',
            "\u{2026}" => '...',
            "\u{00A0}" => ' ',
            "\u{2022}" => '-',
            "\u{00B7}" => '-',
        ]);

        if (! function_exists('iconv')) {
            return preg_replace('/[^\x09\x0A\x0D\x20-\x7E\x{0E00}-\x{0E7F}]/u', '', $value) ?? $value;
        }

        $tis = @iconv('UTF-8', 'TIS-620//TRANSLIT//IGNORE', $value);
        if ($tis === false) {
            return preg_replace('/[^\x09\x0A\x0D\x20-\x7E\x{0E00}-\x{0E7F}]/u', '', $value) ?? $value;
        }

        $utf8 = @iconv('TIS-620', 'UTF-8', $tis);

        return $utf8 === false ? $value : $utf8;
    }
}
