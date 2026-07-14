<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Services\FacultyAdmin\RegGradeDepartmentService;
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
    ) {}

    public function index(Request $request): View
    {
        [$term, $year, $departmentId, $q] = $this->filters($request);

        $courses = $this->service->groupedCoursesPaginated($term, $year, $departmentId, $q, 40);

        return view('faculty-admin.settings.reg-grade-manage.index', [
            'departments' => $this->service->departments(),
            'courses' => $courses,
            'term' => $term,
            'year' => $year,
            'departmentId' => $departmentId,
            'q' => $q,
            'years' => AcademicTerm::yearOptions(2565, 2580),
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
