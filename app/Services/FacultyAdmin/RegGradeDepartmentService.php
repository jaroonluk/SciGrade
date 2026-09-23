<?php

namespace App\Services\FacultyAdmin;

use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Models\GradeReportReg;
use App\Models\DepartmentSubjectPattern;
use App\Models\TblDepartment;
use App\Models\TblUser;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use App\Support\SubjectDegree;
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
     * @param  list<int>|null  $allowedDepartmentIds
     * @return LengthAwarePaginator<int, object>
     */
    public function groupedCoursesPaginated(
        int $term,
        int $year,
        ?int $departmentId = null,
        string $q = '',
        int $perPage = 40,
        ?array $allowedDepartmentIds = null,
        ?string $educationLevel = null,
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
            $this->applyDepartmentFilter($query, $departmentId, $allowedDepartmentIds, $educationLevel);
        }
        $this->applySearchFilter($query, $q);
        $this->applyEducationLevelToCourseCodeQuery($query, $educationLevel);

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
            $educationLevel,
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
        ?string $educationLevel = null,
    ): Collection {
        return $this->groupedCoursesPaginated(
            $term,
            $year,
            $departmentId,
            $q,
            100000,
            $allowedDepartmentIds,
            $educationLevel,
        )->getCollection();
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
        ?string $educationLevel = null,
    ): Collection {
        $courses = $this->groupedCourses($term, $year, $departmentId, '', $allowedDepartmentIds, $educationLevel);

        $reports = $this->departmentGradeReports($term, $year, $departmentId, $allowedDepartmentIds, $educationLevel);

        [$reportsBySection] = $this->indexReports($reports);

        $usedPairs = [];
        $rows = collect();

        foreach ($courses as $course) {
            $key = $this->courseSectionKey((string) $course->COURSECODE, $course->SECTION);
            $matches = $reportsBySection[$key] ?? [];

            if ($matches === []) {
                $matches = [null];
            }

            foreach ($matches as $report) {
                $rows->push($this->statusRowFromCourse($course, $report, $course->SECTION));
                if ($report) {
                    $usedPairs[(int) $report->grade_id.'|'.$key] = true;
                }
            }
        }

        foreach ($reports as $report) {
            foreach ($report->gradeStds as $std) {
                $key = $this->courseSectionKey((string) $report->subject_code, $std->sec);
                $pair = (int) $report->grade_id.'|'.$key;
                if (isset($usedPairs[$pair])) {
                    continue;
                }

                $usedPairs[$pair] = true;
                $rows->push($this->statusRowFromReport($report, (string) $std->sec, $term, $year));
            }
        }

        $sectionCounts = $rows
            ->groupBy(fn (object $course) => strtoupper(trim((string) $course->COURSECODE)))
            ->map(fn (Collection $group) => $group
                ->map(fn (object $item) => (int) $item->SECTION)
                ->unique()
                ->count());

        $sorted = $rows
            ->map(function (object $course) use ($sectionCounts) {
                $code = strtoupper(trim((string) $course->COURSECODE));
                $sectionCount = (int) ($sectionCounts[$code] ?? 1);
                $course->section_count = $sectionCount;
                $course->has_multi_section = $sectionCount > 1;

                return $course;
            })
            ->sortBy(fn (object $course) => sprintf(
                '%s|%05d|%010d',
                strtoupper(trim((string) $course->COURSECODE)),
                (int) $course->SECTION,
                (int) ($course->grade_id ?? 0),
            ))
            ->values();

        return $this->attachCourseExamFiles(
            $this->attachCourseStatusControls($this->markDuplicateCourseSectionRows($sorted)),
            $reports,
        );
    }

    /**
     * รายงานรหัสวิชา+ภาค+ปีเดียวกัน (ใช้ตอนเปลี่ยนสถานะทั้งวิชา)
     *
     * @return Collection<int, GradeReport>
     */
    public function siblingReports(GradeReport $report): Collection
    {
        $code = strtoupper(trim((string) $report->subject_code));

        if ($code === '') {
            return collect([$report]);
        }

        $reports = GradeReport::query()
            ->where('term', (string) $report->term)
            ->where('year', (string) $report->year)
            ->whereRaw('UPPER(TRIM(subject_code)) = ?', [$code])
            ->orderBy('grade_id')
            ->get();

        if (! $reports->contains(fn (GradeReport $item) => (int) $item->grade_id === (int) $report->grade_id)) {
            $reports->prepend($report);
        }

        return $reports;
    }

    /**
     * ไฟล์ มข.11 / REG ที่ติดกลุ่มเรียนนี้เท่านั้น — ไม่ยืมไฟล์ Section อื่น และไม่ใส่ใบขวาง
     *
     * @return Collection<int, object>
     */
    public function attachedFilesForSection(GradeReport $report, string|int|null $section): Collection
    {
        $sectionInt = (int) $section;

        if (! $report->relationLoaded('files')) {
            $report->load(['files' => fn ($q) => $q->orderByDesc('file_id')]);
        }

        if (! $report->relationLoaded('gradeStds')) {
            $report->load(['gradeStds' => fn ($q) => $q->orderBy('sec')]);
        }

        return $report->files
            ->filter(function (GradeReportFile $file) use ($report, $sectionInt) {
                if ($file->resolvedType() === GradeReportFile::TYPE_EXAM_REPORT) {
                    return false;
                }

                $resolved = $file->resolvedSection($report);

                return $resolved !== null && (int) $resolved === $sectionInt;
            })
            ->sortBy(fn (GradeReportFile $file) => (int) $file->file_id)
            ->map(fn (GradeReportFile $file) => $this->formatAttachedFile($file, $report))
            ->values();
    }

    /**
     * ใบขวางทั้งหมดของรายงานในวิชานี้ เรียงตาม file_id
     *
     * @param  Collection<int, GradeReport>  $reports
     * @return Collection<int, object>
     */
    public function examFilesForReports(Collection $reports): Collection
    {
        $files = collect();

        foreach ($reports as $report) {
            if (! $report instanceof GradeReport) {
                continue;
            }
            if (! $report->relationLoaded('files')) {
                $report->load(['files' => fn ($q) => $q->orderBy('file_id')]);
            }

            foreach ($report->files as $file) {
                if ($file->resolvedType() !== GradeReportFile::TYPE_EXAM_REPORT) {
                    continue;
                }
                $files->push($this->formatAttachedFile($file, $report));
            }
        }

        return $files
            ->unique('file_id')
            ->sortBy(fn (object $file) => (int) $file->file_id)
            ->values()
            ->map(function (object $file, int $index) {
                $file->type_label = GradeReportFile::examReportLabel($index + 1);

                return $file;
            });
    }

    private function examSubmissionOrder(GradeReportFile $file, GradeReport $report): int
    {
        $ids = $report->files
            ->filter(fn (GradeReportFile $row) => $row->resolvedType() === GradeReportFile::TYPE_EXAM_REPORT)
            ->sortBy(fn (GradeReportFile $row) => (int) $row->file_id)
            ->pluck('file_id')
            ->values();

        $index = $ids->search($file->file_id);

        return $index === false ? 1 : (int) $index + 1;
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
        ?string $educationLevel = null,
    ): Collection {
        $query = GradeReport::query()
            ->examReportable()
            ->with([
                'gradeStds' => fn ($q) => $q->orderBy('sec'),
                'files' => fn ($q) => $q->orderByDesc('file_id'),
            ])
            ->where('term', (string) $term)
            ->where('year', (string) $year)
            ->whereHas('gradeStds');

        $allowed = $allowedDepartmentIds ?? self::DEPARTMENT_IDS;
        $allowed = array_values(array_unique(array_map('intval', $allowed)));
        $patternLevel = DepartmentSubjectPattern::fromReportFilter($educationLevel);

        if ($departmentId) {
            if (! in_array($departmentId, $allowed, true)) {
                return collect();
            }
            $this->subjectFilter->applyToQuery($query, $departmentId, $patternLevel);
        } else {
            if ($allowed === []) {
                return collect();
            }
            $this->subjectFilter->applyDepartmentsToQuery($query, $allowed, $patternLevel);
        }

        $this->applyEducationLevelToGradeReportQuery($query, $educationLevel);

        return $query->get();
    }

    /**
     * @param  list<int>|null  $allowedDepartmentIds
     */
    private function applyDepartmentFilter(
        Builder $query,
        ?int $departmentId,
        ?array $allowedDepartmentIds = null,
        ?string $educationLevel = null,
    ): void {
        $allowed = $allowedDepartmentIds ?? self::DEPARTMENT_IDS;
        $allowed = array_values(array_unique(array_map('intval', $allowed)));
        $patternLevel = DepartmentSubjectPattern::fromReportFilter($educationLevel);

        if ($allowed === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($departmentId) {
            if (! in_array($departmentId, $allowed, true)) {
                $query->whereRaw('1 = 0');

                return;
            }
            $this->subjectFilter->applyCourseCodeToQuery($query, $departmentId, 'COURSECODE', $patternLevel);

            return;
        }

        $this->subjectFilter->applyCourseCodeDepartmentsToQuery($query, $allowed, 'COURSECODE', $patternLevel);
    }

    private function applyEducationLevelToGradeReportQuery(Builder $query, ?string $level): void
    {
        if ($level === null || $level === '' || $level === 'all') {
            return;
        }

        $degrees = match ($level) {
            'bachelor' => [0, SubjectDegree::BACHELOR],
            'master' => [SubjectDegree::MASTER],
            'doctoral' => [SubjectDegree::DOCTORAL],
            'graduate' => [SubjectDegree::MASTER, SubjectDegree::DOCTORAL],
            default => [],
        };

        if ($degrees !== []) {
            $query->whereIn('degree', $degrees);
        }
    }

    /**
     * กรองรหัสวิชา REG ตามหลักระดับในรหัส (เหมือน SubjectDegree)
     */
    private function applyEducationLevelToCourseCodeQuery(Builder $query, ?string $level): void
    {
        if ($level === null || $level === '' || $level === 'all') {
            return;
        }

        // รูปแบบทั่วไป: ตัวอักษร + ตัวเลข โดยหลักที่ 3 ของตัวเลข = ระดับ (1-4 ตรี, 5-6 โท, 7-9 เอก)
        match ($level) {
            'bachelor' => $query->where(function (Builder $q): void {
                $q->whereRaw("COURSECODE REGEXP '^[A-Za-z]{2,}[0-9]{2}[1-4]'")
                    ->orWhereRaw("COURSECODE REGEXP '^[A-Za-z][0-9][1-4]'");
            }),
            'master' => $query->where(function (Builder $q): void {
                $q->whereRaw("COURSECODE REGEXP '^[A-Za-z]{2,}[0-9]{2}[5-6]'")
                    ->orWhereRaw("COURSECODE REGEXP '^[A-Za-z][0-9][5-6]'");
            }),
            'doctoral' => $query->where(function (Builder $q): void {
                $q->whereRaw("COURSECODE REGEXP '^[A-Za-z]{2,}[0-9]{2}[7-9]'")
                    ->orWhereRaw("COURSECODE REGEXP '^[A-Za-z][0-9][7-9]'");
            }),
            'graduate' => $query->where(function (Builder $q): void {
                $q->whereRaw("COURSECODE REGEXP '^[A-Za-z]{2,}[0-9]{2}[5-9]'")
                    ->orWhereRaw("COURSECODE REGEXP '^[A-Za-z][0-9][5-9]'");
            }),
            default => null,
        };
    }

    /**
     * @param  Collection<int, GradeReport>  $reports
     * @return array{0: array<string, list<GradeReport>>, 1: array<string, list<GradeReport>>}
     */
    private function indexReports(Collection $reports): array
    {
        $bySection = [];
        $byCourse = [];

        foreach ($reports as $report) {
            $courseCode = strtoupper(trim((string) $report->subject_code));
            if ($courseCode !== '') {
                $byCourse[$courseCode][(int) $report->grade_id] = $report;
            }

            foreach ($report->gradeStds as $std) {
                $key = $this->courseSectionKey((string) $report->subject_code, $std->sec);
                $bySection[$key][(int) $report->grade_id] = $report;
            }
        }

        return [
            $this->sortIndexedReports($bySection),
            $this->sortIndexedReports($byCourse),
        ];
    }

    /**
     * @param  array<string, array<int, GradeReport>>  $index
     * @return array<string, list<GradeReport>>
     */
    private function sortIndexedReports(array $index): array
    {
        foreach ($index as $key => $map) {
            ksort($map);
            $index[$key] = array_values($map);
        }

        return $index;
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    public function markDuplicateCourseSectionRows(Collection $rows): Collection
    {
        $counts = $rows
            ->groupBy(fn (object $row) => $this->courseSectionKey((string) $row->COURSECODE, $row->SECTION))
            ->map(fn (Collection $group) => $group->count());

        return $rows->map(function (object $row) use ($counts) {
            $key = $this->courseSectionKey((string) $row->COURSECODE, $row->SECTION);
            $count = (int) ($counts[$key] ?? 1);
            $row->duplicate_count = $count;
            $row->is_duplicate_entry = $count > 1;

            return $row;
        })->values();
    }

    private function courseSectionKey(string $courseCode, string|int|null $section): string
    {
        return strtoupper(trim($courseCode)).'|'.(string) ((int) $section);
    }

    private function statusRowFromCourse(object $course, ?GradeReport $report, string|int|null $section = null): object
    {
        $status = 0;
        $gradeId = null;
        $fileId = null;
        $fileName = null;
        $approv = null;
        $attachedFiles = collect();

        if ($report) {
            [$status, $gradeId, $fileId, $fileName, $approv, $attachedFiles] = $this->statusFieldsFromReport(
                $report,
                $section ?? $course->SECTION,
            );
        }

        return (object) [
            'COURSECODE' => $course->COURSECODE,
            'COURSENAMEENG' => $course->COURSENAMEENG,
            'SECTION' => $course->SECTION,
            'ACADYEAR' => $course->ACADYEAR,
            'SEMESTER' => $course->SEMESTER,
            'officers' => $report
                ? (trim((string) $report->teacher) ?: $course->officers)
                : $course->officers,
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
        [$status, $gradeId, $fileId, $fileName, $approv, $attachedFiles] = $this->statusFieldsFromReport($report, $section);

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
    private function statusFieldsFromReport(GradeReport $report, string|int|null $section = null): array
    {
        $approv = (int) $report->approv;
        // 0 ยังไม่ส่ง | 1 ส่งแล้ว | 2 นำเข้าที่ประชุมสาขา | 3 ผ่านที่ประชุมสาขา | 4 ผ่านคณะฯ
        $status = match (true) {
            $approv === 2 => 4,
            $approv === 1, $approv === 3 => 3,
            $approv === 4 => 2,
            $approv === -1 => 1,
            default => 1,
        };

        $attachedFiles = $this->attachedFilesForSection($report, $section);
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

    /**
     * @return object{file_id: int|null, grade_id: int|null, file_name: string|null, file_type: string, type_label: string}
     */
    private function formatAttachedFile(GradeReportFile $file, GradeReport $report): object
    {
        if ($file->resolvedType() === GradeReportFile::TYPE_EXAM_REPORT) {
            $typeLabel = GradeReportFile::examReportLabel($this->examSubmissionOrder($file, $report));
        } else {
            $baseLabel = $file->isDeptAdminUpload($report)
                ? 'ใบส่งผลการศึกษา (REG-Admin)'
                : 'ใบส่งผลการศึกษา (REG)';
            $typeLabel = $file->attachmentLinkLabel($baseLabel, $report);
        }

        return (object) [
            'file_id' => $file->file_id,
            'grade_id' => $file->grade_id ?: $report->grade_id,
            'file_name' => $file->original_name,
            'file_type' => $file->resolvedType(),
            'type_label' => $typeLabel,
        ];
    }

    /**
     * ใบขวางอยู่แถวแรกของวิชาที่มีรายงาน — ไม่กระจายไปทุก Section
     *
     * @param  Collection<int, object>  $rows
     * @param  Collection<int, GradeReport>  $reports
     * @return Collection<int, object>
     */
    private function attachCourseExamFiles(Collection $rows, Collection $reports): Collection
    {
        $examsByCourse = $reports
            ->groupBy(fn (GradeReport $report) => strtoupper(trim((string) $report->subject_code)))
            ->map(fn (Collection $courseReports) => $this->examFilesForReports($courseReports));

        $used = [];

        return $rows->map(function (object $row) use ($examsByCourse, &$used) {
            $code = strtoupper(trim((string) $row->COURSECODE));
            if (isset($used[$code]) || ! $row->grade_id) {
                return $row;
            }

            $used[$code] = true;
            $exams = $examsByCourse[$code] ?? collect();
            if ($exams->isEmpty()) {
                return $row;
            }

            $row->attached_files = $exams->concat(collect($row->attached_files ?? []))->values();
            $first = $row->attached_files->first();
            $row->file_id = $first?->file_id;
            $row->file_name = $first?->file_name;

            return $row;
        })->values();
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    private function attachCourseStatusControls(Collection $rows): Collection
    {
        $groups = $rows->groupBy(fn (object $row) => strtoupper(trim((string) $row->COURSECODE)));

        $prevCode = null;

        return $rows->map(function (object $row) use ($groups, &$prevCode) {
            $code = strtoupper(trim((string) $row->COURSECODE));
            $group = $groups->get($code, collect());
            $withReport = $group->first(fn (object $item) => $item->grade_id);

            $row->is_course_start = $prevCode !== $code;
            $row->course_grade_id = $withReport?->grade_id;
            // สาขาตั้งได้เฉพาะ 1/2/3 และกดครั้งเดียวมีผลทุก Section (ยกเว้นผ่านคณะฯ แล้ว)
            $row->course_can_queue_meeting = $group->contains(
                fn (object $item) => in_array((int) $item->status, [1, 3], true)
                    && $item->grade_id
                    && (int) ($item->approv ?? 0) !== -1
            );
            $row->course_can_pass_meeting = $group->contains(
                fn (object $item) => in_array((int) $item->status, [1, 2], true)
                    && $item->grade_id
                    && (int) ($item->approv ?? 0) !== -1
            );
            $row->course_can_approve_dept = $row->course_can_pass_meeting;
            $row->course_can_revert_dept = $group->contains(
                fn (object $item) => in_array((int) $item->status, [2, 3], true) && $item->grade_id
            );
            $row->course_can_approve_faculty = $group->contains(
                fn (object $item) => (int) $item->status === 3 && $item->grade_id
            );
            $row->course_can_revert_faculty = $group->contains(
                fn (object $item) => (int) $item->status === 4 && $item->grade_id
            );

            $prevCode = $code;

            return $row;
        })->values();
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
     * @param  list<int>|null  $allowedDepartmentIds
     * @return array<string, int>
     */
    private function sectionCountsForCourseCodes(
        array $courseCodes,
        int $term,
        int $year,
        ?int $departmentId,
        string $q,
        ?array $allowedDepartmentIds = null,
        ?string $educationLevel = null,
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
            $this->applyDepartmentFilter($query, $departmentId, $allowedDepartmentIds, $educationLevel);
        }
        $this->applySearchFilter($query, $q);
        $this->applyEducationLevelToCourseCodeQuery($query, $educationLevel);

        return $query
            ->groupBy('COURSECODE')
            ->pluck('section_count', 'COURSECODE')
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}
