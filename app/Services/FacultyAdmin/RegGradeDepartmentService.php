<?php

namespace App\Services\FacultyAdmin;

use App\Models\GradeReport;
use App\Models\GradeReportFile;
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
            ->selectRaw("
                COURSECODE,
                SECTION,
                MAX(COURSENAMEENG) as COURSENAMEENG,
                MAX(ACADYEAR) as ACADYEAR,
                MAX(SEMESTER) as SEMESTER,
                COUNT(*) as officer_count,
                GROUP_CONCAT(DISTINCT TRIM(CONCAT(IFNULL(OFFICERNAME, ''), ' ', IFNULL(OFFICERSURNAME, ''))) ORDER BY OFFICERNAME SEPARATOR ', ') as officers
            ")
            ->where('ACADYEAR', (string) $year)
            ->where('SEMESTER', (string) $term);

        // เลือกสาขาแล้ว = กรองตามสาขาเสมอ
        // ไม่เลือกสาขาและไม่มีคำค้น = กรองรวมทุกสาขาตาม pattern
        // ไม่เลือกสาขาแต่มีคำค้น = ค้นทั้งภาค/ปี (รวมรหัสนอก pattern)
        if ($departmentId || trim($q) === '') {
            $this->applyDepartmentFilter($query, $departmentId, $allowedDepartmentIds);
        }
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

    /**
     * ลบหลายกลุ่มวิชา (COURSECODE + SECTION) ในภาค/ปีเดียวกัน
     *
     * @param  list<array{COURSECODE: string, SECTION: string}>  $items
     */
    public function deleteSections(array $items, string $year, string $term): int
    {
        $deleted = 0;

        foreach ($items as $item) {
            $courseCode = trim((string) ($item['COURSECODE'] ?? ''));
            $section = trim((string) ($item['SECTION'] ?? ''));
            if ($courseCode === '' || $section === '') {
                continue;
            }

            $deleted += $this->deleteSection($courseCode, $section, $year, $term);
        }

        return $deleted;
    }

    /**
     * ลบรายวิชาทั้งหมดตามเงื่อนไขกรองเดียวกับหน้ารายการ
     */
    public function deleteFilteredCourses(
        int $term,
        int $year,
        ?int $departmentId = null,
        string $q = '',
    ): int {
        $query = GradeReportReg::query()
            ->where('ACADYEAR', (string) $year)
            ->where('SEMESTER', (string) $term);

        if ($departmentId || trim($q) === '') {
            $this->applyDepartmentFilter($query, $departmentId);
        }
        $this->applySearchFilter($query, $q);

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
     * ฐานหลักจาก grade_report_reg และเติมรายวิชาที่มีใน grade_report
     * (เช่น หน้ารีวิว) แต่ยังไม่ถูก sync เข้า REG ให้ครบด้วย
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

        $reports = $this->departmentGradeReports($term, $year, $departmentId, $allowedDepartmentIds);

        $reportIndex = [];
        foreach ($reports as $report) {
            foreach ($report->gradeStds as $std) {
                $key = $this->courseSectionKey((string) $report->subject_code, $std->sec);
                if (! isset($reportIndex[$key]) || $this->approvalRank($report) > $this->approvalRank($reportIndex[$key])) {
                    $reportIndex[$key] = $report;
                }
            }
        }

        $rows = $courses->map(function (object $course) use ($reportIndex) {
            $key = $this->courseSectionKey((string) $course->COURSECODE, $course->SECTION);
            $report = $reportIndex[$key] ?? null;

            return $this->statusRowFromCourse($course, $report);
        });

        $existingKeys = $rows
            ->map(fn (object $row) => $this->courseSectionKey((string) $row->COURSECODE, $row->SECTION))
            ->flip();

        $extraRows = collect();
        foreach ($reports as $report) {
            foreach ($report->gradeStds as $std) {
                $key = $this->courseSectionKey((string) $report->subject_code, $std->sec);
                if (isset($existingKeys[$key])) {
                    continue;
                }

                $existingKeys[$key] = true;
                $extraRows->push($this->statusRowFromReport($report, (string) $std->sec, $term, $year));
            }
        }

        $merged = $rows->concat($extraRows)->values();

        $sectionCounts = $merged
            ->groupBy(fn (object $course) => strtoupper(trim((string) $course->COURSECODE)))
            ->map(fn (Collection $group) => $group->count());

        return $merged
            ->map(function (object $course) use ($sectionCounts) {
                $code = strtoupper(trim((string) $course->COURSECODE));
                $sectionCount = (int) ($sectionCounts[$code] ?? 1);
                $course->section_count = $sectionCount;
                $course->has_multi_section = $sectionCount > 1;

                return $course;
            })
            ->sortBy(fn (object $course) => sprintf(
                '%s|%05d',
                strtoupper(trim((string) $course->COURSECODE)),
                (int) $course->SECTION,
            ))
            ->values();
    }

    /**
     * @param  list<int>|null  $allowedDepartmentIds
     * @return Collection<int, GradeReport>
     */
    private function departmentGradeReports(
        int $term,
        int $year,
        ?int $departmentId,
        ?array $allowedDepartmentIds = null,
    ): Collection {
        $query = GradeReport::query()
            ->with([
                'gradeStds' => fn ($q) => $q->orderBy('sec'),
                'files' => fn ($q) => $q->orderByDesc('file_id'),
            ])
            ->where('term', (string) $term)
            ->where('year', (string) $year)
            ->whereHas('gradeStds');

        $allowed = $allowedDepartmentIds ?? self::DEPARTMENT_IDS;
        $allowed = array_values(array_unique(array_map('intval', $allowed)));

        if ($departmentId) {
            if (! in_array($departmentId, $allowed, true)) {
                return collect();
            }
            $this->subjectFilter->applyToQuery($query, $departmentId);
        } else {
            if ($allowed === []) {
                return collect();
            }
            $this->subjectFilter->applyDepartmentsToQuery($query, $allowed);
        }

        return $query->get();
    }

    private function courseSectionKey(string $courseCode, string|int|null $section): string
    {
        return strtoupper(trim($courseCode)).'|'.(string) ((int) $section);
    }

    private function statusRowFromCourse(object $course, ?GradeReport $report): object
    {
        $status = 0;
        $gradeId = null;
        $fileId = null;
        $fileName = null;
        $approv = null;
        $attachedFiles = collect();

        if ($report) {
            [$status, $gradeId, $fileId, $fileName, $approv, $attachedFiles] = $this->statusFieldsFromReport($report);
        }

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
            'attached_files' => $attachedFiles,
            'approv' => $approv,
            'section_count' => 1,
            'has_multi_section' => false,
        ];
    }

    private function statusRowFromReport(GradeReport $report, string $section, int $term, int $year): object
    {
        [$status, $gradeId, $fileId, $fileName, $approv, $attachedFiles] = $this->statusFieldsFromReport($report);

        return (object) [
            'COURSECODE' => strtoupper(trim((string) $report->subject_code)),
            'COURSENAMEENG' => (string) $report->subject,
            'SECTION' => $section,
            'ACADYEAR' => (string) $year,
            'SEMESTER' => (string) $term,
            'officers' => trim((string) $report->teacher),
            'status' => $status,
            'grade_id' => $gradeId,
            'file_id' => $fileId,
            'file_name' => $fileName,
            'attached_files' => $attachedFiles,
            'approv' => $approv,
            'section_count' => 1,
            'has_multi_section' => false,
        ];
    }

    /**
     * @return array{0: int, 1: int|null, 2: int|null, 3: string|null, 4: int|null, 5: Collection<int, object>}
     */
    private function statusFieldsFromReport(GradeReport $report): array
    {
        $approv = (int) $report->approv;
        $status = match (true) {
            $approv === 2 => 3,
            $approv === 1 => 2,
            $approv === -1 => 1,
            default => 1,
        };

        // แสดงไฟล์ล่าสุดของแต่ละประเภท (แบบรายงานผล / ใบส่งผล REG)
        $attachedFiles = collect(GradeReportFile::allowedTypes())
            ->map(function (string $type) use ($report) {
                $file = $report->files->first(
                    fn (GradeReportFile $candidate) => $candidate->resolvedType() === $type
                );

                if (! $file) {
                    return null;
                }

                return (object) [
                    'file_id' => $file->file_id,
                    'file_name' => $file->original_name,
                    'file_type' => $type,
                    'type_label' => $file->typeLabel(),
                ];
            })
            ->filter()
            ->values();

        $first = $attachedFiles->first();

        return [
            $status,
            $report->grade_id,
            $first?->file_id,
            $first?->file_name,
            $approv,
            $attachedFiles,
        ];
    }

    private function approvalRank(GradeReport $report): int
    {
        return match ((int) $report->approv) {
            2 => 3,
            1 => 2,
            default => 1,
        };
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

        if ($departmentId || trim($q) === '') {
            $this->applyDepartmentFilter($query, $departmentId, $allowedDepartmentIds);
        }
        $this->applySearchFilter($query, $q);

        return $query
            ->groupBy('COURSECODE')
            ->pluck('section_count', 'COURSECODE')
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}
