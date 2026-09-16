<?php

namespace App\Services\DeptAdmin;

use App\Models\DepartmentSubjectPattern;
use App\Models\GradeStd;
use App\Models\TblDepartment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class DepartmentSubjectFilter
{
    /** @var array<string, list<string>> */
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
    public function subjectMatchers(int $departmentId, ?string $educationLevel = null): array
    {
        $patterns = $this->patternsFor($departmentId, $educationLevel);

        if ($patterns === []) {
            return [];
        }

        return [
            function (Builder $query) use ($patterns): void {
                $query->where(function (Builder $inner) use ($patterns): void {
                    foreach ($patterns as $pattern) {
                        $like = str_contains($pattern, '%') ? $pattern : $pattern.'%';
                        $inner->orWhereRaw('subject_code LIKE ?', [$like]);
                    }
                });
            },
        ];
    }

    public function applyToQuery(Builder $query, int $departmentId, ?string $educationLevel = null): Builder
    {
        foreach ($this->subjectMatchers($departmentId, $educationLevel) as $matcher) {
            $matcher($query);
        }

        return $query;
    }

    /**
     * กรองคอลัมน์รหัสวิชาในตารางอื่น (เช่น grade_report_reg.COURSECODE)
     */
    public function applyCourseCodeToQuery(Builder $query, int $departmentId, string $column = 'COURSECODE', ?string $educationLevel = null): Builder
    {
        $patterns = $this->patternsFor($departmentId, $educationLevel);
        if ($patterns === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $inner) use ($patterns, $column): void {
            $wrapped = '`'.str_replace('`', '``', $column).'`';
            foreach ($patterns as $pattern) {
                $like = str_contains($pattern, '%') ? $pattern : $pattern.'%';
                $inner->orWhereRaw($wrapped.' LIKE ?', [$like]);
            }
        });
    }

    /**
     * @param  list<int>  $departmentIds
     */
    public function applyCourseCodeDepartmentsToQuery(Builder $query, array $departmentIds, string $column = 'COURSECODE', ?string $educationLevel = null): Builder
    {
        if ($departmentIds === []) {
            return $query;
        }

        return $query->where(function (Builder $outer) use ($departmentIds, $column, $educationLevel): void {
            foreach ($departmentIds as $departmentId) {
                $outer->orWhere(function (Builder $inner) use ($departmentId, $column, $educationLevel): void {
                    $this->applyCourseCodeToQuery($inner, $departmentId, $column, $educationLevel);
                });
            }
        });
    }

    /**
     * @return list<string>
     */
    public function patternsForDepartment(int $departmentId, ?string $educationLevel = null): array
    {
        return $this->patternsFor($departmentId, $educationLevel);
    }

    /**
     * @return list<array{pattern: string, label: string, kind: string}>
     */
    public function patternDetailsForDepartment(int $departmentId, ?string $educationLevel = null): array
    {
        return array_map(function (string $pattern): array {
            return [
                'pattern' => $pattern,
                'label' => $this->describePattern($pattern),
                'kind' => $this->patternKind($pattern),
            ];
        }, $this->patternsFor($departmentId, $educationLevel));
    }

    public function courseMatchesDepartment(string $courseCode, int $departmentId, ?string $educationLevel = null): bool
    {
        $code = strtoupper(trim($courseCode));
        if ($code === '') {
            return false;
        }

        foreach ($this->patternsFor($departmentId, $educationLevel) as $pattern) {
            $regex = '/^'.str_replace('%', '.*', preg_quote($pattern, '/')).'$/i';
            if (preg_match($regex, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<int>
     */
    public function departmentIdsMatchingSubject(string $subjectCode): array
    {
        $code = strtoupper(trim($subjectCode));
        if ($code === '') {
            return [];
        }

        $ids = array_keys(self::defaultPatternsMap());

        try {
            if (Schema::connection('scigrad')->hasTable('department_subject_pattern')) {
                $fromDb = DepartmentSubjectPattern::query()
                    ->distinct()
                    ->pluck('department_id')
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn (int $id) => $id > 0)
                    ->all();
                $ids = array_values(array_unique(array_merge($ids, $fromDb)));
            }
        } catch (\Throwable) {
            // keep defaults
        }

        $matched = [];
        foreach ($ids as $departmentId) {
            if ($this->courseMatchesDepartment($code, (int) $departmentId)) {
                $matched[] = (int) $departmentId;
            }
        }

        return array_values(array_unique($matched));
    }

    public function departmentNameForSubject(string $subjectCode): ?string
    {
        $ids = $this->departmentIdsMatchingSubject($subjectCode);
        if ($ids === []) {
            return null;
        }

        try {
            $name = TblDepartment::query()
                ->whereIn('department_id', $ids)
                ->orderBy('department_id')
                ->value('department_name');
        } catch (\Throwable) {
            return null;
        }

        $name = trim((string) $name);

        return $name !== '' ? $name : null;
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
    private function patternsFor(int $departmentId, ?string $educationLevel = null): array
    {
        $level = $educationLevel !== null
            ? DepartmentSubjectPattern::normalizeEducationLevel($educationLevel)
            : null;
        $cacheKey = $departmentId.'|'.($level ?? '*');

        if (isset($this->runtimeCache[$cacheKey])) {
            return $this->runtimeCache[$cacheKey];
        }

        $patterns = $this->loadPatterns($departmentId, $level);
        $this->runtimeCache[$cacheKey] = $patterns;

        return $patterns;
    }

    /**
     * @return list<string>
     */
    private function loadPatterns(int $departmentId, ?string $educationLevel = null): array
    {
        try {
            if (Schema::connection('scigrad')->hasTable('department_subject_pattern')) {
                // ไม่แบ่งระดับการศึกษา — ใช้เงื่อนไขทั้งหมดของสาขา (ตัดซ้ำ)
                $fromDb = DepartmentSubjectPattern::query()
                    ->where('department_id', $departmentId)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->pluck('pattern')
                    ->map(fn ($pattern) => strtoupper(trim((string) $pattern)))
                    ->filter()
                    ->unique()
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
    public function applyDepartmentsToQuery(Builder $query, array $departmentIds, ?string $educationLevel = null): Builder
    {
        if ($departmentIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $outer) use ($departmentIds, $educationLevel): void {
            foreach ($departmentIds as $departmentId) {
                $outer->orWhere(function (Builder $inner) use ($departmentId, $educationLevel): void {
                    $this->applyToQuery($inner, $departmentId, $educationLevel);
                });
            }
        });
    }

    /**
     * กรองใบรายงานผลการสอบ (ใบขวาง) ตามสาขา:
     * - สาขาทั่วไป: รหัสวิชาตาม department-patterns และต้องมีอาจารย์ในสาขานั้นกรอก
     * - งานบริการการศึกษา: ใช้เฉพาะรหัสวิชาตาม patterns ไม่เช็กผู้กรอก
     *
     * @param  list<int>  $departmentIds
     */
    public function applyDepartmentsExamReportsToQuery(Builder $query, array $departmentIds, ?string $educationLevel = null): Builder
    {
        if ($departmentIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $outer) use ($departmentIds, $educationLevel): void {
            foreach ($departmentIds as $departmentId) {
                $outer->orWhere(function (Builder $inner) use ($departmentId, $educationLevel): void {
                    $this->applyToQuery($inner, $departmentId, $educationLevel);
                    if (! $this->isEducationServicesDepartment($departmentId)) {
                        $this->applyFilledByDepartmentInstructors($inner, $departmentId);
                    }
                });
            }
        });
    }

    public static function isEducationServicesName(?string $departmentName): bool
    {
        return is_string($departmentName)
            && str_contains($departmentName, 'งานบริการการศึกษา');
    }

    public function isEducationServicesDepartment(int $departmentId): bool
    {
        static $cache = [];

        if (array_key_exists($departmentId, $cache)) {
            return $cache[$departmentId];
        }

        try {
            $name = TblDepartment::query()
                ->where('department_id', $departmentId)
                ->value('department_name');

            $cache[$departmentId] = self::isEducationServicesName(
                is_string($name) ? $name : null
            );
        } catch (\Throwable) {
            // fallback รหัสเดิมของงานบริการการศึกษา เมื่ออ่านชื่อหน่วยงานไม่ได้
            $cache[$departmentId] = $departmentId === 25;
        }

        return $cache[$departmentId];
    }

    /**
     * รายงานที่กรอกโดยอาจารย์ในสาขา (เจ้าของรายงาน / Section / ไฟล์แนบ)
     */
    public function applyFilledByDepartmentInstructors(Builder $query, int $departmentId): Builder
    {
        $usernamesInDepartment = function ($sub) use ($departmentId): void {
            $sub->select('username')
                ->from('tbluser')
                ->where('department_id', $departmentId);
        };

        return $query->where(function (Builder $outer) use ($usernamesInDepartment): void {
            $outer->whereIn('username', $usernamesInDepartment)
                ->orWhereHas('files', function (Builder $files) use ($usernamesInDepartment): void {
                    $files->whereIn('username', $usernamesInDepartment);
                });

            if (GradeStd::hasUsernameColumn()) {
                $outer->orWhereHas('gradeStds', function (Builder $stds) use ($usernamesInDepartment): void {
                    $stds->whereIn('username', $usernamesInDepartment);
                });
            }
        });
    }
}
