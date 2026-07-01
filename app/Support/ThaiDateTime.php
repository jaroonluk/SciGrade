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
}
