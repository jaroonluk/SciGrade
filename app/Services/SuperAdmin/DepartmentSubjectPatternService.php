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
     *     bachelor_patterns: Collection<int, DepartmentSubjectPattern>,
     *     graduate_patterns: Collection<int, DepartmentSubjectPattern>,
     *     bachelor_details: list<array{pattern: string, label: string, kind: string}>,
     *     graduate_details: list<array{pattern: string, label: string, kind: string}>,
     *     bachelor_count: int,
     *     graduate_count: int,
     *     patterns: Collection<int, DepartmentSubjectPattern>,
     *     pattern_details: list<array{pattern: string, label: string, kind: string}>
     * }>
     */
    public function departmentsWithPatterns(?string $q = null, ?string $viewFilter = null): Collection
    {
        $this->ensureSeeded();
        $viewFilter = $this->normalizeViewFilter($viewFilter);

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
            $deptPatterns = $allPatterns->get($departmentId, collect());
            $bachelor = $this->patternsForLevel($deptPatterns, DepartmentSubjectPattern::EDUCATION_BACHELOR);
            $graduate = $this->patternsForLevel($deptPatterns, DepartmentSubjectPattern::EDUCATION_GRADUATE);

            return (object) [
                'department_id' => $departmentId,
                'department_name' => $dept?->department_name ?? ('สาขา #'.$departmentId),
                'bachelor_patterns' => $bachelor,
                'graduate_patterns' => $graduate,
                'bachelor_count' => $bachelor->count(),
                'graduate_count' => $graduate->count(),
                'bachelor_details' => $this->subjectFilter->patternDetailsForDepartment(
                    $departmentId,
                    DepartmentSubjectPattern::EDUCATION_BACHELOR,
                ),
                'graduate_details' => $this->subjectFilter->patternDetailsForDepartment(
                    $departmentId,
                    DepartmentSubjectPattern::EDUCATION_GRADUATE,
                ),
                'patterns' => $bachelor,
                'pattern_details' => $this->subjectFilter->patternDetailsForDepartment(
                    $departmentId,
                    DepartmentSubjectPattern::EDUCATION_BACHELOR,
                ),
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

                $matchPattern = fn (DepartmentSubjectPattern $pattern) => str_contains(mb_strtolower($pattern->pattern), $like);

                return $row->bachelor_patterns->contains($matchPattern)
                    || $row->graduate_patterns->contains($matchPattern);
            })->values();
        }

        // เก็บ viewFilter ไว้ที่แต่ละแถวให้ view ใช้ซ่อน/โชว์แผง
        return $rows->map(function (object $row) use ($viewFilter) {
            $row->view_filter = $viewFilter;

            return $row;
        })->values();
    }

    /**
     * ตัวกรองหน้าจอ: all | bachelor | graduate
     */
    public function normalizeViewFilter(?string $value): string
    {
        return match (strtolower(trim((string) $value))) {
            DepartmentSubjectPattern::EDUCATION_BACHELOR => DepartmentSubjectPattern::EDUCATION_BACHELOR,
            DepartmentSubjectPattern::EDUCATION_GRADUATE => DepartmentSubjectPattern::EDUCATION_GRADUATE,
            default => 'all',
        };
    }

    public function store(int $departmentId, string $pattern, ?string $educationLevel = null): DepartmentSubjectPattern
    {
        $this->ensureSeeded();
        $educationLevel = DepartmentSubjectPattern::normalizeEducationLevel($educationLevel);
        $pattern = $this->normalizePattern($pattern);
        $this->assertValidPattern($pattern);
        $this->assertUnique($departmentId, $pattern, $educationLevel);

        $maxOrder = (int) DepartmentSubjectPattern::query()
            ->where('department_id', $departmentId)
            ->when(
                DepartmentSubjectPattern::hasEducationLevelColumn(),
                fn ($query) => $query->where('education_level', $educationLevel),
            )
            ->max('sort_order');

        $attributes = [
            'department_id' => $departmentId,
            'pattern' => $pattern,
            'sort_order' => $maxOrder + 1,
        ];
        if (DepartmentSubjectPattern::hasEducationLevelColumn()) {
            $attributes['education_level'] = $educationLevel;
        }

        return DepartmentSubjectPattern::query()->create($attributes);
    }

    public function update(DepartmentSubjectPattern $row, string $pattern, ?string $educationLevel = null): DepartmentSubjectPattern
    {
        $pattern = $this->normalizePattern($pattern);
        $this->assertValidPattern($pattern);
        $level = DepartmentSubjectPattern::hasEducationLevelColumn()
            ? DepartmentSubjectPattern::normalizeEducationLevel($educationLevel ?? $row->education_level)
            : DepartmentSubjectPattern::normalizeEducationLevel($educationLevel);
        $this->assertUnique((int) $row->department_id, $pattern, $level, (int) $row->id);

        $attributes = ['pattern' => $pattern];
        if (DepartmentSubjectPattern::hasEducationLevelColumn()) {
            $attributes['education_level'] = $level;
        }
        $row->update($attributes);

        return $row->fresh();
    }

    public function destroy(DepartmentSubjectPattern $row): void
    {
        $row->delete();
    }

    public function restoreDefaults(int $departmentId, ?string $educationLevel = null): int
    {
        $this->ensureSeeded();
        $educationLevel = DepartmentSubjectPattern::normalizeEducationLevel($educationLevel);

        $defaults = $this->defaultPatterns()[$departmentId] ?? null;
        if ($defaults === null) {
            throw ValidationException::withMessages([
                'department_id' => 'ไม่มีค่าเริ่มต้นสำหรับสาขานี้',
            ]);
        }

        return DB::connection('scigrad')->transaction(function () use ($departmentId, $defaults, $educationLevel) {
            $query = DepartmentSubjectPattern::query()->where('department_id', $departmentId);
            if (DepartmentSubjectPattern::hasEducationLevelColumn()) {
                $query->where('education_level', $educationLevel);
            }
            $query->delete();

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
                    $row['education_level'] = $educationLevel;
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
            foreach ([DepartmentSubjectPattern::EDUCATION_BACHELOR, DepartmentSubjectPattern::EDUCATION_GRADUATE] as $level) {
                foreach (array_values($patterns) as $index => $pattern) {
                    $row = [
                        'department_id' => $departmentId,
                        'pattern' => $pattern,
                        'sort_order' => $index + 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if (DepartmentSubjectPattern::hasEducationLevelColumn()) {
                        $row['education_level'] = $level;
                    }
                    $rows[] = $row;
                }
                if (! DepartmentSubjectPattern::hasEducationLevelColumn()) {
                    break;
                }
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

    private function assertUnique(int $departmentId, string $pattern, string $educationLevel, ?int $ignoreId = null): void
    {
        $query = DepartmentSubjectPattern::query()
            ->where('department_id', $departmentId)
            ->where('pattern', $pattern);

        if (DepartmentSubjectPattern::hasEducationLevelColumn()) {
            $query->where('education_level', $educationLevel);
        }

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'pattern' => 'เงื่อนไขนี้มีอยู่แล้วในสาขานี้สำหรับ'.DepartmentSubjectPattern::label($educationLevel),
            ]);
        }
    }

    /**
     * @param  Collection<int, DepartmentSubjectPattern>  $patterns
     * @return Collection<int, DepartmentSubjectPattern>
     */
    private function patternsForLevel(Collection $patterns, string $educationLevel): Collection
    {
        if (! DepartmentSubjectPattern::hasEducationLevelColumn()) {
            return $patterns->values();
        }

        return $patterns
            ->filter(fn (DepartmentSubjectPattern $pattern) => DepartmentSubjectPattern::normalizeEducationLevel($pattern->education_level) === $educationLevel)
            ->values();
    }
}
