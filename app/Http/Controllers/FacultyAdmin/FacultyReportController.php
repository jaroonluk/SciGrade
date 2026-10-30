<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FacultyAdmin\FacultyReportExportRequest;
use App\Models\TblDepartment;
use App\Services\DeptAdmin\DepartmentReportExportService;
use App\Services\DeptAdmin\DepartmentReportQueryService;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use App\Support\AcademicTerm;
use App\Support\FacultyReportDepartments;
use App\Support\SciGradeRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FacultyReportController extends Controller
{
    public function __construct(
        private readonly DepartmentReportQueryService $queryService,
        private readonly DepartmentReportExportService $exportService,
        private readonly DepartmentSubjectFilter $subjectFilter,
    ) {}

    public function form(Request $request): View
    {
        abort_unless(SciGradeRole::isFacultyCapable(), 403);

        $departments = FacultyReportDepartments::selectable();

        $patternsByDepartment = [];
        foreach ($departments as $dept) {
            $id = (int) $dept->department_id;
            $patternsByDepartment[$id] = [
                'name' => (string) $dept->department_name,
                'patterns' => $this->subjectFilter->patternDetailsForDepartment($id),
            ];
        }

        $initialDepartmentId = old('department_id', $departments->first()?->department_id);
        $selected = AcademicTerm::fromRequest($request);

        return view('faculty-admin.reports.form', [
            'departments' => $departments,
            'term' => (int) old('term', $selected['term']),
            'year' => (int) old('year', $selected['year']),
            'years' => AcademicTerm::yearOptions(),
            'patternsByDepartment' => $patternsByDepartment,
            'initialDepartmentId' => $initialDepartmentId,
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
            $status = \App\Enums\GradeApprovalStatus::tryFromValue((int) $request->input('report_status'));
            $level = (string) $request->input('education_level', 'all');
            $levelLabel = match ($level) {
                'bachelor' => 'ปริญญาตรี',
                'master' => 'ปริญญาโท',
                'doctoral' => 'ปริญญาเอก',
                'graduate' => 'บัณฑิตศึกษา (โท+เอก)',
                default => 'รวมทั้งหมด',
            };
            $term = $request->integer('term') ?: null;
            $year = $request->integer('year') ?: null;
            $termYear = ($term || $year)
                ? trim(($term ? match ($term) {
                    1 => 'ภาคต้น',
                    2 => 'ภาคปลาย',
                    3 => 'ภาคการศึกษาพิเศษ',
                    default => 'ภาค '.$term,
                } : 'ทุกภาค').($year ? ' ปีการศึกษา '.$year : ''))
                : 'ทุกภาค/ปี';

            return back()->withErrors([
                'export' => 'ไม่พบรายวิชาตามเงื่อนไขที่เลือก'
                    ."\nสาขา: ".$department->department_name
                    ."\nระดับการศึกษา: ".$levelLabel
                    ."\n".$termYear
                    ."\nสถานะ: ".$status->shortLabel()
                    ."\nตรวจสอบว่ามีรายวิชาที่รหัสอยู่ในสังกัดสาขานี้และตรงสถานะ/ภาคปีที่เลือก หรือลองเปลี่ยนเป็น «รวมทั้งหมด»",
            ])->withInput();
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
}
