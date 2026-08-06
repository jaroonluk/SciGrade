<?php

namespace App\Http\Controllers;

use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Models\TblUser;
use App\Services\AuditLogService;
use App\Services\DeptAdmin\DepartmentAccessService;
use App\Services\DeptAdmin\DepartmentReportQueryService;
use App\Services\FacultyAdmin\FacultyReportQueryService;
use App\Services\GradeReportFileZipService;
use App\Services\StaffAuthService;
use App\Support\SciGradeRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GradeReportFileDownloadController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly GradeReportFileZipService $zipService,
        private readonly DepartmentAccessService $departmentAccess,
        private readonly DepartmentReportQueryService $deptQuery,
        private readonly FacultyReportQueryService $facultyQuery,
        private readonly AuditLogService $auditLog,
    ) {}

    public function downloadReport(Request $request, GradeReport $gradeReport): BinaryFileResponse|RedirectResponse
    {
        abort_unless($this->canViewFiles($gradeReport), 403);

        $type = $this->normalizeType($request->input('type'));

        try {
            $files = $this->zipService->collectFiles(collect([$gradeReport->loadMissing('files.gradeReport')]), $type);

            $response = $this->zipService->download(
                $files,
                sprintf(
                    '%s_%s_%s_files.zip',
                    $gradeReport->year,
                    $gradeReport->term,
                    $gradeReport->subject_code,
                ),
            );

            $this->auditLog->record(
                'grade_report_file.download_zip',
                subjectType: 'grade_report',
                subjectId: $gradeReport->grade_id,
                metadata: [
                    'scope' => 'report',
                    'type' => $type,
                    'file_count' => $files->count(),
                ],
            );

            return $response;
        } catch (RuntimeException $e) {
            return back()->withErrors(['download' => $e->getMessage()]);
        }
    }

    public function downloadDept(Request $request): BinaryFileResponse|RedirectResponse
    {
        $staff = $this->requireStaff();
        $departments = $this->departmentAccess->allowedDepartments($staff);
        $departmentIds = $departments->pluck('department_id')->map(fn ($id) => (int) $id)->all();

        $scope = $request->input('scope', 'selected');
        $type = $this->normalizeType($request->input('type'));

        if ($scope === 'all') {
            $filters = [
                'department_ids' => $departmentIds,
                'department_id' => $request->filled('department_id') ? (int) $request->department_id : null,
                'term' => $request->input('term'),
                'year' => $request->input('year'),
                'status' => $request->input('status'),
                'subject_code' => $request->input('subject_code'),
                'subject' => $request->input('subject'),
            ];

            if ($filters['department_id'] && ! $this->departmentAccess->canAccessDepartment($staff, $filters['department_id'])) {
                abort(403);
            }

            $reports = $this->deptQuery->baseQuery($filters)->get();
        } else {
            $ids = $this->selectedIds($request);
            $reports = $this->deptQuery
                ->baseQuery(['department_ids' => $departmentIds])
                ->whereIn('grade_id', $ids)
                ->get();
        }

        return $this->respondZip($reports, $type, 'dept_grade_files');
    }

    public function downloadFaculty(Request $request): BinaryFileResponse|RedirectResponse
    {
        $this->requireStaff();

        $scope = $request->input('scope', 'selected');
        $type = $this->normalizeType($request->input('type'));

        if ($scope === 'all') {
            $filters = [
                'department_id' => $request->filled('department_id') ? (int) $request->department_id : null,
                'term' => $request->input('term'),
                'year' => $request->input('year'),
                'status' => $request->input('status'),
                'subject_code' => $request->input('subject_code'),
                'subject' => $request->input('subject'),
                'created_from' => $request->input('created_from'),
                'created_to' => $request->input('created_to'),
            ];
            $reports = $this->facultyQuery->baseQuery($filters)->with('files')->get();
        } else {
            $ids = $this->selectedIds($request);
            $reports = $this->facultyQuery
                ->baseQuery([])
                ->whereIn('grade_id', $ids)
                ->with('files')
                ->get();
        }

        return $this->respondZip($reports, $type, 'faculty_grade_files');
    }

    /**
     * @return list<int>
     */
    private function selectedIds(Request $request): array
    {
        $ids = collect($request->input('grade_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        abort_if($ids === [], 422, 'กรุณาเลือกรายวิชาอย่างน้อย 1 รายการ');

        return $ids;
    }

    /**
     * @param  Collection<int, GradeReport>  $reports
     */
    private function respondZip(Collection $reports, ?string $type, string $prefix): BinaryFileResponse|RedirectResponse
    {
        $reports->loadMissing('files.gradeReport');

        try {
            $files = $this->zipService->collectFiles($reports, $type);

            $response = $this->zipService->download(
                $files,
                sprintf('%s_%s.zip', $prefix, now()->format('Ymd_His')),
            );

            $this->auditLog->record(
                'grade_report_file.download_zip',
                subjectType: 'grade_report_batch',
                subjectId: $prefix,
                metadata: [
                    'scope' => $prefix,
                    'type' => $type,
                    'report_count' => $reports->count(),
                    'file_count' => $files->count(),
                    'grade_ids' => $reports->pluck('grade_id')->take(100)->values()->all(),
                ],
            );

            return $response;
        } catch (RuntimeException $e) {
            return back()->withErrors(['download' => $e->getMessage()]);
        }
    }

    private function normalizeType(mixed $type): ?string
    {
        $type = is_string($type) ? trim($type) : '';

        if ($type === '' || $type === 'all') {
            return 'all';
        }

        abort_unless(in_array($type, GradeReportFile::allowedTypes(), true), 422);

        return $type;
    }

    private function canViewFiles(GradeReport $gradeReport): bool
    {
        if ($gradeReport->username === $this->staffUsername()) {
            return true;
        }

        $role = session('scigrade_role', 'instructor');

        return $role === 'dept_admin' || SciGradeRole::isFacultyCapable($role);
    }

    private function requireStaff(): TblUser
    {
        $staff = $this->staffAuth->findByEmail(auth()->user()->email);
        abort_unless($staff, 403, 'ไม่พบข้อมูลเจ้าหน้าที่');
        $this->staffAuth->storeInSession($staff);

        return $staff;
    }

    private function staffUsername(): string
    {
        $username = session('staff_username');

        if (empty($username) && auth()->user()) {
            $staff = $this->staffAuth->findByEmail(auth()->user()->email);
            if ($staff) {
                $this->staffAuth->storeInSession($staff);
                $username = $staff->username;
            }
        }

        abort_unless($username, 403, 'ไม่พบข้อมูลผู้ใช้งาน');

        return (string) $username;
    }
}
