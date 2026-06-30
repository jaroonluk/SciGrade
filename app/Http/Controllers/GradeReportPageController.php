<?php

namespace App\Http\Controllers;

use App\Models\GradeReport;
use App\Models\TblProgramQa;
use App\Services\StaffAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GradeReportPageController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
    ) {}

    private function formView(?int $reportId): View
    {
        $deptId = session('staff_department_id');
        if ($deptId === null && auth()->user()) {
            $staff = $this->staffAuth->findByEmail(auth()->user()->email);
            if ($staff) {
                $this->staffAuth->storeInSession($staff);
                $deptId = (int) $staff->department_id;
            }
        }

        $teacherHelpImageUrl = file_exists(public_path('images/teacher2.png'))
            ? asset('images/teacher2.png')
            : (Storage::disk('public')->exists('teacher2.png')
                ? asset('storage/teacher2.png')
                : 'https://e.sc.kku.ac.th/sci-eoffice/teacher/images2/teacher2.png');

        return view('templade', [
            'reportId' => $reportId,
            'staffDisplayName' => $this->staffAuth->displayNameFor(
                auth()->user()->email,
                auth()->user()->name,
            ),
            'teacherHelpImageUrl' => $teacherHelpImageUrl,
            'programs' => TblProgramQa::forDepartment($deptId),
        ]);
    }

    public function create(): View
    {
        return $this->formView(null);
    }

    public function edit(GradeReport $gradeReport): View
    {
        abort_if($gradeReport->user_id !== auth()->id(), 403);
        abort_if($gradeReport->approv > 0, 403, 'ไม่สามารถแก้ไขรายการที่อนุมัติแล้ว');

        return $this->formView($gradeReport->id);
    }

    public function upload(): View
    {
        return view('grade-reports.upload');
    }

    public function storeUpload(Request $request): RedirectResponse
    {
        $request->validate([
            'grade_file' => ['required', 'file', 'mimes:csv,xlsx,xls,pdf', 'max:10240'],
        ]);

        $path = $request->file('grade_file')->store('grade-uploads/'.auth()->id(), 'local');

        session([
            'grade_upload_path' => $path,
            'grade_upload_name' => $request->file('grade_file')->getClientOriginalName(),
        ]);

        return redirect()
            ->route('grade-reports.create')
            ->with('status', 'อัปโหลดไฟล์สำเร็จ กรุณาตรวจสอบและแก้ไขข้อมูลก่อนบันทึก');
    }

    public function my(): View
    {
        return view('grade-reports.my', ['role' => 'instructor']);
    }

    public function approve(): View
    {
        $role = session('scigrade_role', 'dept_admin');
        abort_if(! in_array($role, ['dept_admin', 'faculty_admin'], true), 403);

        return view('grade-reports.approve', compact('role'));
    }

    public function reports(): View
    {
        $role = session('scigrade_role', 'instructor');

        return view('grade-reports.reports', compact('role'));
    }

    public function printSummary(Request $request): View
    {
        $role = session('scigrade_role', 'dept_admin');
        abort_if(! in_array($role, ['dept_admin', 'faculty_admin'], true), 403);

        $query = GradeReport::query()->with('gradeStds')->orderBy('subject_code');

        if ($role === 'dept_admin') {
            $query->whereIn('approv', [0, 1, 2, -1]);
        } else {
            if ($request->filled('fac')) {
                $query->whereHas('gradeStds', fn ($q) => $q->where('fac', 'like', '%'.$request->fac.'%'));
            }
            if ($request->filled('approv')) {
                $query->where('approv', $request->integer('approv'));
            } else {
                $query->where('approv', 2);
            }
        }

        $reports = $query->get();

        return view('grade-reports.print-summary', [
            'reports' => $reports,
            'role' => $role,
            'fac' => $request->get('fac'),
        ]);
    }

    public function print(GradeReport $gradeReport): View
    {
        if (session('scigrade_role', 'instructor') === 'instructor') {
            abort_if($gradeReport->user_id !== auth()->id(), 403);
        }

        $gradeReport->load('gradeStds');

        return view('grade-reports.print', compact('gradeReport'));
    }
}
