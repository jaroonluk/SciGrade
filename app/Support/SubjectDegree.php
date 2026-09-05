<?php

namespace App\Support;

class SubjectDegree
{
    public const BACHELOR = 3;

    public const MASTER = 5;

    public const DOCTORAL = 7;

    /**
     * รหัสวิชา มข. รูปแบบตัวอักษรคณะ + ตัวเลขสาขา(2) + ระดับ(1) + ลำดับวิชา
     * ระดับ 1–4 = ปริญญาตรี, 5–6 = ปริญญาโท, 7–9 = ปริญญาเอก
     */
    public static function fromSubjectCode(string $subjectCode): int
    {
        $code = strtoupper(preg_replace('/\s+/', '', $subjectCode) ?? '');
        if ($code === '') {
            return self::BACHELOR;
        }

        $letters = preg_match('/^[A-Z]+/', $code, $match) === 1 ? $match[0] : '';
        $digits = preg_replace('/\D/', '', substr($code, strlen($letters))) ?? '';
        if ($digits === '') {
            return self::BACHELOR;
        }

        $levelIndex = strlen($letters) >= 2 && strlen($digits) >= 3 ? 2 : 0;
        $level = (int) $digits[$levelIndex];

        if ($level >= 7) {
            return self::DOCTORAL;
        }
        if ($level >= 5) {
            return self::MASTER;
        }

        return self::BACHELOR;
    }

    public static function isGraduate(string $subjectCode, ?int $knownDegree = null): bool
    {
        if (in_array($knownDegree, [self::MASTER, self::DOCTORAL], true)) {
            return true;
        }

        return self::fromSubjectCode($subjectCode) !== self::BACHELOR;
    }
}
