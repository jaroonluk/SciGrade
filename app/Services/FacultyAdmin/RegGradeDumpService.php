<?php

namespace App\Services\FacultyAdmin;

use App\Models\GradeReportReg;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class RegGradeDumpService
{
    public const FACULTY_SCIENCE = 2;

    /**
     * ดึงรายวิชาที่เปิดสอนจาก REG (อาจารย์คณะวิทยาศาสตร์) แล้ว upsert เข้า grade_report_reg
     *
     * @return array{
     *     fetched: int,
     *     inserted: int,
     *     updated: int,
     *     skipped: int,
     *     rows: list<array{coursecode: string, coursenameeng: string, section: string, officer: string, status: string}>
     * }
     */
    public function dump(int $buddhistYear, int $term): array
    {
        $courses = $this->fetchFromReg($buddhistYear, $term);

        $rows = [];
        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($courses as $course) {
            $officerId = trim((string) ($course->OFFICERID ?? ''));
            if ($officerId === '') {
                $skipped++;

                continue;
            }

            $payload = [
                'COURSECODE' => trim((string) $course->COURSECODE),
                'COURSENAMEENG' => trim((string) $course->COURSENAMEENG),
                'SECTION' => trim((string) $course->SECTION),
                'ACADYEAR' => (string) $buddhistYear,
                'SEMESTER' => (string) $term,
                'LEVELID' => trim((string) ($course->LEVELID ?? '')),
                'FACULTYID' => self::FACULTY_SCIENCE,
                'OFFICERNAME' => trim((string) ($course->OFFICERNAME ?? '')),
                'OFFICERSURNAME' => trim((string) ($course->OFFICERSURNAME ?? '')),
                'KKUMAIL' => trim((string) ($course->KKUMAIL ?? '')),
                'OFFICERID' => $officerId,
            ];

            if ($payload['COURSECODE'] === '') {
                $skipped++;

                continue;
            }

            $status = $this->upsert($payload);
            if ($status === 'inserted') {
                $inserted++;
            } elseif ($status === 'updated') {
                $updated++;
            } else {
                $skipped++;

                continue;
            }

            $rows[] = [
                'coursecode' => $payload['COURSECODE'],
                'coursenameeng' => $payload['COURSENAMEENG'],
                'section' => $payload['SECTION'],
                'officer' => trim($payload['OFFICERNAME'].' '.$payload['OFFICERSURNAME']),
                'status' => $status,
            ];
        }

        return [
            'fetched' => $courses->count(),
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'rows' => $rows,
        ];
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
            ->where('course.COURSENAMEENG', 'not like', '%SEMINAR%')
            ->where('course.COURSENAMEENG', 'not like', '%THESIS%')
            ->where('course.COURSENAMEENG', 'not like', '%INDEPENDENT STUDY%')
            ->where('course.COURSENAMEENG', 'not like', '%DISSERTATION%')
            ->orderBy('course.COURSECODE')
            ->orderBy('class.SECTION')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsert(array $payload): string
    {
        $existing = GradeReportReg::query()
            ->where('COURSECODE', $payload['COURSECODE'])
            ->where('ACADYEAR', $payload['ACADYEAR'])
            ->where('SEMESTER', $payload['SEMESTER'])
            ->where('SECTION', $payload['SECTION'])
            ->where('OFFICERID', $payload['OFFICERID'])
            ->first();

        if ($existing) {
            $updated = GradeReportReg::query()
                ->where('COURSECODE', $payload['COURSECODE'])
                ->where('ACADYEAR', $payload['ACADYEAR'])
                ->where('SEMESTER', $payload['SEMESTER'])
                ->where('SECTION', $payload['SECTION'])
                ->where('OFFICERID', $payload['OFFICERID'])
                ->update([
                    'COURSENAMEENG' => $payload['COURSENAMEENG'],
                    'LEVELID' => $payload['LEVELID'],
                    'OFFICERNAME' => $payload['OFFICERNAME'],
                    'OFFICERSURNAME' => $payload['OFFICERSURNAME'],
                    'KKUMAIL' => $payload['KKUMAIL'],
                ]);

            return $updated > 0 ? 'updated' : 'unchanged';
        }

        GradeReportReg::query()->create($payload);

        return 'inserted';
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
