<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FacultyAdmin\GradeTermRequest;
use App\Models\GradeTerm;
use App\Support\AcademicTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GradeTermController extends Controller
{
    public function edit(): View
    {
        $termSetting = GradeTerm::query()->orderBy('id')->first();

        return view('faculty-admin.settings.term', [
            'termSetting' => $termSetting,
            'defaults' => AcademicTerm::defaults(),
        ]);
    }

    public function update(GradeTermRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $termSetting = GradeTerm::query()->orderBy('id')->first();
        if ($termSetting) {
            $termSetting->update($validated);
        } else {
            GradeTerm::query()->create($validated);
        }

        return redirect()
            ->route('faculty-admin.settings.term')
            ->with('status', 'บันทึกภาคการศึกษาปัจจุบันสำหรับระบบเรียบร้อย');
    }
}
