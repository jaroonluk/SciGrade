<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FacultyAdmin\RegGradeDumpRequest;
use App\Services\FacultyAdmin\RegGradeDepartmentService;
use App\Services\FacultyAdmin\RegGradeDumpService;
use App\Support\AcademicTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class RegGradeDumpController extends Controller
{
    public function __construct(
        private readonly RegGradeDumpService $dumpService,
        private readonly RegGradeDepartmentService $regService,
    ) {}

    public function index(Request $request): View
    {
        $currentBuddhistYear = (int) date('Y') + 543;
        $years = range(2566, max(2566, $currentBuddhistYear));

        $term = (int) $request->input('term', AcademicTerm::defaultTerm());
        $year = (int) $request->input('year', AcademicTerm::defaultYear());
        $q = trim((string) $request->input('q', ''));
        $departmentId = $request->filled('department_id') ? $request->integer('department_id') : null;

        if (! in_array($term, [1, 2, 3], true)) {
            $term = AcademicTerm::defaultTerm();
        }
        if ($year < 2566 || $year > $currentBuddhistYear + 5) {
            $year = AcademicTerm::defaultYear();
        }
        if ($departmentId !== null && ! in_array($departmentId, RegGradeDepartmentService::DEPARTMENT_IDS, true)) {
            $departmentId = null;
        }

        $courses = $this->regService->groupedCoursesPaginated($term, $year, $departmentId, $q, 40);

        $enrollmentMap = [];
        $zeroEnrollment = [];
        if ($this->dumpService->canConnect() && $courses->isNotEmpty()) {
            try {
                $enrollmentMap = $this->dumpService->enrollmentSeatMap($year, $term, $courses->items());
            } catch (Throwable) {
                $enrollmentMap = [];
            }
        }

        $courses->setCollection(
            $courses->getCollection()->map(function (object $row) use ($enrollmentMap) {
                $key = strtoupper(trim((string) $row->COURSECODE)).'|'.trim((string) $row->SECTION);
                $enroll = array_key_exists($key, $enrollmentMap) ? (int) $enrollmentMap[$key] : null;
                $row->enrollseat = $enroll;
                $row->has_no_enrollment = $enroll !== null && $enroll <= 0;

                return $row;
            })
        );

        $zeroEnrollment = $courses->getCollection()
            ->filter(fn (object $row) => (bool) ($row->has_no_enrollment ?? false))
            ->map(fn (object $row) => [
                'coursecode' => (string) $row->COURSECODE,
                'coursenameeng' => (string) $row->COURSENAMEENG,
                'section' => (string) $row->SECTION,
                'enrollseat' => (int) ($row->enrollseat ?? 0),
            ])
            ->values()
            ->all();

        return view('faculty-admin.settings.reg-grade-dump.index', [
            'years' => $years,
            'term' => $term,
            'year' => $year,
            'q' => $q,
            'departmentId' => $departmentId,
            'departments' => $this->regService->departments(),
            'courses' => $courses,
            'canConnect' => $this->dumpService->canConnect(),
            'zeroEnrollment' => $zeroEnrollment,
            'zeroEnrollmentCount' => count($zeroEnrollment),
        ]);
    }

    public function dump(RegGradeDumpRequest $request): RedirectResponse
    {
        $year = $request->integer('year');
        $term = $request->integer('term');

        try {
            $result = $this->dumpService->dump($year, $term);
        } catch (Throwable $e) {
            return redirect()
                ->route('faculty-admin.settings.reg-grade-dump.index', [
                    'term' => $term,
                    'year' => $year,
                ])
                ->withInput()
                ->withErrors(['dump' => 'ไม่สามารถดึงข้อมูลจาก REG ได้: '.$e->getMessage()]);
        }

        $status = sprintf(
            'Download รายวิชา REG ภาค %d/%d เรียบร้อย — พบ %d รายการ เพิ่มใหม่ %d ทับข้อมูลเดิม %d กลุ่ม ข้าม %d',
            $term,
            $year,
            $result['fetched'],
            $result['inserted'],
            $result['sections_replaced'],
            $result['skipped'],
        );

        $zeroCount = count($result['zero_enrollment']);
        if ($zeroCount > 0) {
            $status .= " — พบ {$zeroCount} กลุ่มที่ไม่มีผู้ลงทะเบียน (ENROLLSEAT = 0)";
        }

        return redirect()
            ->route('faculty-admin.settings.reg-grade-dump.index', [
                'term' => $term,
                'year' => $year,
            ])
            ->with('status', $status);
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
        ]);

        try {
            $this->regService->addCourse([
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
                ->route('faculty-admin.settings.reg-grade-dump.index', [
                    'term' => $validated['SEMESTER'],
                    'year' => $validated['ACADYEAR'],
                    'department_id' => $validated['department_id'] ?? null,
                    'q' => $validated['q'] ?? null,
                ])
                ->withInput()
                ->withErrors(['dump' => $e->getMessage()]);
        }

        return redirect()
            ->route('faculty-admin.settings.reg-grade-dump.index', [
                'term' => $validated['SEMESTER'],
                'year' => $validated['ACADYEAR'],
                'department_id' => $validated['department_id'] ?? null,
                'q' => $validated['COURSECODE'],
            ])
            ->with('status', 'เพิ่มรายวิชา '.$validated['COURSECODE'].' Sec. '.$validated['SECTION'].' เรียบร้อย');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'COURSECODE' => ['required', 'string', 'max:255'],
            'SECTION' => ['required', 'string', 'max:5'],
            'ACADYEAR' => ['required', 'string', 'max:10'],
            'SEMESTER' => ['required', 'string', 'max:5'],
            'department_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $deleted = $this->regService->deleteSection(
            $validated['COURSECODE'],
            $validated['SECTION'],
            $validated['ACADYEAR'],
            $validated['SEMESTER'],
        );

        return redirect()
            ->route('faculty-admin.settings.reg-grade-dump.index', [
                'term' => $validated['SEMESTER'],
                'year' => $validated['ACADYEAR'],
                'department_id' => $validated['department_id'] ?? null,
                'q' => $validated['q'] ?? null,
            ])
            ->with('status', $deleted > 0
                ? 'ลบรายวิชา '.$validated['COURSECODE'].' Sec. '.$validated['SECTION'].' เรียบร้อย'
                : 'ไม่พบข้อมูลที่จะลบ');
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
            $deleted = $this->regService->deleteFilteredCourses($term, $year, $departmentId, $q);
            $message = $deleted > 0
                ? 'ลบรายวิชาตามเงื่อนไขที่กรองเรียบร้อย ('.$deleted.' แถว)'
                : 'ไม่พบข้อมูลที่จะลบตามเงื่อนไขที่กรอง';
        } else {
            $items = $validated['items'] ?? [];
            if ($items === []) {
                return redirect()
                    ->route('faculty-admin.settings.reg-grade-dump.index', [
                        'term' => $term,
                        'year' => $year,
                        'department_id' => $departmentId,
                        'q' => $q !== '' ? $q : null,
                    ])
                    ->withErrors(['dump' => 'กรุณาเลือกรายวิชาที่ต้องการลบ']);
            }

            $deleted = $this->regService->deleteSections($items, (string) $year, (string) $term);
            $message = $deleted > 0
                ? 'ลบรายวิชาที่เลือกเรียบร้อย ('.$deleted.' แถว / '.count($items).' กลุ่ม)'
                : 'ไม่พบข้อมูลที่จะลบจากรายการที่เลือก';
        }

        return redirect()
            ->route('faculty-admin.settings.reg-grade-dump.index', [
                'term' => $term,
                'year' => $year,
                'department_id' => $departmentId,
                'q' => $q !== '' ? $q : null,
            ])
            ->with('status', $message);
    }
}
