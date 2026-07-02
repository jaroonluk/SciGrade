<?php

namespace App\Support;

use App\Models\GradeTerm;

class AcademicTerm
{
    private static bool $dbResolved = false;

    /** @var array{term: int, year: int}|null */
    private static ?array $dbDefaults = null;

    /**
     * @return array{term: int, year: int}
     */
    public static function defaults(): array
    {
        $fromDb = self::fromDatabase();
        if ($fromDb !== null) {
            return $fromDb;
        }

        return [
            'term' => self::termFromDate(),
            'year' => self::yearFromDate(),
        ];
    }

    public static function defaultYear(): int
    {
        return self::defaults()['year'];
    }

    public static function defaultTerm(): int
    {
        return self::defaults()['term'];
    }

    /**
     * @return list<int>
     */
    public static function yearOptions(int $from = 2565, int $to = 2575): array
    {
        return range($from, $to);
    }

    /**
     * @return array{term: int, year: int}|null
     */
    private static function fromDatabase(): ?array
    {
        if (self::$dbResolved) {
            return self::$dbDefaults;
        }

        self::$dbResolved = true;

        try {
            $row = GradeTerm::query()->orderBy('id')->first();
            if ($row !== null && $row->term !== '' && $row->year !== '') {
                self::$dbDefaults = [
                    'term' => (int) $row->term,
                    'year' => (int) $row->year,
                ];
            }
        } catch (\Throwable) {
            self::$dbDefaults = null;
        }

        return self::$dbDefaults;
    }

    private static function yearFromDate(): int
    {
        $year = (int) date('Y') + 543;
        if ((int) date('m') <= 9) {
            $year--;
        }

        return $year;
    }

    private static function termFromDate(): int
    {
        $month = (int) date('m');

        return match (true) {
            $month >= 10, $month <= 1 => 1,
            $month >= 2 && $month <= 6 => 2,
            default => 3,
        };
    }
}
