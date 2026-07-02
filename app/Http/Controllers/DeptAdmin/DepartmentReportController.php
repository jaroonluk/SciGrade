<?php

namespace App\Http\Controllers\DeptAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeptAdmin\DepartmentReportExportRequest;
use App\Models\TblDepartment;
use App\Services\DeptAdmin\DepartmentAccessService;
use App\Services\DeptAdmin\DepartmentReportExportService;
use App\Services\DeptAdmin\DepartmentReportQueryService;
use App\Services\StaffAuthService;
use App\Support\AcademicTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepartmentReportController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly DepartmentAccessService $departmentAccess,
        private readonly DepartmentReportQueryService $queryService,
        private readonly DepartmentReportExportService $exportService,
    ) {}

    public function form(): View
    {
        $staff = $this->requireStaff();
        $departments = $this->departmentAccess->allowedDepartments($staff);

        return view('dept-admin.reports.form', [
            'departments' => $departments,
            'term' => AcademicTerm::defaultTerm(),
            'year' => AcademicTerm::defaultYear(),
            'years' => AcademicTerm::yearOptions(),
        ]);
    }

    public function export(DepartmentReportExportRequest $request): Response|StreamedResponse|RedirectResponse
    {
        $staff = $this->requireStaff();
        $departmentId = $request->integer('department_id');

        abort_unless(
            $this->departmentAccess->canAccessDepartment($staff, $departmentId),
            403,
            'ไม่มีสิทธิ์พิมพ์รายงานสาขานี้',
        );

        $department = TblDepartment::query()->findOrFail($departmentId);
        $departmentIds = $this->departmentAccess->allowedDepartmentIds($staff);
        $filters = $request->exportFilters($departmentIds);
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

    private function requireStaff()
    {
        $staff = $this->staffAuth->findByEmail(auth()->user()->email);
        abort_unless($staff, 403, 'ไม่พบข้อมูลเจ้าหน้าที่');
        $this->staffAuth->storeInSession($staff);

        return $staff;
    }
}
