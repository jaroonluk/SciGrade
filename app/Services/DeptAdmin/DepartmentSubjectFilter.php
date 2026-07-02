<?php

namespace App\Services\DeptAdmin;

use Illuminate\Database\Eloquent\Builder;

class DepartmentSubjectFilter
{
    /**
     * @return list<callable(Builder): void>
     */
    public function subjectMatchers(int $departmentId): array
    {
        $patterns = match ($departmentId) {
            4 => ['320%', '322%', '324%', '342%', '340%', '%SC3%'],
            5 => ['316%', '326%', '336%', '%SC6%'],
            6 => ['312%', '313%', '343%', '332%', '%SC2%'],
            7 => ['315%', '301%', '%SC5%'],
            8 => ['311%', '331%', '%SC1%'],
            9 => ['317%', '327%', '%SC7%', 'SC7%'],
            10 => ['314%', '321%', '323%', '333%', '%SC4%'],
            11 => ['318%', '%SC8%'],
            12 => ['319%', '%SC9%'],
            17 => ['SC0%', '300%'],
            22 => ['SC0%', '300%'],
            25 => ['3007%', '302%', 'SC01%', 'SC02%', '3003%', 'SC057%', 'SC068%', 'SC069%',
                'SC002002', 'SC002003', 'SC002004', 'SC002005', 'SC002006', 'SC002007',
                'SC017891', 'SC017892', 'SC057701', 'SC057702', 'SC057703', 'SC057721',
                'SC057722', 'SC057723', 'SC057725', 'SC057729', 'SC057733', 'SC017898',
                'SC017899', 'SC057738'],
            31 => ['SC017891', 'SC017892', 'SC057701', 'SC057702', 'SC057703', 'SC057721',
                'SC057722', 'SC057723', 'SC057725', 'SC057729', 'SC057733', 'SC017898', 'SC017899'],
            34 => ['SC0%', '300%'],
            35 => ['SC027701', 'SC028891', 'SC028892', 'SC028894', 'SC028898', 'SC028899',
                'SC029990', 'SC029992', 'SC029996'],
            36 => ['300302', '300304', '300305', '300306', 'SC002001'],
            default => [],
        };

        if ($patterns === []) {
            return [];
        }

        return [
            function (Builder $query) use ($patterns): void {
                $query->where(function (Builder $inner) use ($patterns): void {
                    foreach ($patterns as $pattern) {
                        if (str_contains($pattern, '%')) {
                            $inner->orWhere('subject_code', 'like', $pattern);
                        } else {
                            $inner->orWhere('subject_code', 'like', $pattern.'%');
                        }
                    }
                });
            },
        ];
    }

    public function applyToQuery(Builder $query, int $departmentId): Builder
    {
        foreach ($this->subjectMatchers($departmentId) as $matcher) {
            $matcher($query);
        }

        return $query;
    }

    /**
     * @param  list<int>  $departmentIds
     */
    public function applyDepartmentsToQuery(Builder $query, array $departmentIds): Builder
    {
        if ($departmentIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $outer) use ($departmentIds): void {
            foreach ($departmentIds as $departmentId) {
                $outer->orWhere(function (Builder $inner) use ($departmentId): void {
                    $this->applyToQuery($inner, $departmentId);
                });
            }
        });
    }
}
