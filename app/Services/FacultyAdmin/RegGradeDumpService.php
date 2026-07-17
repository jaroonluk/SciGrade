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
        $zeroSeen = [];

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

            $hadExisting = GradeReportReg::query()
                ->where('COURSECODE', $courseCode)
                ->where('SECTION', $section)
                ->where('ACADYEAR', (string) $buddhistYear)
                ->where('SEMESTER', (string) $term)
                ->exists();

            // ทับข้อมูลเดิมทั้งกลุ่มวิชา+Sec.
            GradeReportReg::query()
                ->where('COURSECODE', $courseCode)
                ->where('SECTION', $section)
                ->where('ACADYEAR', (string) $buddhistYear)
                ->where('SEMESTER', (string) $term)
                ->delete();

            if ($hadExisting) {
                $sectionsReplaced++;
            }

            $groupInserted = 0;
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

                GradeReportReg::query()->create($payload);
                $groupInserted++;

                $status = $hadExisting ? 'updated' : 'inserted';
                if ($hadExisting) {
                    $updated++;
                } else {
                    $inserted++;
                }

                $rows[] = [
                    'coursecode' => $payload['COURSECODE'],
                    'coursenameeng' => $payload['COURSENAMEENG'],
                    'section' => $payload['SECTION'],
                    'officer' => trim($payload['OFFICERNAME'].' '.$payload['OFFICERSURNAME']),
                    'status' => $status,
                    'enrollseat' => $enrollSeat,
                ];
            }

            if ($groupInserted === 0) {
                continue;
            }

            if ($enrollSeat <= 0) {
                $key = $courseCode.'|'.$section;
                if (! isset($zeroSeen[$key])) {
                    $zeroSeen[$key] = true;
                    $zeroEnrollment[] = [
                        'coursecode' => $courseCode,
                        'coursenameeng' => $courseName,
                        'section' => $section,
                        'enrollseat' => $enrollSeat,
                    ];
                }
            }
        }

        return [
            'fetched' => $courses->count(),
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'sections_replaced' => $sectionsReplaced,
            'zero_enrollment' => $zeroEnrollment,
            'rows' => $rows,
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
            DB::connection('reg')->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
