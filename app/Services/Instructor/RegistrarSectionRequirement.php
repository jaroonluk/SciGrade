<?php

namespace App\Services\Instructor;

/**
 * กำหนด Section ที่ต้องมีไฟล์ มข.11 ในรอบบันทึกปัจจุบัน
 *
 * เมื่อเพิ่ม Section ทีละกลุ่มในรายงานวิชาเดิม ต้องไม่บังคับแนบไฟล์ของ Section
 * ที่ผู้อื่น/รอบก่อนกรอกไว้แล้ว
 */
class RegistrarSectionRequirement
{
    /**
     * @param  list<int|string>  $reportSections
     * @param  list<int|string>  $requestedSections  Section ในฟอร์มรอบนี้
     * @param  list<int|string>  $pendingSections    Section ที่อัปโหลด มข.11 ใน session รอบนี้
     * @return list<int>
     */
    public static function needed(array $reportSections, array $requestedSections = [], array $pendingSections = []): array
    {
        $onReport = self::normalize($reportSections);
        $requested = self::normalize($requestedSections);
        $pending = self::normalize($pendingSections);

        if ($requested !== []) {
            $matched = array_values(array_intersect($requested, $onReport));

            return $matched !== [] ? $matched : $requested;
        }

        if ($pending !== []) {
            $matched = array_values(array_intersect($pending, $onReport));

            return $matched !== [] ? $matched : $pending;
        }

        return $onReport;
    }

    /**
     * @param  list<int|string>  $sections
     * @return list<int>
     */
    public static function normalize(array $sections): array
    {
        $out = [];
        foreach ($sections as $section) {
            $n = (int) $section;
            if ($n > 0) {
                $out[$n] = $n;
            }
        }

        $values = array_values($out);
        sort($values, SORT_NUMERIC);

        return $values;
    }
}
