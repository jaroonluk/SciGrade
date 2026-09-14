<?php

namespace App\Support;

use Carbon\CarbonInterface;

class ThaiDateTime
{
    /**
     * รูปแบบ: 1/ก.ค./2569 เวลา 13:53:34 น.
     */
    public static function formatPrintFooter(?CarbonInterface $dateTime = null): string
    {
        $dt = ($dateTime ?? now())->timezone('Asia/Bangkok');

        $months = [
            1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
            5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
            9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
        ];

        $day = (int) $dt->format('j');
        $month = $months[(int) $dt->format('n')];
        $year = (int) $dt->format('Y') + 543;
        $time = $dt->format('H:i:s');

        return "{$day}/{$month}/{$year} เวลา {$time} น.";
    }

    public static function formatShortDate(string $date): string
    {
        return self::formatDate($date);
    }

    /**
     * รูปแบบ: 14 กันยายน 2568
     */
    public static function formatLongDate(CarbonInterface|string|null $date = null): string
    {
        $dt = $date instanceof CarbonInterface
            ? $date->copy()->timezone('Asia/Bangkok')
            : ($date === null || $date === ''
                ? now()->timezone('Asia/Bangkok')
                : \Carbon\Carbon::parse($date, 'Asia/Bangkok'));

        $months = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
        ];

        return ((int) $dt->format('j')).' '.$months[(int) $dt->format('n')].' '.((int) $dt->format('Y') + 543);
    }

    /**
     * รูปแบบ: 1/ก.ค./2569
     */
    public static function formatDate(CarbonInterface|string|null $date): string
    {
        if ($date === null || $date === '') {
            return '-';
        }

        $dt = $date instanceof CarbonInterface
            ? $date->copy()->timezone('Asia/Bangkok')
            : \Carbon\Carbon::parse($date, 'Asia/Bangkok');

        $months = [
            1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
            5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
            9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
        ];

        $day = (int) $dt->format('j');
        $month = $months[(int) $dt->format('n')];
        $year = (int) $dt->format('Y') + 543;

        return "{$day}/{$month}/{$year}";
    }

    /**
     * รูปแบบ: 1/ก.ค./2569 13:53 น.
     */
    public static function formatDateTime(?CarbonInterface $dateTime): string
    {
        if ($dateTime === null) {
            return '-';
        }

        return self::formatDate($dateTime).' '.$dateTime->copy()->timezone('Asia/Bangkok')->format('H:i').' น.';
    }
}
