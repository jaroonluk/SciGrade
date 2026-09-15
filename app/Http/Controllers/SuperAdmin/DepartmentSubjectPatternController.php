<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\DepartmentSubjectPattern;
use App\Services\SuperAdmin\DepartmentSubjectPatternService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class DepartmentSubjectPatternController extends Controller
{
    public function __construct(
        private readonly DepartmentSubjectPatternService $service,
    ) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));
        $focus = $request->integer('department_id') ?: null;
        $viewFilter = $this->service->normalizeViewFilter($request->input('education_level'));

        return view('super-admin.department-patterns.index', [
            'departments' => $this->service->departmentsWithPatterns($q, $viewFilter),
            'q' => $q,
            'focusDepartmentId' => $focus,
            'viewFilter' => $viewFilter,
            'educationLevel' => $viewFilter === 'all'
                ? DepartmentSubjectPattern::EDUCATION_BACHELOR
                : $viewFilter,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'integer'],
            'pattern' => ['required', 'string', 'max:100'],
            'education_level' => ['nullable', 'in:bachelor,graduate'],
        ]);
        $educationLevel = DepartmentSubjectPattern::normalizeEducationLevel($validated['education_level'] ?? null);

        try {
            $this->service->store((int) $validated['department_id'], $validated['pattern'], $educationLevel);
        } catch (ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('focus_department_id', (int) $validated['department_id']);
        }

        return redirect()
            ->route('faculty-admin.department-patterns.index', $this->indexQuery($request, (int) $validated['department_id'], $educationLevel))
            ->with('status', 'เพิ่มเงื่อนไข '.$validated['pattern'].' สำหรับ'.DepartmentSubjectPattern::label($educationLevel).' เรียบร้อย');
    }

    public function update(Request $request, DepartmentSubjectPattern $pattern): RedirectResponse
    {
        $validated = $request->validate([
            'pattern' => ['required', 'string', 'max:100'],
            'education_level' => ['nullable', 'in:bachelor,graduate'],
        ]);
        $educationLevel = DepartmentSubjectPattern::normalizeEducationLevel(
            $validated['education_level'] ?? $pattern->education_level
        );

        try {
            $this->service->update($pattern, $validated['pattern'], $educationLevel);
        } catch (ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('focus_department_id', (int) $pattern->department_id);
        }

        return redirect()
            ->route('faculty-admin.department-patterns.index', $this->indexQuery($request, (int) $pattern->department_id, $educationLevel))
            ->with('status', 'แก้ไขเงื่อนไขเรียบร้อย');
    }

    public function destroy(Request $request, DepartmentSubjectPattern $pattern): RedirectResponse
    {
        $departmentId = (int) $pattern->department_id;
        $educationLevel = DepartmentSubjectPattern::normalizeEducationLevel(
            $request->input('education_level', $pattern->education_level)
        );
        $label = $pattern->pattern;
        $this->service->destroy($pattern);

        return redirect()
            ->route('faculty-admin.department-patterns.index', $this->indexQuery($request, $departmentId, $educationLevel))
            ->with('status', 'ลบเงื่อนไข '.$label.' เรียบร้อย');
    }

    public function restoreDefaults(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'integer'],
            'education_level' => ['nullable', 'in:bachelor,graduate'],
        ]);
        $educationLevel = DepartmentSubjectPattern::normalizeEducationLevel($validated['education_level'] ?? null);

        try {
            $count = $this->service->restoreDefaults((int) $validated['department_id'], $educationLevel);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('faculty-admin.department-patterns.index', $this->indexQuery($request, (int) $validated['department_id'], $educationLevel))
            ->with('status', 'กู้คืนค่าเริ่มต้นของ'.DepartmentSubjectPattern::label($educationLevel).' เรียบร้อย ('.$count.' เงื่อนไข)');
    }

    /**
     * @return array{department_id: int, q: mixed, education_level: string}
     */
    private function indexQuery(Request $request, int $departmentId, string $educationLevel): array
    {
        // คงโหมดการแสดงผลหน้าจอ (all/bachelor/graduate) ไม่สลับตามระดับที่เพิ่งแก้
        $viewFilter = $this->service->normalizeViewFilter(
            $request->input('view', $request->input('education_level'))
        );

        return [
            'department_id' => $departmentId,
            'q' => $request->input('q'),
            'education_level' => $viewFilter,
        ];
    }
}
