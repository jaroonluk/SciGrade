<?php

namespace App\Services\FacultyAdmin;

use App\Models\GradeReport;
use App\Models\GradeReportReg;
use App\Models\TblDepartment;
use App\Models\TblUser;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RegGradeDepartmentService
{
    /** สาขาที่ใช้ในหน้าจัดการ/ตรวจสอบสถานะ REG (ตามระบบเดิม) */
    public const DEPARTMENT_IDS = [5, 6, 7, 8, 9, 10, 11, 12, 25, 36];

    public function __construct(
        private readonly DepartmentSubjectFilter $subjectFilter,
    ) {}

    /**
     * @return Collection<int, TblDepartment>
     */
    public function departments(): Collection
    {
        return TblDepartment::query()
            ->whereIn('department_id', self::DEPARTMENT_IDS)
            ->orderBy('department_name')
            ->get();
    }

    /**
     * รายวิชาใน grade_report_reg จัดกลุ่มตามรหัส+กลุ่ม พร้อมค้นหาและแบ่งหน้า
     *
     * @return LengthAwarePaginator<int, object>
     */
    public function groupedCoursesPaginated(
        int $term,
        int $year,
        ?int $departmentId = null,
        string $q = '',
        int $perPage = 40,
        ?array $allowedDepartmentIds = null,
    ): LengthAwarePaginator {
        $query = GradeReportReg::query()
            ->selectRaw('
                COURSECODE,
                SECTION,
                MAX(COURSENAMEENG) as COURSENAMEENG,
                MAX(ACADYEAR) as ACADYEAR,
                MAX(SEMESTER) as SEMESTER,
                COUNT(*) as officer_count,
                GROUP_CONCAT(DISTINCT TRIM(CONCAT(IFNULL(OFFICERNAME, ""), " ", IFNULL(OFFICERSURNAME, ""))) ORDER BY OFFICERNAME SEPARATOR ", ") as officers
            ')
            ->where('ACADYEAR', (string) $year)
            ->where('SEMESTER', (string) $term);

        $this->applyDepartmentFilter($query, $departmentId, $allowedDepartmentIds);
        $this->applySearchFilter($query, $q);

        $paginator = $query
            ->groupBy('COURSECODE', 'SECTION')
            ->orderBy('COURSECODE')
            ->orderBy('SECTION')
            ->paginate($perPage)
            ->withQueryString();

        $sectionCounts = $this->sectionCountsForCourseCodes(
            $paginator->getCollection()->pluck('COURSECODE')->unique()->all(),
            $term,
            $year,
            $departmentId,
            $q,
            $allowedDepartmentIds,
        );

        $paginator->setCollection(
            $paginator->getCollection()->map(function ($row) use ($sectionCounts) {
                $code = (string) $row->COURSECODE;
                $sectionCount = (int) ($sectionCounts[$code] ?? 1);
                $officers = trim((string) preg_replace('/\s+,/', ',', (string) ($row->officers ?? '')));

                return (object) [
                    'COURSECODE' => $code,
                    'COURSENAMEENG' => (string) $row->COURSENAMEENG,
                    'SECTION' => (string) $row->SECTION,
                    'ACADYEAR' => (string) $row->ACADYEAR,
                    'SEMESTER' => (string) $row->SEMESTER,
                    'officer_count' => (int) $row->officer_count,
                    'officers' => $officers,
                    'section_count' => $sectionCount,
                    'has_multi_section' => $sectionCount > 1,
                ];
            })
        );

        return $paginator;
    }

    /**
     * @param  list<int>|null  $allowedDepartmentIds
     * @return Collection<int, object>
     */
    public function groupedCourses(
        int $term,
        int $year,
        ?int $departmentId = null,
        string $q = '',
        ?array $allowedDepartmentIds = null,
    ): Collection {
        return $this->groupedCoursesPaginated($term, $year, $departmentId, $q, 100000, $allowedDepartmentIds)->getCollection();
    }

    /**
     * @return Collection<int, GradeReportReg>
     */
    public function sectionRows(string $courseCode, string $section, string $year, string $term): Collection
    {
        return GradeReportReg::query()
            ->where('COURSECODE', $courseCode)
            ->where('SECTION', $section)
            ->where('ACADYEAR', $year)
            ->where('SEMESTER', $term)
            ->orderBy('OFFICERNAME')
            ->get();
    }

    /**
     * @param  array{
     *     COURSECODE: string,
     *     COURSENAMEENG: string,
     *     SECTION: string,
     *     ACADYEAR: string,
     *     SEMESTER: string,
     *     OFFICERID: string,
     *     OFFICERNAME: string,
     *     OFFICERSURNAME: string,
     *     KKUMAIL?: string|null,
     *     LEVELID?: string|null,
     * }  $data
     */
    public function addCourse(array $data): GradeReportReg
    {
        $exists = GradeReportReg::query()
            ->where('COURSECODE', $data['COURSECODE'])
            ->where('SECTION', $data['SECTION'])
            ->where('ACADYEAR', $data['ACADYEAR'])
            ->where('SEMESTER', $data['SEMESTER'])
            ->where('OFFICERID', $data['OFFICERID'])
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException('รายวิชานี้ มี Sec. และอาจารย์ผู้สอนนี้อยู่แล้ว');
        }

        return GradeReportReg::query()->create([
            'COURSECODE' => $data['COURSECODE'],
            'COURSENAMEENG' => $data['COURSENAMEENG'],
            'SECTION' => $data['SECTION'],
            'ACADYEAR' => $data['ACADYEAR'],
            'SEMESTER' => $data['SEMESTER'],
            'LEVELID' => $data['LEVELID'] ?? '',
            'FACULTYID' => RegGradeDumpService::FACULTY_SCIENCE,
            'OFFICERNAME' => $data['OFFICERNAME'],
            'OFFICERSURNAME' => $data['OFFICERSURNAME'],
            'KKUMAIL' => $data['KKUMAIL'] ?? '',
            'OFFICERID' => $data['OFFICERID'],
        ]);
    }

    /**
     * @return Collection<int, array{officer_id: string, display_name: string, email: string, fname: string, lname: string}>
     */
    public function searchInstructors(string $q): Collection
    {
        $like = '%'.$q.'%';

        return TblUser::query()
            ->with('titleRelation')
            ->where(function ($query) use ($like) {
                $query->where('fname', 'like', $like)
                    ->orWhere('lname', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhere('userid', 'like', $like)
                    ->orWhere('OFFICERID', 'like', $like)
                    ->orWhere('email', 'like', $like);
            })
            ->orderBy('fname')
            ->orderBy('lname')
            ->limit(15)
            ->get()
            ->map(fn (TblUser $user) => [
                'officer_id' => (string) ($user->OFFICERID ?: $user->userid ?: $user->username),
                'username' => (string) $user->username,
                'display_name' => $user->displayName(),
                'fname' => (string) $user->fname,
                'lname' => (string) $user->lname,
                'email' => (string) ($user->email ?? ''),
            ]);
    }

    public function deleteSection(string $courseCode, string $section, string $year, string $term, ?string $officerId = null): int
    {
        $query = GradeReportReg::query()
            ->where('COURSECODE', $courseCode)
            ->where('SECTION', $section)
            ->where('ACADYEAR', $year)
            ->where('SEMESTER', $term);

        if ($officerId !== null && $officerId !== '') {
            $query->where('OFFICERID', $officerId);
        }

        return $query->delete();
    }

    public function updateCourseName(string $courseCode, string $section, string $year, string $term, string $courseNameEng): int
    {
        return GradeReportReg::query()
            ->where('COURSECODE', $courseCode)
            ->where('SECTION', $section)
            ->where('ACADYEAR', $year)
            ->where('SEMESTER', $term)
            ->update(['COURSENAMEENG' => $courseNameEng]);
    }

    /**
     * สถานะการส่งผลสอบเทียบกับ grade_report
     *
     * @return Collection<int, object>
     */
    /**
     * สถานะการส่งผลสอบเทียบกับ grade_report
     *
     * @param  list<int>|null  $allowedDepartmentIds
     * @return Collection<int, object>
     */
    public function coursesWithStatus(
        int $term,
        int $year,
        ?int $departmentId = null,
        ?array $allowedDepartmentIds = null,
    ): Collection {
        $courses = $this->groupedCourses($term, $year, $departmentId, '', $allowedDepartmentIds);

        $sectionCounts = $courses
            ->groupBy(fn (object $course) => (string) $course->COURSECODE)
            ->map(fn (Collection $group) => $group->count());

        $reports = GradeReport::query()
            ->with([
                'gradeStds' => fn ($q) => $q->orderBy('sec'),
                'files' => fn ($q) => $q->orderByDesc('file_id'),
            ])
            ->where('term', (string) $term)
            ->where('year', (string) $year)
            ->get();

        $reportIndex = [];
        foreach ($reports as $report) {
            foreach ($report->gradeStds as $std) {
                $key = strtoupper(trim($report->subject_code)).'|'.trim((string) $std->sec);
                $reportIndex[$key] = $report;
            }
        }

        return $courses->map(function (object $course) use ($reportIndex, $sectionCounts) {
            $key = strtoupper(trim($course->COURSECODE)).'|'.trim((string) $course->SECTION);
            $report = $reportIndex[$key] ?? null;

            $status = 0;
            $gradeId = null;
            $fileId = null;
            $fileName = null;
            $approv = null;

            if ($report) {
                $gradeId = $report->grade_id;
                $approv = (int) $report->approv;
                $status = match (true) {
                    $approv === 2 => 3,
                    $approv === 1 => 2,
                    $approv === -1 => 1,
                    default => 1,
                };

                $file = $report->files->first();
                if ($file) {
                    $fileId = $file->file_id;
                    $fileName = $file->original_name;
                }
            }

            $sectionCount = (int) ($sectionCounts[(string) $course->COURSECODE] ?? 1);

            return (object) [
                'COURSECODE' => $course->COURSECODE,
                'COURSENAMEENG' => $course->COURSENAMEENG,
                'SECTION' => $course->SECTION,
                'ACADYEAR' => $course->ACADYEAR,
                'SEMESTER' => $course->SEMESTER,
                'officers' => $course->officers,
                'status' => $status,
                'grade_id' => $gradeId,
                'file_id' => $fileId,
                'file_name' => $fileName,
                'approv' => $approv,
                'section_count' => $sectionCount,
                'has_multi_section' => $sectionCount > 1,
            ];
        });
    }

    /**
     * @param  list<int>|null  $allowedDepartmentIds
     */
    private function applyDepartmentFilter(Builder $query, ?int $departmentId, ?array $allowedDepartmentIds = null): void
    {
        $allowed = $allowedDepartmentIds ?? self::DEPARTMENT_IDS;
        $allowed = array_values(array_unique(array_map('intval', $allowed)));

        if ($allowed === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($departmentId) {
            if (! in_array($departmentId, $allowed, true)) {
                $query->whereRaw('1 = 0');

                return;
            }
            $this->subjectFilter->applyCourseCodeToQuery($query, $departmentId);

            return;
        }

        $this->subjectFilter->applyCourseCodeDepartmentsToQuery($query, $allowed);
    }

    private function applySearchFilter(Builder $query, string $q): void
    {
        $q = trim($q);
        if ($q === '') {
            return;
        }

        $like = '%'.$q.'%';
        $query->where(function (Builder $inner) use ($like) {
            $inner->where('COURSECODE', 'like', $like)
                ->orWhere('COURSENAMEENG', 'like', $like)
                ->orWhere('OFFICERNAME', 'like', $like)
                ->orWhere('OFFICERSURNAME', 'like', $like)
                ->orWhere('KKUMAIL', 'like', $like)
                ->orWhere('OFFICERID', 'like', $like)
                ->orWhereRaw("CONCAT(IFNULL(OFFICERNAME,''), ' ', IFNULL(OFFICERSURNAME,'')) LIKE ?", [$like]);
        });
    }

    /**
     * @param  list<string>  $courseCodes
     * @return array<string, int>
     */
    private function sectionCountsForCourseCodes(
        array $courseCodes,
        int $term,
        int $year,
        ?int $departmentId,
        string $q,
        ?array $allowedDepartmentIds = null,
    ): array {
        if ($courseCodes === []) {
            return [];
        }

        $query = GradeReportReg::query()
            ->selectRaw('COURSECODE, COUNT(DISTINCT SECTION) as section_count')
            ->where('ACADYEAR', (string) $year)
            ->where('SEMESTER', (string) $term)
            ->whereIn('COURSECODE', $courseCodes);

        $this->applyDepartmentFilter($query, $departmentId, $allowedDepartmentIds);
        $this->applySearchFilter($query, $q);

        return $query
            ->groupBy('COURSECODE')
            ->pluck('section_count', 'COURSECODE')
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}
