<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use App\Services\FacultyAdmin\RegGradeDepartmentService;
use App\Services\FacultyAdmin\RegGradeDumpService;
use App\Support\AcademicTerm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class RegGradeManageController extends Controller
{
    public function __construct(
        private readonly RegGradeDepartmentService $service,
        private readonly DepartmentSubjectFilter $subjectFilter,
        private readonly RegGradeDumpService $dumpService,
    ) {}

    public function index(Request $request): View
    {
        [$term, $year, $departmentId, $q] = $this->filters($request);

        $courses = $this->service->groupedCoursesPaginated($term, $year, $departmentId, $q, 40);

        $selectedDepartment = null;
        $departmentPatterns = [];
        $outsidePatternCount = 0;
        $outsidePatternCodes = [];

        if ($departmentId) {
            $selectedDepartment = $this->service->departments()
                ->firstWhere('department_id', $departmentId);
            $departmentPatterns = $this->subjectFilter->patternDetailsForDepartment($departmentId);

            $outsidePatternCodes = [];
            if ($q !== '') {
                $outsidePatternCodes = $courses->getCollection()
                    ->filter(fn ($row) => ! $this->subjectFilter->courseMatchesDepartment(
                        (string) $row->COURSECODE,
                        $departmentId,
                    ))
                    ->pluck('COURSECODE')
                    ->unique()
                    ->values()
                    ->all();
                $outsidePatternCount = count($outsidePatternCodes);
            }
        }

        $canConnectReg = $this->dumpService->canConnect();
        $enrollmentMap = [];
        $programTypeMap = [];
        if ($canConnectReg && $courses->isNotEmpty()) {
            try {
                $enrollmentMap = $this->dumpService->enrollmentSeatMap($year, $term, $courses->items());
            } catch (Throwable) {
                $enrollmentMap = [];
            }

            try {
                $programTypeMap = $this->dumpService->courseProgramTypeMap(
                    $year,
                    $term,
                    $courses->items(),
                );
            } catch (Throwable) {
                $programTypeMap = [];
            }
        }

        $courses->setCollection(
            $courses->getCollection()->map(function (object $row) use ($enrollmentMap, $programTypeMap) {
                $code = strtoupper(trim((string) $row->COURSECODE));
                $key = $code.'|'.trim((string) $row->SECTION);
                $typeKey = RegGradeDumpService::courseSectionKey($code, $row->SECTION);
                $enroll = array_key_exists($key, $enrollmentMap)
                    ? (int) $enrollmentMap[$key]
                    : (array_key_exists($typeKey, $enrollmentMap) ? (int) $enrollmentMap[$typeKey] : null);
                $row->enrollseat = $enroll;
                $row->has_no_enrollment = $enroll !== null && $enroll <= 0;
                $row->program_types = $programTypeMap[$typeKey]
                    ?? RegGradeDumpService::typesFromLevelIdList($row->LEVELIDS ?? '');

                return $row;
            })
        );

        $zeroEnrollmentCount = $courses->getCollection()
            ->filter(fn (object $row) => (bool) ($row->has_no_enrollment ?? false))
            ->count();

        $multiSectionCourseCount = $courses->getCollection()
            ->filter(fn (object $row) => (bool) ($row->has_multi_section ?? false))
            ->pluck('COURSECODE')
            ->unique()
            ->count();

        return view('faculty-admin.settings.reg-grade-manage.index', [
            'departments' => $this->service->departments(),
            'courses' => $courses,
            'term' => $term,
            'year' => $year,
            'departmentId' => $departmentId,
            'q' => $q,
            'years' => AcademicTerm::yearOptions(2565, 2580),
            'selectedDepartment' => $selectedDepartment,
            'departmentPatterns' => $departmentPatterns,
            'outsidePatternCount' => $outsidePatternCount,
            'outsidePatternCodes' => $outsidePatternCodes ?? [],
            'canConnectReg' => $canConnectReg,
            'zeroEnrollmentCount' => $zeroEnrollmentCount,
            'multiSectionCourseCount' => $multiSectionCourseCount,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'COURSECODE' => ['required', 'string', 'max:255'],
            'COURSENAMEENG' => ['required', 'string', 'max:255'],
            'SECTION' => ['required', 'string', 'max:5'],
            'ACADYEAR' => ['required', 'integer', 'min:2565', 'max:2580'],
            'SEMESTER' => ['required', 'integer', 'in:1,2,3'],
            'OFFICERID' => ['required', 'string', 'max:100'],
            'OFFICERNAME' => ['required', 'string', 'max:100'],
            'OFFICERSURNAME' => ['required', 'string', 'max:100'],
            'KKUMAIL' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:255'],
        ], [
            'COURSECODE.required' => 'กรุณาระบุรหัสวิชา',
            'COURSENAMEENG.required' => 'กรุณาระบุชื่อวิชา',
            'SECTION.required' => 'กรุณาระบุ Sec.',
            'ACADYEAR.required' => 'กรุณาเลือกปีการศึกษา',
            'SEMESTER.required' => 'กรุณาเลือกภาคการศึกษา',
            'OFFICERID.required' => 'กรุณาเลือกอาจารย์ผู้สอนจากรายการค้นหา',
            'OFFICERNAME.required' => 'กรุณาเลือกอาจารย์ผู้สอนจากรายการค้นหา',
            'OFFICERSURNAME.required' => 'กรุณาเลือกอาจารย์ผู้สอนจากรายการค้นหา',
        ]);

        try {
            $this->service->addCourse([
                'COURSECODE' => strtoupper(trim($validated['COURSECODE'])),
                'COURSENAMEENG' => trim($validated['COURSENAMEENG']),
                'SECTION' => trim($validated['SECTION']),
                'ACADYEAR' => (string) $validated['ACADYEAR'],
                'SEMESTER' => (string) $validated['SEMESTER'],
                'OFFICERID' => trim($validated['OFFICERID']),
                'OFFICERNAME' => trim($validated['OFFICERNAME']),
                'OFFICERSURNAME' => trim($validated['OFFICERSURNAME']),
                'KKUMAIL' => trim((string) ($validated['KKUMAIL'] ?? '')),
            ]);
        } catch (Throwable $e) {
            return redirect()
                ->route('faculty-admin.settings.reg-grade-manage.index', [
                    'term' => $validated['SEMESTER'],
                    'year' => $validated['ACADYEAR'],
                    'department_id' => $validated['department_id'] ?? null,
                    'q' => $validated['q'] ?? null,
                ])
                ->withInput()
                ->withErrors(['manage' => $e->getMessage()]);
        }

        return redirect()
            ->route('faculty-admin.settings.reg-grade-manage.index', [
                'term' => $validated['SEMESTER'],
                'year' => $validated['ACADYEAR'],
                'department_id' => $validated['department_id'] ?? null,
                'q' => $validated['COURSECODE'],
            ])
            ->with('status', 'เพิ่มรายวิชา '.$validated['COURSECODE'].' Sec. '.$validated['SECTION'].' เรียบร้อย');
    }

    public function searchInstructors(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        return response()->json($this->service->searchInstructors($q));
    }

    public function edit(Request $request): View|RedirectResponse
    {
        $courseCode = (string) $request->query('COURSECODE', '');
        $section = (string) $request->query('SECTION', '');
        $year = (string) $request->query('ACADYEAR', '');
        $term = (string) $request->query('SEMESTER', '');
        $departmentId = $request->query('department_id');

        $rows = $this->service->sectionRows($courseCode, $section, $year, $term);
        if ($rows->isEmpty()) {
            return redirect()
                ->route('faculty-admin.settings.reg-grade-manage.index', [
                    'term' => $term,
                    'year' => $year,
                    'department_id' => $departmentId,
                ])
                ->withErrors(['manage' => 'ไม่พบรายวิชาที่ต้องการแก้ไข']);
        }

        return view('faculty-admin.settings.reg-grade-manage.edit', [
            'rows' => $rows,
            'courseCode' => $courseCode,
            'section' => $section,
            'year' => $year,
            'term' => $term,
            'departmentId' => $departmentId,
            'courseNameEng' => $rows->first()->COURSENAMEENG,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'COURSECODE' => ['required', 'string', 'max:255'],
            'SECTION' => ['required', 'string', 'max:5'],
            'ACADYEAR' => ['required', 'string', 'max:10'],
            'SEMESTER' => ['required', 'string', 'max:5'],
            'COURSENAMEENG' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer'],
        ]);

        $this->service->updateCourseName(
            $validated['COURSECODE'],
            $validated['SECTION'],
            $validated['ACADYEAR'],
            $validated['SEMESTER'],
            $validated['COURSENAMEENG'],
        );

        return redirect()
            ->route('faculty-admin.settings.reg-grade-manage.index', [
                'term' => $validated['SEMESTER'],
                'year' => $validated['ACADYEAR'],
                'department_id' => $validated['department_id'] ?? null,
            ])
            ->with('status', 'บันทึกชื่อวิชารายวิชา '.$validated['COURSECODE'].' เรียบร้อย');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'COURSECODE' => ['required', 'string', 'max:255'],
            'SECTION' => ['required', 'string', 'max:5'],
            'ACADYEAR' => ['required', 'string', 'max:10'],
            'SEMESTER' => ['required', 'string', 'max:5'],
            'OFFICERID' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $deleted = $this->service->deleteSection(
            $validated['COURSECODE'],
            $validated['SECTION'],
            $validated['ACADYEAR'],
            $validated['SEMESTER'],
            $validated['OFFICERID'] ?? null,
        );

        $message = $deleted > 0
            ? 'ลบรายวิชา '.$validated['COURSECODE'].' กลุ่ม '.$validated['SECTION'].' เรียบร้อย'
            : 'ไม่พบข้อมูลที่จะลบ';

        return redirect()
            ->route('faculty-admin.settings.reg-grade-manage.index', [
                'term' => $validated['SEMESTER'],
                'year' => $validated['ACADYEAR'],
                'department_id' => $validated['department_id'] ?? null,
                'q' => $validated['q'] ?? null,
            ])
            ->with('status', $message);
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'scope' => ['required', 'string', 'in:selected,filtered'],
            'ACADYEAR' => ['required', 'integer', 'min:2565', 'max:2580'],
            'SEMESTER' => ['required', 'integer', 'in:1,2,3'],
            'department_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:255'],
            'items' => ['nullable', 'array'],
            'items.*.COURSECODE' => ['required_with:items', 'string', 'max:255'],
            'items.*.SECTION' => ['required_with:items', 'string', 'max:5'],
        ]);

        $term = (int) $validated['SEMESTER'];
        $year = (int) $validated['ACADYEAR'];
        $departmentId = isset($validated['department_id']) ? (int) $validated['department_id'] : null;
        $q = trim((string) ($validated['q'] ?? ''));

        if ($departmentId !== null && ! in_array($departmentId, RegGradeDepartmentService::DEPARTMENT_IDS, true)) {
            $departmentId = null;
        }

        if ($validated['scope'] === 'filtered') {
            $deleted = $this->service->deleteFilteredCourses($term, $year, $departmentId, $q);
            $message = $deleted > 0
                ? 'ลบรายวิชาตามเงื่อนไขที่กรองเรียบร้อย ('.$deleted.' แถว)'
                : 'ไม่พบข้อมูลที่จะลบตามเงื่อนไขที่กรอง';
        } else {
            $items = $validated['items'] ?? [];
            if ($items === []) {
                return redirect()
                    ->route('faculty-admin.settings.reg-grade-manage.index', [
                        'term' => $term,
                        'year' => $year,
                        'department_id' => $departmentId,
                        'q' => $q !== '' ? $q : null,
                    ])
                    ->withErrors(['manage' => 'กรุณาเลือกรายวิชาที่ต้องการลบ']);
            }

            $deleted = $this->service->deleteSections($items, (string) $year, (string) $term);
            $message = $deleted > 0
                ? 'ลบรายวิชาที่เลือกเรียบร้อย ('.$deleted.' แถว / '.count($items).' กลุ่ม)'
                : 'ไม่พบข้อมูลที่จะลบจากรายการที่เลือก';
        }

        return redirect()
            ->route('faculty-admin.settings.reg-grade-manage.index', [
                'term' => $term,
                'year' => $year,
                'department_id' => $departmentId,
                'q' => $q !== '' ? $q : null,
            ])
            ->with('status', $message);
    }

    /**
     * @return array{0: int, 1: int, 2: int|null, 3: string}
     */
    private function filters(Request $request): array
    {
        $term = (int) $request->input('term', AcademicTerm::defaultTerm());
        $year = (int) $request->input('year', AcademicTerm::defaultYear());
        $departmentId = $request->filled('department_id') ? $request->integer('department_id') : null;
        $q = trim((string) $request->input('q', ''));

        if (! in_array($term, [1, 2, 3], true)) {
            $term = AcademicTerm::defaultTerm();
        }

        if ($departmentId !== null && ! in_array($departmentId, RegGradeDepartmentService::DEPARTMENT_IDS, true)) {
            $departmentId = null;
        }

        return [$term, $year, $departmentId, $q];
    }
}
