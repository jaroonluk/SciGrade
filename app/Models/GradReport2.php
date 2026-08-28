<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class GradReport2 extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'grad_report2';

    public $incrementing = false;

    protected $primaryKey = null;

    public $timestamps = true;

    protected $fillable = [
        'subject_code2',
        'subject_code',
        'subject',
        'username',
    ];

    /**
     * รหัสในตารางเดิมอาจมีช่องว่าง / CHAR padding ทำให้ where ตรงๆ หาไม่เจอ
     */
    public static function normalizeCode(string $code): string
    {
        $code = str_replace(["\u{00A0}", "\u{200B}", "\u{FEFF}"], '', trim($code));
        $code = preg_replace('/\s+/u', '', $code) ?? '';

        return strtoupper($code);
    }

    public static function normalizedCodeSql(string $column): string
    {
        self::assertCodeColumn($column);

        return "UPPER(REPLACE(REPLACE(TRIM(`{$column}`), ' ', ''), UNHEX('C2A0'), ''))";
    }

    public function scopeWhereNormalizedCode(Builder $query, string $column, string $code): Builder
    {
        $normalized = self::normalizeCode($code);
        if ($normalized === '') {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereRaw(self::normalizedCodeSql($column).' = ?', [$normalized]);
    }

    /**
     * @param  list<string>  $codes
     */
    public function scopeWhereNormalizedCodeIn(Builder $query, string $column, array $codes): Builder
    {
        $normalized = array_values(array_unique(array_filter(
            array_map(fn (string $code) => self::normalizeCode($code), $codes),
        )));

        if ($normalized === []) {
            return $query->whereRaw('0 = 1');
        }

        $placeholders = implode(',', array_fill(0, count($normalized), '?'));

        return $query->whereRaw(self::normalizedCodeSql($column).' IN ('.$placeholders.')', $normalized);
    }

    private static function assertCodeColumn(string $column): void
    {
        if (! in_array($column, ['subject_code', 'subject_code2'], true)) {
            throw new InvalidArgumentException('Invalid grad_report2 code column.');
        }
    }
}
