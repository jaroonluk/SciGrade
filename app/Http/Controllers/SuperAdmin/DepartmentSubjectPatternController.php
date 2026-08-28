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

        return view('super-admin.department-patterns.index', [
            'departments' => $this->service->departmentsWithPatterns($q),
            'q' => $q,
            'focusDepartmentId' => $focus,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'integer'],
            'pattern' => ['required', 'string', 'max:100'],
        ]);

        try {
            $this->service->store((int) $validated['department_id'], $validated['pattern']);
        } catch (ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('focus_department_id', (int) $validated['department_id']);
        }

        return redirect()
            ->route('faculty-admin.department-patterns.index', [
                'department_id' => $validated['department_id'],
                'q' => $request->input('q'),
            ])
            ->with('status', 'เพิ่มเงื่อนไข '.$validated['pattern'].' เรียบร้อย');
    }

    public function update(Request $request, DepartmentSubjectPattern $pattern): RedirectResponse
    {
        $validated = $request->validate([
            'pattern' => ['required', 'string', 'max:100'],
        ]);

        try {
            $this->service->update($pattern, $validated['pattern']);
        } catch (ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('focus_department_id', (int) $pattern->department_id);
        }

        return redirect()
            ->route('faculty-admin.department-patterns.index', [
                'department_id' => $pattern->department_id,
                'q' => $request->input('q'),
            ])
            ->with('status', 'แก้ไขเงื่อนไขเรียบร้อย');
    }

    public function destroy(Request $request, DepartmentSubjectPattern $pattern): RedirectResponse
    {
        $departmentId = (int) $pattern->department_id;
        $label = $pattern->pattern;
        $this->service->destroy($pattern);

        return redirect()
            ->route('faculty-admin.department-patterns.index', [
                'department_id' => $departmentId,
                'q' => $request->input('q'),
            ])
            ->with('status', 'ลบเงื่อนไข '.$label.' เรียบร้อย');
    }

    public function restoreDefaults(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'integer'],
        ]);

        try {
            $count = $this->service->restoreDefaults((int) $validated['department_id']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('faculty-admin.department-patterns.index', [
                'department_id' => $validated['department_id'],
                'q' => $request->input('q'),
            ])
            ->with('status', 'กู้คืนค่าเริ่มต้นเรียบร้อย ('.$count.' เงื่อนไข)');
    }
}
