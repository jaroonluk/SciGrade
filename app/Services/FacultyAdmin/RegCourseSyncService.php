<?php

namespace App\Services\FacultyAdmin;

use App\Models\PdCourse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class RegCourseSyncService
{
    /**
     * ดึงรายวิชา SC จากฐาน REG ตามปีปฏิทิน (พ.ศ. ที่เลือก − 543)
     * แล้ว insert เฉพาะรหัสที่ยังไม่มีใน pdcourse
     *
     * @return array{
     *     fetched: int,
     *     inserted: int,
     *     skipped: int,
     *     rows: list<array{subjcode: string, subjname: string, courseint: string, status: string}>
     * }
     */
    public function sync(int $buddhistYear): array
    {
        $gregorianYear = $buddhistYear - 543;
        $courses = $this->fetchFromReg($gregorianYear);

        $rows = [];
        $inserted = 0;
        $skipped = 0;

        foreach ($courses as $course) {
            $code = trim((string) $course->COURSECODE);
            $name = trim((string) $course->COURSENAMEENG);
            $unit = trim((string) ($course->COURSEUNIT ?? ''));

            if ($code === '') {
                continue;
            }

            $exists = PdCourse::query()
                ->where('subjcode', $code)
                ->exists();

            if ($exists) {
                $skipped++;
                $rows[] = [
                    'subjcode' => $code,
                    'subjname' => $name,
                    'courseint' => $unit,
                    'status' => 'skipped',
                ];

                continue;
            }

            PdCourse::query()->create([
                'subjcode' => $code,
                'subjname' => $name,
                'courseint' => $unit,
            ]);

            $inserted++;
            $rows[] = [
                'subjcode' => $code,
                'subjname' => $name,
                'courseint' => $unit,
                'status' => 'inserted',
            ];
        }

        return [
            'fetched' => $courses->count(),
            'inserted' => $inserted,
            'skipped' => $skipped,
            'rows' => $rows,
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function fetchFromReg(int $gregorianYear): Collection
    {
        return DB::connection('reg')
            ->table('course')
            ->selectRaw('COURSECODE, MAX(COURSENAMEENG) as COURSENAMEENG, MAX(COURSEUNIT) as COURSEUNIT')
            ->where('CREATEDATETIME', 'like', '%'.$gregorianYear.'%')
            ->where('COURSECODE', 'like', '%SC%')
            ->where('COURSENAMEENG', 'not like', '%SEMINAR%')
            ->where('COURSENAMEENG', 'not like', '%THESIS%')
            ->where('COURSENAMEENG', 'not like', '%INDEPENDENT STUDY%')
            ->where('COURSENAMEENG', 'not like', '%DISSERTATION%')
            ->where('COURSENAMEENG', '!=', '')
            ->groupBy('COURSECODE')
            ->orderBy('COURSECODE')
            ->get();
    }

    public function canConnect(): bool
    {
        try {
            DB::connection('reg')->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
