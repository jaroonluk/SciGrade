<?php

namespace App\Support;

class AcademicTerm
{
    public static function defaultYear(): int
    {
        $year = (int) date('Y') + 543;
        if ((int) date('m') <= 9) {
            $year--;
        }

        return $year;
    }

    public static function defaultTerm(): int
    {
        $month = (int) date('m');

        return match (true) {
            $month >= 10, $month <= 1 => 1,
            $month >= 2 && $month <= 6 => 2,
            default => 3,
        };
    }

    /**
     * @return list<int>
     */
    public static function yearOptions(int $from = 2565, int $to = 2575): array
    {
        return range($from, $to);
    }
}
