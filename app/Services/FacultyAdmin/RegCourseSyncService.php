<?php

namespace App\Services\FacultyAdmin;

use App\Models\PdCourse;
use App\Support\Tis620Text;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
     *     failed: int,
     *     rows: list<array{subjcode: string, subjname: string, courseint: string, status: string, error?: string}>
     * }
     */
    public function sync(int $buddhistYear): array
    {
        $gregorianYear = $buddhistYear - 543;
        $courses = $this->fetchFromReg($gregorianYear);

        $rows = [];
        $inserted = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($courses as $course) {
            $code = Tis620Text::sanitize(trim((string) $course->COURSECODE));
            $name = Tis620Text::sanitize(trim((string) $course->COURSENAMEENG));
            $unit = Tis620Text::sanitize(trim((string) ($course->COURSEUNIT ?? '')));

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

            try {
                PdCourse::query()->create([
                    'subjcode' => $code,
                    'subjname' => $name !== '' ? $name : $code,
                ]);

                $inserted++;
                $rows[] = [
                    'subjcode' => $code,
                    'subjname' => $name,
                    'courseint' => $unit,
                    'status' => 'inserted',
                ];
            } catch (Throwable $e) {
                $failed++;
                Log::warning('pdcourse insert failed during REG sync', [
                    'subjcode' => $code,
                    'subjname' => $name,
                    'error' => $e->getMessage(),
                ]);
                $rows[] = [
                    'subjcode' => $code,
                    'subjname' => $name,
                    'courseint' => $unit,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'fetched' => $courses->count(),
            'inserted' => $inserted,
            'skipped' => $skipped,
            'failed' => $failed,
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
            // รวม SEMINAR, THESIS, DISSERTATION, INDEPENDENT STUDY ถ้ามีในปีนั้น
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
