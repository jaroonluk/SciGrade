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
     *     pattern_details: list<array{pattern: string, label: string, kind: string}>
     * }>
     */
    public function departmentsWithPatterns(?string $q = null): Collection
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

        $patterns = DepartmentSubjectPattern::query()
            ->whereIn('department_id', $departmentIds->all())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('department_id');

        $rows = $departmentIds->map(function (int $departmentId) use ($departments, $patterns) {
            $dept = $departments->get($departmentId);
            $deptPatterns = $patterns->get($departmentId, collect());

            return (object) [
                'department_id' => $departmentId,
                'department_name' => $dept?->department_name ?? ('สาขา #'.$departmentId),
                'patterns' => $deptPatterns,
                'pattern_details' => $this->subjectFilter->patternDetailsForDepartment($departmentId),
            ];
        });

        $q = trim((string) $q);
        if ($q === '') {
            return $rows->values();
        }

        $like = mb_strtolower($q);

        return $rows->filter(function (object $row) use ($like) {
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

    public function store(int $departmentId, string $pattern): DepartmentSubjectPattern
    {
        $this->ensureSeeded();
        $pattern = $this->normalizePattern($pattern);
        $this->assertValidPattern($pattern);
        $this->assertUnique($departmentId, $pattern);

        $maxOrder = (int) DepartmentSubjectPattern::query()
            ->where('department_id', $departmentId)
            ->max('sort_order');

        return DepartmentSubjectPattern::query()->create([
            'department_id' => $departmentId,
            'pattern' => $pattern,
            'sort_order' => $maxOrder + 1,
        ]);
    }

    public function update(DepartmentSubjectPattern $row, string $pattern): DepartmentSubjectPattern
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

    public function restoreDefaults(int $departmentId): int
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
                $rows[] = [
                    'department_id' => $departmentId,
                    'pattern' => $pattern,
                    'sort_order' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
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
                $rows[] = [
                    'department_id' => $departmentId,
                    'pattern' => $pattern,
                    'sort_order' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
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
}
