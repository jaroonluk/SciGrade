<?php

namespace App\Services\SuperAdmin;

use App\Models\DepartmentSubjectPattern;
use App\Models\TblDepartment;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DepartmentSubjectPatternService
{
    public function __construct(
        private readonly DepartmentSubjectFilter $subjectFilter,
    ) {}

    /**
     * @return Collection<int, object{
     *     department_id: int,
     *     department_name: string,
     *     patterns: Collection<int, DepartmentSubjectPattern>,
     *     pattern_details: list<array{pattern: string, label: string, kind: string}>,
     *     pattern_count: int
     * }>
     */
    public function departmentsWithPatterns(?string $q = null, ?string $viewFilter = null): Collection
    {
        $this->ensureSeeded();

        $departmentIds = collect($this->defaultPatterns())
            ->keys()
            ->merge(
                DepartmentSubjectPattern::query()->distinct()->pluck('department_id')
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();

        $departments = TblDepartment::query()
            ->whereIn('department_id', $departmentIds->all())
            ->orderBy('department_name')
            ->get()
            ->keyBy('department_id');

        $allPatterns = DepartmentSubjectPattern::query()
            ->whereIn('department_id', $departmentIds->all())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('department_id');

        $rows = $departmentIds->map(function (int $departmentId) use ($departments, $allPatterns) {
            $dept = $departments->get($departmentId);
            $patterns = $this->uniquePatterns($allPatterns->get($departmentId, collect()));

            return (object) [
                'department_id' => $departmentId,
                'department_name' => $dept?->department_name ?? ('สาขา #'.$departmentId),
                'patterns' => $patterns,
                'pattern_count' => $patterns->count(),
                'pattern_details' => $this->subjectFilter->patternDetailsForDepartment($departmentId),
            ];
        });

        $q = trim((string) $q);
        if ($q !== '') {
            $like = mb_strtolower($q);
            $rows = $rows->filter(function (object $row) use ($like) {
                if (str_contains(mb_strtolower($row->department_name), $like)) {
                    return true;
                }

                if (str_contains((string) $row->department_id, $like)) {
                    return true;
                }

                return $row->patterns->contains(
                    fn (DepartmentSubjectPattern $pattern) => str_contains(mb_strtolower($pattern->pattern), $like)
                );
            })->values();
        }

        return $rows->values();
    }

    /**
     * @deprecated ไม่ใช้แยกชั้นกรองแล้ว — คงไว้เพื่อความเข้ากันได้ของ controller เดิม
     */
    public function normalizeViewFilter(?string $value): string
    {
        return 'all';
    }

    public function store(int $departmentId, string $pattern, ?string $educationLevel = null): DepartmentSubjectPattern
    {
        $this->ensureSeeded();
        $pattern = $this->normalizePattern($pattern);
        $this->assertValidPattern($pattern);
        $this->assertUnique($departmentId, $pattern);

        $maxOrder = (int) DepartmentSubjectPattern::query()
            ->where('department_id', $departmentId)
            ->max('sort_order');

        $attributes = [
            'department_id' => $departmentId,
            'pattern' => $pattern,
            'sort_order' => $maxOrder + 1,
        ];
        if (DepartmentSubjectPattern::hasEducationLevelColumn()) {
            // คอลัมน์ยังมีใน DB แต่ไม่ใช้แยกกรองแล้ว — เก็บเป็น bachelor เป็นค่าเริ่มต้น
            $attributes['education_level'] = DepartmentSubjectPattern::EDUCATION_BACHELOR;
        }

        return DepartmentSubjectPattern::query()->create($attributes);
    }

    public function update(DepartmentSubjectPattern $row, string $pattern, ?string $educationLevel = null): DepartmentSubjectPattern
    {
        $pattern = $this->normalizePattern($pattern);
        $this->assertValidPattern($pattern);
        $this->assertUnique((int) $row->department_id, $pattern, (int) $row->id);

        $row->update(['pattern' => $pattern]);

        return $row->fresh();
    }

    public function destroy(DepartmentSubjectPattern $row): void
    {
        $row->delete();
    }

    public function restoreDefaults(int $departmentId, ?string $educationLevel = null): int
    {
        $this->ensureSeeded();

        $defaults = $this->defaultPatterns()[$departmentId] ?? null;
        if ($defaults === null) {
            throw ValidationException::withMessages([
                'department_id' => 'ไม่มีค่าเริ่มต้นสำหรับสาขานี้',
            ]);
        }

        return DB::connection('scigrad')->transaction(function () use ($departmentId, $defaults) {
            DepartmentSubjectPattern::query()->where('department_id', $departmentId)->delete();

            $now = now();
            $rows = [];
            foreach (array_values($defaults) as $index => $pattern) {
                $row = [
                    'department_id' => $departmentId,
                    'pattern' => $pattern,
                    'sort_order' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (DepartmentSubjectPattern::hasEducationLevelColumn()) {
                    $row['education_level'] = DepartmentSubjectPattern::EDUCATION_BACHELOR;
                }
                $rows[] = $row;
            }

            if ($rows !== []) {
                DB::connection('scigrad')->table('department_subject_pattern')->insert($rows);
            }

            return count($rows);
        });
    }

    public function ensureSeeded(): void
    {
        if (! Schema::connection('scigrad')->hasTable('department_subject_pattern')) {
            return;
        }

        if (DepartmentSubjectPattern::query()->exists()) {
            return;
        }

        $now = now();
        $rows = [];
        foreach ($this->defaultPatterns() as $departmentId => $patterns) {
            foreach (array_values($patterns) as $index => $pattern) {
                $row = [
                    'department_id' => $departmentId,
                    'pattern' => $pattern,
                    'sort_order' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (DepartmentSubjectPattern::hasEducationLevelColumn()) {
                    $row['education_level'] = DepartmentSubjectPattern::EDUCATION_BACHELOR;
                }
                $rows[] = $row;
            }
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::connection('scigrad')->table('department_subject_pattern')->insert($chunk);
        }
    }

    /**
     * @return array<int, list<string>>
     */
    public function defaultPatterns(): array
    {
        return DepartmentSubjectFilter::defaultPatternsMap();
    }

    private function normalizePattern(string $pattern): string
    {
        return strtoupper(trim($pattern));
    }

    private function assertValidPattern(string $pattern): void
    {
        if ($pattern === '') {
            throw ValidationException::withMessages([
                'pattern' => 'กรุณาระบุรหัส/เงื่อนไข',
            ]);
        }

        if (strlen($pattern) > 100) {
            throw ValidationException::withMessages([
                'pattern' => 'เงื่อนไขยาวเกินไป (สูงสุด 100 ตัวอักษร)',
            ]);
        }

        if (! preg_match('/^[A-Z0-9%]+$/', $pattern)) {
            throw ValidationException::withMessages([
                'pattern' => 'ใช้ได้เฉพาะตัวอักษร ตัวเลข และ % เท่านั้น เช่น 319% หรือ %SC9%',
            ]);
        }
    }

    private function assertUnique(int $departmentId, string $pattern, ?int $ignoreId = null): void
    {
        $query = DepartmentSubjectPattern::query()
            ->where('department_id', $departmentId)
            ->where('pattern', $pattern);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'pattern' => 'เงื่อนไขนี้มีอยู่แล้วในสาขานี้',
            ]);
        }
    }

    /**
     * รวมเงื่อนไขซ้ำข้ามระดับการศึกษาเดิม — คงแถวแรกของแต่ละ pattern
     *
     * @param  Collection<int, DepartmentSubjectPattern>  $patterns
     * @return Collection<int, DepartmentSubjectPattern>
     */
    private function uniquePatterns(Collection $patterns): Collection
    {
        $seen = [];
        $unique = collect();

        foreach ($patterns as $pattern) {
            $key = strtoupper(trim((string) $pattern->pattern));
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique->push($pattern);
        }

        return $unique->values();
    }
}
