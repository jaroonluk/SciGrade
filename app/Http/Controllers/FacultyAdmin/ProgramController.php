<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FacultyAdmin\ProgramRequest;
use App\Models\TblDepartment;
use App\Models\TblProgramQa;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(): View
    {
        $programs = TblProgramQa::query()
            ->orderBy('department_id')
            ->orderBy('typestudy')
            ->orderBy('programid')
            ->paginate(30);

        return view('faculty-admin.settings.programs.index', [
            'programs' => $programs,
        ]);
    }

    public function create(): View
    {
        return view('faculty-admin.settings.programs.form', [
            'program' => new TblProgramQa,
            'departments' => TblDepartment::query()->orderBy('department_name')->get(),
        ]);
    }

    public function store(ProgramRequest $request): RedirectResponse
    {
        TblProgramQa::query()->create($request->validated());

        return redirect()
            ->route('faculty-admin.settings.programs.index')
            ->with('status', 'เพิ่มหลักสูตรเรียบร้อย');
    }

    public function edit(TblProgramQa $program): View
    {
        return view('faculty-admin.settings.programs.form', [
            'program' => $program,
            'departments' => TblDepartment::query()->orderBy('department_name')->get(),
        ]);
    }

    public function update(ProgramRequest $request, TblProgramQa $program): RedirectResponse
    {
        $program->update($request->validated());

        return redirect()
            ->route('faculty-admin.settings.programs.index')
            ->with('status', 'บันทึกหลักสูตรเรียบร้อย');
    }

    public function destroy(TblProgramQa $program): RedirectResponse
    {
        $program->delete();

        return redirect()
            ->route('faculty-admin.settings.programs.index')
            ->with('status', 'ลบหลักสูตรเรียบร้อย');
    }
}
