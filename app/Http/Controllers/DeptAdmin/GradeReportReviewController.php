<?php

namespace App\Http\Controllers\DeptAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeptAdmin\DeptRegistrarBulkUploadRequest;
use App\Http\Requests\DeptAdmin\GradeReportApprovalRequest;
use App\Http\Requests\DeptAdmin\GradeReportReviewFilterRequest;
use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Services\AuditLogService;
use App\Services\DeptAdmin\DepartmentAccessService;
use App\Services\DeptAdmin\DepartmentReportQueryService;
use App\Services\DeptAdmin\DeptRegistrarBulkUploadService;
use App\Services\DeptAdmin\GradeReportApprovalService;
use App\Services\StaffAuthService;
use App\Support\AcademicTerm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use InvalidArgumentException;

class GradeReportReviewController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly DepartmentAccessService $departmentAccess,
        private readonly DepartmentReportQueryService $queryService,
        private readonly GradeReportApprovalService $approvalService,
        private readonly DeptRegistrarBulkUploadService $registrarUpload,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(GradeReportReviewFilterRequest $request): View
    {
        $staff = $this->requireStaff();
        $departments = $this->departmentAccess->allowedDepartments($staff);
        $departmentIds = $departments->pluck('department_id')->map(fn ($id) => (int) $id)->all();

        $filters = $request->filters($departmentIds);
        $filters['term'] = $filters['term'] ?? AcademicTerm::defaultTerm();
        $filters['year'] = $filters['year'] ?? AcademicTerm::defaultYear();

        if ($request->filled('department_id') && ! $this->departmentAccess->canAccessDepartment($staff, (int) $request->department_id)) {
            abort(403, 'ไม่มีสิทธิ์เข้าถึงสาขานี้');
        }

        $perPage = (int) $request->input('per_page', 20);
        $reports = $this->queryService
            ->baseQuery($filters)
            ->paginate($perPage)
            ->withQueryString();

        return view('dept-admin.reviews.index', [
            'reports' => $reports,
            'departments' => $departments,
            'filters' => $filters,
            'years' => AcademicTerm::yearOptions(),
        ]);
    }

    public function approve(GradeReportApprovalRequest $request, GradeReport $gradeReport): JsonResponse|RedirectResponse
    {
        $this->authorize('reviewDept', $gradeReport);

        try {
            $wasResubmit = $gradeReport->awaitingDeptResubmit();
            $this->approvalService->approve(
                $gradeReport,
                $this->staffUsername(),
                $request->input('remark'),
            );
        } catch (InvalidArgumentException $e) {
            return $this->failureResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse(
            $request,
            $wasResubmit
                ? 'ส่งรายงานผลการสอบไล่อีกครั้งเรียบร้อย'
                : 'บันทึกผ่านการรับรองผลสอบเรียบร้อย',
        );
    }

    public function reject(GradeReportApprovalRequest $request, GradeReport $gradeReport): JsonResponse|RedirectResponse
    {
        $this->authorize('reviewDept', $gradeReport);

        try {
            $this->approvalService->reject(
                $gradeReport,
                $this->staffUsername(),
                $request->input('remark'),
            );
        } catch (InvalidArgumentException $e) {
            return $this->failureResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse($request, 'บันทึกสถานะยังไม่ผ่านการรับรองผลสอบเรียบร้อย');
    }

    public function sendBack(GradeReportApprovalRequest $request, GradeReport $gradeReport): JsonResponse|RedirectResponse
    {
        $this->authorize('reviewDept', $gradeReport);

        try {
            $this->approvalService->sendBackForInstructorEdit(
                $gradeReport,
                $this->staffUsername(),
                $request->input('remark'),
            );
        } catch (InvalidArgumentException $e) {
            return $this->failureResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse($request, 'ส่งกลับให้อาจารย์แก้ไขเรียบร้อย');
    }

    public function revert(GradeReportApprovalRequest $request, GradeReport $gradeReport): JsonResponse|RedirectResponse
    {
        $this->authorize('reviewDept', $gradeReport);

        try {
            $this->approvalService->resetToSaved(
                $gradeReport,
                $this->staffUsername(),
                $request->input('remark'),
            );
        } catch (InvalidArgumentException $e) {
            return $this->failureResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse($request, 'ย้อนสถานะเป็นบันทึกแล้วเรียบร้อย');
    }

    public function previewRegistrarUploads(Request $request): JsonResponse
    {
        $departmentIds = $this->uploadDepartmentIds($request);

        $validated = $request->validate([
            'term' => ['required', 'integer', 'in:1,2,3'],
            'year' => ['required', 'integer', 'min:2500', 'max:2600'],
            'filenames' => ['required', 'array', 'min:1', 'max:40'],
            'filenames.*' => ['required', 'string', 'max:255'],
        ]);

        return response()->json([
            'results' => $this->registrarUpload->preview(
                $validated['filenames'],
                (int) $validated['term'],
                (int) $validated['year'],
                $departmentIds,
            ),
        ]);
    }

    public function uploadRegistrarFiles(DeptRegistrarBulkUploadRequest $request): JsonResponse
    {
        $departmentIds = $this->uploadDepartmentIds($request);

        $uploaded = $request->file('attachments', []);
        if ($uploaded instanceof UploadedFile) {
            $files = [$uploaded];
        } else {
            $files = array_values(array_filter(
                is_array($uploaded) ? $uploaded : [],
                fn ($file) => $file instanceof UploadedFile,
            ));
        }

        $results = $this->registrarUpload->upload(
            $files,
            $this->staffUsername(),
            (int) $request->integer('term'),
            (int) $request->integer('year'),
            $departmentIds,
        );

        $okCount = count(array_filter($results, fn (array $row) => $row['ok'] === true));

        return response()->json([
            'results' => $results,
            'ok_count' => $okCount,
            'fail_count' => count($results) - $okCount,
        ]);
    }

    public function destroyRegistrarFile(Request $request, GradeReport $gradeReport, GradeReportFile $file): JsonResponse
    {
        $this->authorize('reviewDept', $gradeReport);
        abort_unless((int) $file->grade_id === (int) $gradeReport->grade_id, 404);
        abort_unless($file->isDeptAdminUpload($gradeReport), 422, 'ลบได้เฉพาะไฟล์ REG-Admin ที่สาขาอัปโหลด');

        if (! $gradeReport->canDeptDeleteRegistrar()) {
            return response()->json([
                'message' => 'ไม่สามารถลบไฟล์ได้ เนื่องจาก Admin กลางเปลี่ยนสถานะแล้ว',
            ], 422);
        }

        $meta = [
            'grade_id' => $gradeReport->grade_id,
            'file_type' => $file->resolvedType(),
            'original_name' => $file->original_name,
            'source' => 'dept_registrar_delete',
        ];
        $fileId = $file->file_id;

        $file->delete();

        $this->auditLog->record(
            'grade_report_file.delete',
            subjectType: 'grade_report_file',
            subjectId: $fileId,
            metadata: $meta,
            actorRole: 'dept_admin',
        );

        return response()->json(['ok' => true]);
    }

    /**
     * @return list<int>
     */
    private function uploadDepartmentIds(Request $request): array
    {
        $staff = $this->requireStaff();
        $allowed = $this->departmentAccess->allowedDepartments($staff)
            ->pluck('department_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($request->filled('department_id')) {
            $departmentId = (int) $request->input('department_id');
            abort_unless($this->departmentAccess->canAccessDepartment($staff, $departmentId), 403, 'ไม่มีสิทธิ์เข้าถึงสาขานี้');

            return [$departmentId];
        }

        return $allowed;
    }

    private function requireStaff()
    {
        $staff = $this->staffAuth->findByEmail(auth()->user()->email);
        abort_unless($staff, 403, 'ไม่พบข้อมูลเจ้าหน้าที่');

        $this->staffAuth->storeInSession($staff);

        return $staff;
    }

    private function staffUsername(): string
    {
        return (string) session('staff_username', $this->requireStaff()->username);
    }

    private function successResponse(GradeReportApprovalRequest $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return back()->with('status', $message);
    }

    private function failureResponse(GradeReportApprovalRequest $request, string $message, int $code): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $code);
        }

        return back()->withErrors(['approval' => $message]);
    }
}
