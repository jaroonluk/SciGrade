<?php

namespace App\Services\DeptAdmin;

use App\Models\DepartmentSubjectPattern;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class DepartmentSubjectFilter
{
    /** @var array<int, list<string>> */
    private array $runtimeCache = [];

    /**
     * @return array<int, list<string>>
     */
    public static function defaultPatternsMap(): array
    {
        return [
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
        ];
    }

    /**
     * @return list<callable(Builder): void>
     */
    public function subjectMatchers(int $departmentId): array
    {
        $patterns = $this->patternsFor($departmentId);

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
     * กรองคอลัมน์รหัสวิชาในตารางอื่น (เช่น grade_report_reg.COURSECODE)
     */
    public function applyCourseCodeToQuery(Builder $query, int $departmentId, string $column = 'COURSECODE'): Builder
    {
        $patterns = $this->patternsFor($departmentId);
        if ($patterns === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $inner) use ($patterns, $column): void {
            foreach ($patterns as $pattern) {
                if (str_contains($pattern, '%')) {
                    $inner->orWhere($column, 'like', $pattern);
                } else {
                    $inner->orWhere($column, 'like', $pattern.'%');
                }
            }
        });
    }

    /**
     * @param  list<int>  $departmentIds
     */
    public function applyCourseCodeDepartmentsToQuery(Builder $query, array $departmentIds, string $column = 'COURSECODE'): Builder
    {
        if ($departmentIds === []) {
            return $query;
        }

        return $query->where(function (Builder $outer) use ($departmentIds, $column): void {
            foreach ($departmentIds as $departmentId) {
                $outer->orWhere(function (Builder $inner) use ($departmentId, $column): void {
                    $this->applyCourseCodeToQuery($inner, $departmentId, $column);
                });
            }
        });
    }

    /**
     * @return list<string>
     */
    public function patternsForDepartment(int $departmentId): array
    {
        return $this->patternsFor($departmentId);
    }

    /**
     * @return list<array{pattern: string, label: string, kind: string}>
     */
    public function patternDetailsForDepartment(int $departmentId): array
    {
        return array_map(function (string $pattern): array {
            return [
                'pattern' => $pattern,
                'label' => $this->describePattern($pattern),
                'kind' => $this->patternKind($pattern),
            ];
        }, $this->patternsFor($departmentId));
    }

    public function courseMatchesDepartment(string $courseCode, int $departmentId): bool
    {
        $code = strtoupper(trim($courseCode));
        if ($code === '') {
            return false;
        }

        foreach ($this->patternsFor($departmentId) as $pattern) {
            $regex = '/^'.str_replace('%', '.*', preg_quote($pattern, '/')).'$/i';
            if (preg_match($regex, $code)) {
                return true;
            }
        }

        return false;
    }

    public function describePattern(string $pattern): string
    {
        if (! str_contains($pattern, '%')) {
            return 'รหัสตรงกับ '.$pattern;
        }

        if (str_starts_with($pattern, '%') && str_ends_with($pattern, '%')) {
            return 'มี “'.trim($pattern, '%').'” ในรหัส';
        }

        if (str_ends_with($pattern, '%') && ! str_starts_with($pattern, '%')) {
            return 'ขึ้นต้นด้วย '.rtrim($pattern, '%');
        }

        if (str_starts_with($pattern, '%')) {
            return 'ลงท้ายด้วย '.ltrim($pattern, '%');
        }

        return $pattern;
    }

    public function patternKind(string $pattern): string
    {
        if (! str_contains($pattern, '%')) {
            return 'exact';
        }

        if (str_starts_with($pattern, '%') && str_ends_with($pattern, '%')) {
            return 'contains';
        }

        if (str_ends_with($pattern, '%')) {
            return 'prefix';
        }

        return 'suffix';
    }

    /**
     * @return list<string>
     */
    private function patternsFor(int $departmentId): array
    {
        if (isset($this->runtimeCache[$departmentId])) {
            return $this->runtimeCache[$departmentId];
        }

        $patterns = $this->loadPatterns($departmentId);
        $this->runtimeCache[$departmentId] = $patterns;

        return $patterns;
    }

    /**
     * @return list<string>
     */
    private function loadPatterns(int $departmentId): array
    {
        try {
            if (Schema::connection('scigrad')->hasTable('department_subject_pattern')) {
                $fromDb = DepartmentSubjectPattern::query()
                    ->where('department_id', $departmentId)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->pluck('pattern')
                    ->map(fn ($pattern) => (string) $pattern)
                    ->values()
                    ->all();

                // ถ้าตารางถูก seed แล้ว (มีข้อมูลอย่างน้อย 1 แถว) ให้ใช้ค่าจาก DB
                // แม้สาขานี้จะว่าง = ตั้งใจไม่ให้มีเงื่อนไข
                if (DepartmentSubjectPattern::query()->exists()) {
                    return $fromDb;
                }
            }
        } catch (\Throwable) {
            // fall back to defaults
        }

        return self::defaultPatternsMap()[$departmentId] ?? [];
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
