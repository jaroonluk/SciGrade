<?php

namespace App\Services\ThesisGrade;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RegStudentDirectory
{
    /**
     * @return array{
     *     student_code: string,
     *     name_prefix: string,
     *     first_name: string,
     *     last_name: string,
     *     student_name: string
     * }|null
     */
    public function findByStudentCode(string $code): ?array
    {
        $code = $this->normalizeCode($code);
        if ($code === '') {
            return null;
        }

        try {
            $row = DB::connection('reg')
                ->table('studentmaster as s')
                ->leftJoin('prefix as p', 'p.PREFIXID', '=', 's.PREFIXID')
                ->where('s.STUDENTCODE', $code)
                ->first([
                    's.STUDENTCODE',
                    's.STUDENTNAME',
                    's.STUDENTSURNAME',
                    's.STUDENTNAMEENG',
                    's.STUDENTSURNAMEENG',
                    'p.PREFIXNAME',
                    'p.PREFIXABB',
                    'p.PREFIXNAMEENG',
                ]);
        } catch (Throwable $e) {
            Log::debug('REG student lookup failed', ['code' => $code, 'error' => $e->getMessage()]);

            return null;
        }

        if ($row === null) {
            return null;
        }

        $first = trim((string) ($row->STUDENTNAME ?: $row->STUDENTNAMEENG ?: ''));
        $last = trim((string) ($row->STUDENTSURNAME ?: $row->STUDENTSURNAMEENG ?: ''));
        $prefix = trim((string) ($row->PREFIXNAME ?: $row->PREFIXABB ?: $row->PREFIXNAMEENG ?: ''));

        return [
            'student_code' => trim((string) $row->STUDENTCODE),
            'name_prefix' => $prefix,
            'first_name' => $first,
            'last_name' => $last,
            'student_name' => trim(preg_replace('/\s+/u', ' ', $prefix.' '.$first.' '.$last) ?? ''),
        ];
    }

    /**
     * แปลงรหัสจากใบ REG/PDF เช่น 88677020018-02 หรือ 11655020091-41
     * ให้เป็นรูปแบบ studentmaster เช่น 677020018-0 / 655020091-4
     */
    public function normalizeCode(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        // รูปแบบมาตรฐานอยู่แล้ว
        if (preg_match('/^(\d{9,10})-(\d)$/', $raw, $m)) {
            return $m[1].'-'.$m[2];
        }

        // จากใบ REG/PDF: S88677020018-02 / 11655020091-41 / 88677020018-02
        if (preg_match('/^(?:[SUIW])?(\d{9,14})-(\d{1,2})$/i', $raw, $m)) {
            $digits = $m[1];
            $suffix = $m[2];
            if (strlen($digits) > 11) {
                $digits = substr($digits, -11);
            }
            if (strlen($digits) === 11) {
                return substr($digits, 2, 9).'-'.substr($suffix, 0, 1);
            }
            if (strlen($digits) === 10) {
                return $digits.'-'.substr($suffix, 0, 1);
            }
            if (strlen($digits) === 9) {
                return $digits.'-'.substr($suffix, 0, 1);
            }
        }

        $digits = preg_replace('/\D/', '', $raw) ?? '';
        if (strlen($digits) === 11) {
            return substr($digits, 2, 9).'-0';
        }

        return $raw;
    }
}
