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

    public const PROGRAM_TYPE_REGULAR = 'regular';

    public const PROGRAM_TYPE_SPECIAL = 'special';

    public const PROGRAM_TYPE_INTERNATIONAL = 'international';

    /** @var list<string> */
    public const PROGRAM_TYPE_ORDER = [
        self::PROGRAM_TYPE_REGULAR,
        self::PROGRAM_TYPE_SPECIAL,
        self::PROGRAM_TYPE_INTERNATIONAL,
    ];

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
     * จำแนกประเภทจาก LEVELID ในตาราง class ของ REG
     * 31, 51, 71 = ภาคปกติ | 34 = โครงการพิเศษ | 33, 35, 53, 73 = นานาชาติ
     */
    public static function classifyLevelId(int|string|null $levelId): ?string
    {
        $id = (int) $levelId;
        if ($id <= 0) {
            return null;
        }

        if (in_array($id, [31, 51, 71], true)) {
            return self::PROGRAM_TYPE_REGULAR;
        }

        if ($id === 34) {
            return self::PROGRAM_TYPE_SPECIAL;
        }

        if (in_array($id, [33, 35, 53, 73], true)) {
            return self::PROGRAM_TYPE_INTERNATIONAL;
        }

        return null;
    }

    /**
     * คีย์รหัสวิชา+กลุ่ม ให้ 1 และ 01 เป็นกลุ่มเดียวกัน
     */
    public static function courseSectionKey(string $courseCode, mixed $section): string
    {
        return strtoupper(trim($courseCode)).'|'.(string) ((int) $section);
    }

    /**
     * @return list<string>
     */
    public static function typesFromLevelIdList(string|array|null $levelIds): array
    {
        $values = is_array($levelIds)
            ? $levelIds
            : preg_split('/\s*,\s*/', trim((string) $levelIds), -1, PREG_SPLIT_NO_EMPTY);

        $collected = [];
        foreach ($values ?: [] as $levelId) {
            $type = self::classifyLevelId($levelId);
            if ($type !== null) {
                $collected[$type] = true;
            }
        }

        return self::orderedTypes($collected);
    }

    /**
     * @param  array<string, true>  $types
     * @return list<string>
     */
    public static function orderedTypes(array $types): array
    {
        $list = array_keys($types);
        $rank = array_flip(self::PROGRAM_TYPE_ORDER);
        usort($list, fn (string $a, string $b) => ($rank[$a] ?? 99) <=> ($rank[$b] ?? 99));

        return $list;
    }

    /**
     * จัดประเภทต่อกลุ่มจากแถว class — ไม่รวม LEVELID ข้าม Sec.
     *
     * @param  iterable<int, object>  $rows
     * @param  array<string, true>  $wantedKeys
     * @return array<string, list<string>>
     */
    public static function buildProgramTypeMapFromClassRows(iterable $rows, array $wantedKeys): array
    {
        $collected = [];
        foreach ($rows as $row) {
            $key = self::courseSectionKey(
                (string) ($row->COURSECODE ?? ''),
                $row->SECTION ?? null,
            );
            if ($key === '|' || ! isset($wantedKeys[$key])) {
                continue;
            }
            $type = self::classifyLevelId($row->LEVELID ?? null);
            if ($type === null) {
                continue;
            }
            $collected[$key][$type] = true;
        }

        $map = [];
        foreach ($collected as $key => $types) {
            $map[$key] = self::orderedTypes($types);
        }

        return $map;
    }

    /**
     * จำแนกประเภทหลักสูตรจากชื่อใน REG (ปกติ / โครงการพิเศษ / นานาชาติ)
     *
     * @return list<string>
     */
    public static function classifyProgramName(?string $nameTh, ?string $nameEn = null): array
    {
        $haystack = trim((string) $nameTh).' '.trim((string) $nameEn);
        $types = [];

        if (
            mb_stripos($haystack, 'นานาชาติ') !== false
            || stripos($haystack, 'International') !== false
        ) {
            $types[] = self::PROGRAM_TYPE_INTERNATIONAL;
        }

        if (mb_stripos($haystack, 'พิเศษ') !== false) {
            $types[] = self::PROGRAM_TYPE_SPECIAL;
        }

        if ($types === []) {
            $types[] = self::PROGRAM_TYPE_REGULAR;
        }

        return $types;
    }

    /**
     * ประเภทกลุ่มต่อรหัสวิชา+Sec. จาก class.LEVELID ของคณะวิทยาศาสตร์ (FACULTYID = 2)
     *
     * @param  list<array{COURSECODE?: string, coursecode?: string, SECTION?: string, section?: string}|object>  $pairs
     * @return array<string, list<string>> key = COURSECODE|SECTION
     */
    public function courseProgramTypeMap(int $buddhistYear, int $term, iterable $pairs): array
    {
        $codes = [];
        $wantedKeys = [];
        foreach ($pairs as $pair) {
            $code = strtoupper(trim((string) (data_get($pair, 'COURSECODE') ?? data_get($pair, 'coursecode') ?? '')));
            $section = data_get($pair, 'SECTION') ?? data_get($pair, 'section');
            if ($code === '') {
                continue;
            }
            $codes[$code] = true;
            $wantedKeys[self::courseSectionKey($code, $section)] = true;
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
                    'class.LEVELID',
                ])
                ->where(function ($query): void {
                    $query->where('class.FACULTYID', self::FACULTY_SCIENCE)
                        ->orWhere('class.FACULTYID', (string) self::FACULTY_SCIENCE);
                })
                ->where('class.ACADYEAR', (string) $buddhistYear)
                ->where('class.SEMESTER', (string) $term)
                ->whereIn('course.COURSECODE', array_keys($codes))
                ->get();
        } catch (Throwable) {
            return [];
        }

        return self::buildProgramTypeMapFromClassRows($rows, $wantedKeys);
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
