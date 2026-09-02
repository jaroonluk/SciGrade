<?php

namespace App\Http\Controllers\DeptAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeptAdmin\DepartmentReportExportRequest;
use App\Models\TblDepartment;
use App\Services\DeptAdmin\DepartmentAccessService;
use App\Services\DeptAdmin\DepartmentReportExportService;
use App\Services\DeptAdmin\DepartmentReportQueryService;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use App\Services\StaffAuthService;
use App\Support\AcademicTerm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        private readonly DepartmentSubjectFilter $subjectFilter,
    ) {}

    public function form(): View
    {
        $staff = $this->requireStaff();
        $departments = $this->departmentAccess->allowedDepartments($staff);

        $patternsByDepartment = [];
        foreach ($departments as $dept) {
            $id = (int) $dept->department_id;
            $patternsByDepartment[$id] = [
                'name' => (string) $dept->department_name,
                'patterns' => $this->subjectFilter->patternDetailsForDepartment($id),
            ];
        }

        $initialDepartmentId = (int) old('department_id', $departments->first()?->department_id);
        $term = old('term', AcademicTerm::defaultTerm());
        $year = old('year', AcademicTerm::defaultYear());
        $educationLevel = old('education_level', 'graduate');

        $dateSummary = $this->queryService->submissionDateSummary([
            'department_ids' => $this->departmentAccess->allowedDepartmentIds($staff),
            'department_id' => $initialDepartmentId ?: null,
            'term' => $term !== null && $term !== '' ? (int) $term : null,
            'year' => $year !== null && $year !== '' ? (int) $year : null,
            'education_level' => $educationLevel,
        ]);

        return view('dept-admin.reports.form', [
            'departments' => $departments,
            'term' => $term,
            'year' => $year,
            'years' => AcademicTerm::yearOptions(),
            'patternsByDepartment' => $patternsByDepartment,
            'initialDepartmentId' => $initialDepartmentId,
            'dateSummary' => $dateSummary,
            'dateSummaryUrl' => route('dept-admin.reports.date-summary'),
        ]);
    }

    public function dateSummary(Request $request): JsonResponse
    {
        $staff = $this->requireStaff();
        $departmentId = $request->integer('department_id');

        abort_unless(
            $departmentId > 0 && $this->departmentAccess->canAccessDepartment($staff, $departmentId),
            403,
            'ไม่มีสิทธิ์ดูสรุปสาขานี้',
        );

        $request->validate([
            'department_id' => ['required', 'integer'],
            'education_level' => ['nullable', 'string', 'in:bachelor,master,doctoral,graduate,all'],
            'term' => ['nullable', 'integer', 'in:1,2,3'],
            'year' => ['nullable', 'integer', 'min:2500', 'max:2600'],
        ]);

        $summary = $this->queryService->submissionDateSummary([
            'department_ids' => $this->departmentAccess->allowedDepartmentIds($staff),
            'department_id' => $departmentId,
            'term' => $request->filled('term') ? $request->integer('term') : null,
            'year' => $request->filled('year') ? $request->integer('year') : null,
            'education_level' => $request->input('education_level', 'all'),
        ]);

        return response()->json($summary);
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
                $request->integer('report_status'),
                $term,
                $year,
            )
            : $this->exportService->exportPdf(
                $reports,
                $department,
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
