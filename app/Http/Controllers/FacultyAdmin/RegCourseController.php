<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FacultyAdmin\RegCourseSyncRequest;
use App\Services\FacultyAdmin\RegCourseSyncService;
use App\Support\AcademicTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class RegCourseController extends Controller
{
    public function __construct(
        private readonly RegCourseSyncService $syncService,
    ) {}

    public function index(): View
    {
        return view('faculty-admin.settings.reg-courses.index', [
            'years' => AcademicTerm::yearOptions(2560, 2580),
            'defaultYear' => AcademicTerm::defaultYear(),
            'canConnect' => $this->syncService->canConnect(),
        ]);
    }

    public function sync(RegCourseSyncRequest $request): RedirectResponse
    {
        $year = $request->integer('year');

        try {
            $result = $this->syncService->sync($year);
        } catch (Throwable $e) {
            return redirect()
                ->route('faculty-admin.settings.reg-courses.index')
                ->withInput()
                ->withErrors(['sync' => 'ไม่สามารถดึงข้อมูลจาก REG ได้: '.$e->getMessage()]);
        }

        $status = sprintf(
            'ดึงรายวิชาปีการศึกษา %d เรียบร้อย — พบ %d รายการ เพิ่มใหม่ %d รายการ (มีอยู่แล้ว %d รายการ)',
            $year,
            $result['fetched'],
            $result['inserted'],
            $result['skipped'],
        );

        return redirect()
            ->route('faculty-admin.settings.reg-courses.index')
            ->with('status', $status)
            ->with('sync_result', $result)
            ->with('sync_year', $year);
    }
}
