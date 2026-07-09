<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FacultyAdmin\FacultyReportExportRequest;
use App\Models\TblDepartment;
use App\Models\TblProgramQa;
use App\Services\DeptAdmin\DepartmentReportExportService;
use App\Services\DeptAdmin\DepartmentReportQueryService;
use App\Support\AcademicTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FacultyReportController extends Controller
{
    public function __construct(
        private readonly DepartmentReportQueryService $queryService,
        private readonly DepartmentReportExportService $exportService,
    ) {}

    public function form(): View
    {
        abort_unless(session('scigrade_role') === 'faculty_admin', 403);

        $departments = TblDepartment::query()
            ->whereIn('department_id', TblProgramQa::ALLOWED_DEPARTMENT_IDS)
            ->orderBy('department_name')
            ->get();

        return view('faculty-admin.reports.form', [
            'departments' => $departments,
            'term' => AcademicTerm::defaultTerm(),
            'year' => AcademicTerm::defaultYear(),
            'years' => AcademicTerm::yearOptions(),
        ]);
    }

    public function export(FacultyReportExportRequest $request): Response|StreamedResponse|RedirectResponse
    {
        $departmentId = $request->integer('department_id');
        $department = TblDepartment::query()->findOrFail($departmentId);
        $filters = $request->exportFilters();
        $reports = $this->queryService->reportsForExport($filters);
        $term = $request->integer('term') ?: null;
        $year = $request->integer('year') ?: null;

        if ($reports->isEmpty()) {
            return back()->withErrors(['export' => 'ไม่พบข้อมูลตามเงื่อนไขที่เลือก']);
        }

        return $request->input('format') === 'word'
            ? $this->exportService->exportWord(
                $reports,
                $department,
                $request->input('start_date'),
                $request->input('end_date'),
                $request->integer('report_status'),
                $term,
                $year,
            )
            : $this->exportService->exportPdf(
                $reports,
                $department,
                $request->input('start_date'),
                $request->input('end_date'),
                $request->integer('report_status'),
                $term,
                $year,
            );
    }
}
