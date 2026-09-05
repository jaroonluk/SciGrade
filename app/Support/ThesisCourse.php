<?php

namespace App\Support;

use App\Models\PdCourse;
use Illuminate\Database\Eloquent\Builder;

class ThesisCourse
{
    public const EXAM_BLOCK_MESSAGE = 'รายวิชา THESIS / DISSERTATION / INDEPENDENT STUDY ใช้ได้เฉพาะเมนูส่งผลการเรียนวิทยานิพนธ์/การศึกษาอิสระ';

    public static function isThesisTitle(?string $name): bool
    {
        $normalized = strtoupper(preg_replace('/\s+/', ' ', trim((string) $name)) ?? '');
        if ($normalized === '') {
            return false;
        }

        if (str_contains($normalized, 'DISSERTATION') || str_contains($normalized, 'INDEPENDENT STUDY')) {
            return true;
        }

        return (bool) preg_match('/(?<![A-Z])THESIS(?![A-Z])/', $normalized);
    }

    public static function isThesisSubject(?string $code, ?string $name = null): bool
    {
        if (self::isThesisTitle($name)) {
            return true;
        }

        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return false;
        }

        $row = PdCourse::query()
            ->whereRaw('UPPER(TRIM(subjcode)) = ?', [$code])
            ->first();

        return $row !== null && self::isThesisTitle($row->subjname ?? '');
    }

    public static function constrainExamReportable(Builder $query, string $column): Builder
    {
        return $query->where(function (Builder $q) use ($column) {
            $q->whereNull($column)
                ->orWhere($column, '')
                ->orWhere(function (Builder $inner) use ($column) {
                    $inner->whereRaw('UPPER('.$column.') NOT LIKE ?', ['%DISSERTATION%'])
                        ->whereRaw('UPPER('.$column.') NOT LIKE ?', ['%INDEPENDENT STUDY%'])
                        ->whereRaw('UPPER('.$column.') NOT REGEXP ?', ['(^|[^A-Z])THESIS([^A-Z]|$)']);
                });
        });
    }

    public static function constrainThesisOnly(Builder $query, string $column): Builder
    {
        return $query->where(function (Builder $q) use ($column) {
            $q->whereRaw('UPPER('.$column.') LIKE ?', ['%DISSERTATION%'])
                ->orWhereRaw('UPPER('.$column.') LIKE ?', ['%INDEPENDENT STUDY%'])
                ->orWhereRaw('UPPER('.$column.') REGEXP ?', ['(^|[^A-Z])THESIS([^A-Z]|$)']);
        });
    }

    public static function courseKind(?string $name): ?string
    {
        $normalized = strtoupper(preg_replace('/\s+/', ' ', trim((string) $name)) ?? '');
        if ($normalized === '') {
            return null;
        }

        if (str_contains($normalized, 'DISSERTATION')) {
            return 'dissertation';
        }

        if (str_contains($normalized, 'INDEPENDENT STUDY')) {
            return 'independent_study';
        }

        if (preg_match('/(?<![A-Z])THESIS(?![A-Z])/', $normalized)) {
            return 'thesis';
        }

        return null;
    }

    public static function courseKindLabel(?string $kind): string
    {
        return match ($kind) {
            'dissertation' => 'DISSERTATION',
            'independent_study' => 'INDEPENDENT STUDY',
            'thesis' => 'THESIS',
            default => 'วิทยานิพนธ์ / การศึกษาอิสระ',
        };
    }
}
