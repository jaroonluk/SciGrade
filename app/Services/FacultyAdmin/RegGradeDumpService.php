<?php

namespace App\Services\FacultyAdmin;

use App\Models\GradeReportReg;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class RegGradeDumpService
{
    public const FACULTY_SCIENCE = 2;

    /**
     * ดึงรายวิชาจาก REG (รวม SEMINAR) แล้วทับข้อมูลเดิมใน grade_report_reg
     *
     * @return array{
     *     fetched: int,
     *     inserted: int,
     *     updated: int,
     *     skipped: int,
     *     sections_replaced: int,
     *     zero_enrollment: list<array{coursecode: string, coursenameeng: string, section: string, enrollseat: int}>,
     *     rows: list<array{coursecode: string, coursenameeng: string, section: string, officer: string, status: string, enrollseat: int}>
     * }
     */
    public function dump(int $buddhistYear, int $term): array
    {
        set_time_limit(300);

        $courses = $this->fetchFromReg($buddhistYear, $term);

        $grouped = $courses->groupBy(function (object $course): string {
            return strtoupper(trim((string) $course->COURSECODE)).'|'.trim((string) $course->SECTION);
        });

        $rows = [];
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $sectionsReplaced = 0;
        $zeroEnrollment = [];
        $insertPayloads = [];
        $sectionKeys = [];

        foreach ($grouped as $group) {
            $first = $group->first();
            $courseCode = strtoupper(trim((string) $first->COURSECODE));
            $section = trim((string) $first->SECTION);
            $courseName = trim((string) $first->COURSENAMEENG);
            $enrollSeat = (int) ($first->ENROLLSEAT ?? 0);

            if ($courseCode === '') {
                $skipped += $group->count();

                continue;
            }

            $sectionKeys[] = ['COURSECODE' => $courseCode, 'SECTION' => $section];
            $groupRows = [];

            foreach ($group as $course) {
                $officerId = trim((string) ($course->OFFICERID ?? ''));
                if ($officerId === '') {
                    $skipped++;

                    continue;
                }

                $payload = [
                    'COURSECODE' => $courseCode,
                    'COURSENAMEENG' => trim((string) $course->COURSENAMEENG),
                    'SECTION' => $section,
                    'ACADYEAR' => (string) $buddhistYear,
                    'SEMESTER' => (string) $term,
                    'LEVELID' => trim((string) ($course->LEVELID ?? '')),
                    'FACULTYID' => self::FACULTY_SCIENCE,
                    'OFFICERNAME' => trim((string) ($course->OFFICERNAME ?? '')),
                    'OFFICERSURNAME' => trim((string) ($course->OFFICERSURNAME ?? '')),
                    'KKUMAIL' => trim((string) ($course->KKUMAIL ?? '')),
                    'OFFICERID' => $officerId,
                ];

                $insertPayloads[] = $payload;
                $groupRows[] = [
                    'coursecode' => $payload['COURSECODE'],
                    'coursenameeng' => $payload['COURSENAMEENG'],
                    'section' => $payload['SECTION'],
                    'officer' => trim($payload['OFFICERNAME'].' '.$payload['OFFICERSURNAME']),
                    'enrollseat' => $enrollSeat,
                ];
            }

            if ($groupRows === []) {
                continue;
            }

            if ($enrollSeat <= 0) {
                $zeroEnrollment[] = [
                    'coursecode' => $courseCode,
                    'coursenameeng' => $courseName,
                    'section' => $section,
                    'enrollseat' => $enrollSeat,
                ];
            }

            foreach ($groupRows as $row) {
                $rows[] = $row;
            }
        }

        $year = (string) $buddhistYear;
        $semester = (string) $term;

        $existingKeys = GradeReportReg::query()
            ->where('ACADYEAR', $year)
            ->where('SEMESTER', $semester)
            ->get(['COURSECODE', 'SECTION'])
            ->mapWithKeys(fn ($row) => [
                strtoupper(trim((string) $row->COURSECODE)).'|'.trim((string) $row->SECTION) => true,
            ])
            ->all();

        DB::connection('scigrad')->transaction(function () use ($year, $semester, $sectionKeys, $insertPayloads): void {
            foreach (array_chunk($sectionKeys, 100) as $chunk) {
                GradeReportReg::query()
                    ->where('ACADYEAR', $year)
                    ->where('SEMESTER', $semester)
                    ->where(function ($q) use ($chunk): void {
                        foreach ($chunk as $pair) {
                            $q->orWhere(function ($inner) use ($pair): void {
                                $inner->where('COURSECODE', $pair['COURSECODE'])
                                    ->where('SECTION', $pair['SECTION']);
                            });
                        }
                    })
                    ->delete();
            }

            foreach (array_chunk($insertPayloads, 250) as $chunk) {
                GradeReportReg::query()->insert($chunk);
            }
        });

        $statusRows = [];
        $seenSections = [];
        foreach ($rows as $row) {
            $key = $row['coursecode'].'|'.$row['section'];
            $hadExisting = isset($existingKeys[$key]);
            if ($hadExisting) {
                $updated++;
                if (! isset($seenSections[$key])) {
                    $seenSections[$key] = true;
                    $sectionsReplaced++;
                }
            } else {
                $inserted++;
            }

            $statusRows[] = $row + ['status' => $hadExisting ? 'updated' : 'inserted'];
        }

        return [
            'fetched' => $courses->count(),
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'sections_replaced' => $sectionsReplaced,
            'zero_enrollment' => $zeroEnrollment,
            'rows' => $statusRows,
        ];
    }

    /**
     * ดึงจำนวนลงทะเบียนจาก REG ตามรหัสวิชา+Sec.
     *
     * @param  list<array{COURSECODE?: string, coursecode?: string, SECTION?: string, section?: string}|object>  $pairs
     * @return array<string, int> key = COURSECODE|SECTION
     */
    public function enrollmentSeatMap(int $buddhistYear, int $term, iterable $pairs): array
    {
        $codes = [];
        $wanted = [];
        foreach ($pairs as $pair) {
            $code = strtoupper(trim((string) (data_get($pair, 'COURSECODE') ?? data_get($pair, 'coursecode') ?? '')));
            $section = trim((string) (data_get($pair, 'SECTION') ?? data_get($pair, 'section') ?? ''));
            if ($code === '') {
                continue;
            }
            $codes[$code] = true;
            $wanted[$code.'|'.$section] = true;
        }

        if ($codes === []) {
            return [];
        }

        try {
            $rows = DB::connection('reg')
                ->table('class')
                ->join('course', 'class.COURSEID', '=', 'course.COURSEID')
                ->select([
                    'course.COURSECODE',
                    'class.SECTION',
                    'class.ENROLLSEAT',
                ])
                ->where('class.ACADYEAR', (string) $buddhistYear)
                ->where('class.SEMESTER', (string) $term)
                ->whereIn('course.COURSECODE', array_keys($codes))
                ->get();
        } catch (Throwable) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $key = strtoupper(trim((string) $row->COURSECODE)).'|'.trim((string) $row->SECTION);
            if (! isset($wanted[$key])) {
                continue;
            }
            $map[$key] = (int) ($row->ENROLLSEAT ?? 0);
        }

        // วิชาในระบบแต่ไม่พบใน REG = ถือว่าไม่มีผู้ลงทะเบียน
        foreach (array_keys($wanted) as $key) {
            if (! array_key_exists($key, $map)) {
                $map[$key] = 0;
            }
        }

        return $map;
    }

    /**
     * ตรวจสอบจำนวนลงทะเบียนจาก REG สำหรับรายวิชาที่อยู่ในระบบแล้ว
     *
     * @return list<array{coursecode: string, coursenameeng: string, section: string, enrollseat: int}>
     */
    public function zeroEnrollmentForStoredCourses(int $buddhistYear, int $term, ?int $departmentId = null): array
    {
        $query = GradeReportReg::query()
            ->selectRaw('COURSECODE, SECTION, MAX(COURSENAMEENG) as COURSENAMEENG')
            ->where('ACADYEAR', (string) $buddhistYear)
            ->where('SEMESTER', (string) $term)
            ->groupBy('COURSECODE', 'SECTION');

        if ($departmentId) {
            app(DepartmentSubjectFilter::class)->applyCourseCodeToQuery($query, $departmentId);
        }

        $stored = $query->get();
        if ($stored->isEmpty()) {
            return [];
        }

        $map = $this->enrollmentSeatMap($buddhistYear, $term, $stored);

        $zero = [];
        foreach ($stored as $row) {
            $key = strtoupper(trim((string) $row->COURSECODE)).'|'.trim((string) $row->SECTION);
            $enroll = (int) ($map[$key] ?? 0);
            if ($enroll > 0) {
                continue;
            }

            $zero[] = [
                'coursecode' => strtoupper(trim((string) $row->COURSECODE)),
                'coursenameeng' => (string) $row->COURSENAMEENG,
                'section' => trim((string) $row->SECTION),
                'enrollseat' => $enroll,
            ];
        }

        usort($zero, fn ($a, $b) => [$a['coursecode'], $a['section']] <=> [$b['coursecode'], $b['section']]);

        return $zero;
    }

    /**
     * @return Collection<int, object>
     */
    public function fetchFromReg(int $buddhistYear, int $term): Collection
    {
        return DB::connection('reg')
            ->table('class')
            ->join('classinstructor', 'class.CLASSID', '=', 'classinstructor.CLASSID')
            ->join('course', 'class.COURSEID', '=', 'course.COURSEID')
            ->join('officer', 'classinstructor.OFFICERID', '=', 'officer.OFFICERID')
            ->select([
                'course.COURSECODE',
                'course.COURSENAMEENG',
                'class.SECTION',
                'class.ACADYEAR',
                'class.SEMESTER',
                'class.LEVELID',
                'class.ENROLLSEAT',
                'officer.FACULTYID',
                'officer.OFFICERNAME',
                'officer.OFFICERSURNAME',
                'officer.KKUMAIL',
                'officer.OFFICEREMAIL',
                'officer.OFFICERID',
            ])
            ->where('officer.FACULTYID', (string) self::FACULTY_SCIENCE)
            ->where('class.ACADYEAR', (string) $buddhistYear)
            ->where('class.SEMESTER', (string) $term)
            // รวม SEMINAR ตามที่ร้องขอ — ยังไม่ดึงวิทยานิพนธ์/ค้นคว้าอิสระ
            ->where('course.COURSENAMEENG', 'not like', '%THESIS%')
            ->where('course.COURSENAMEENG', 'not like', '%INDEPENDENT STUDY%')
            ->where('course.COURSENAMEENG', 'not like', '%DISSERTATION%')
            ->orderBy('course.COURSECODE')
            ->orderBy('class.SECTION')
            ->get();
    }

    public function canConnect(): bool
    {
        try {
            DB::connection('reg')->select('select 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
