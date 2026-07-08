<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FacultyAdmin\GradeReportApprovalRequest;
use App\Http\Requests\FacultyAdmin\GradeReportBulkApprovalRequest;
use App\Http\Requests\FacultyAdmin\GradeReportReviewFilterRequest;
use App\Models\GradeReport;
use App\Services\FacultyAdmin\FacultyReportQueryService;
use App\Services\FacultyAdmin\GradeReportCentralApprovalService;
use App\Services\StaffAuthService;
use App\Support\AcademicTerm;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class GradeReportReviewController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly FacultyReportQueryService $queryService,
        private readonly GradeReportCentralApprovalService $approvalService,
    ) {}

    public function index(GradeReportReviewFilterRequest $request): View
    {
        $this->requireStaff();

        $filters = $request->filters();
        $filters['term'] = $filters['term'] ?? AcademicTerm::defaultTerm();
        $filters['year'] = $filters['year'] ?? AcademicTerm::defaultYear();

        if (! $request->has('status')) {
            $filters['status'] = 1;
        }

        $filters['sort_by'] = $filters['sort_by'] ?: 'subject_code';
        $filters['sort_dir'] = $filters['sort_dir'] ?: 'asc';

        $reports = $this->queryService
            ->baseQuery($filters)
            ->paginate((int) $request->input('per_page', 20))
            ->withQueryString();

        return view('faculty-admin.reviews.index', [
            'reports' => $reports,
            'departments' => $this->queryService->filterDepartments(),
            'filters' => $filters,
            'years' => AcademicTerm::yearOptions(),
            'queryService' => $this->queryService,
        ]);
    }

    public function approve(GradeReportApprovalRequest $request, GradeReport $gradeReport): JsonResponse|RedirectResponse
    {
        $this->requireStaff();
        $this->authorize('reviewFaculty', $gradeReport);

        try {
            $this->approvalService->approve(
                $gradeReport,
                $this->staffUsername(),
                $request->input('remark'),
            );
        } catch (InvalidArgumentException $e) {
            return $this->failureResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse($request, 'บันทึกผ่านการรับรองผลสอบระดับคณะเรียบร้อย');
    }

    public function reject(GradeReportApprovalRequest $request, GradeReport $gradeReport): JsonResponse|RedirectResponse
    {
        $this->requireStaff();
        $this->authorize('reviewFaculty', $gradeReport);

        try {
            $this->approvalService->reject(
                $gradeReport,
                $this->staffUsername(),
                $request->input('remark'),
            );
        } catch (InvalidArgumentException $e) {
            return $this->failureResponse($request, $e->getMessage(), 422);
        }

        return $this->successResponse($request, 'บันทึกส่งกลับแก้ไขเรียบร้อย');
    }

    public function bulkApprove(GradeReportBulkApprovalRequest $request): JsonResponse|RedirectResponse
    {
        $this->requireStaff();

        $approved = 0;
        $skipped = 0;
        $errors = [];

        foreach ($request->gradeIds() as $gradeId) {
            $report = GradeReport::query()->find($gradeId);
            if (! $report) {
                $skipped++;
                continue;
            }

            try {
                $this->authorize('reviewFaculty', $report);
                $this->approvalService->approve(
                    $report,
                    $this->staffUsername(),
                    $request->input('remark'),
                );
                $approved++;
            } catch (AuthorizationException) {
                $skipped++;
                $errors[] = "รายการ {$report->subject_code}: ไม่มีสิทธิ์อนุมัติ";
            } catch (InvalidArgumentException $e) {
                $skipped++;
                $errors[] = "รายการ {$report->subject_code}: {$e->getMessage()}";
            }
        }

        if ($approved === 0 && $skipped > 0) {
            return $this->failureResponse($request, 'ไม่สามารถอนุมัติรายการที่เลือกได้', 422, $errors);
        }

        $message = "อนุมัติระดับคณะสำเร็จ {$approved} รายการ";
        if ($skipped > 0) {
            $message .= " (ข้าม {$skipped} รายการ)";
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'approved' => $approved,
                'skipped' => $skipped,
                'errors' => $errors,
            ]);
        }

        return back()
            ->with('status', $message)
            ->withErrors($errors ? ['approval' => implode("\n", $errors)] : []);
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

    private function failureResponse(GradeReportApprovalRequest|GradeReportBulkApprovalRequest $request, string $message, int $code, array $errors = []): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'errors' => $errors], $code);
        }

        return back()->withErrors(['approval' => $errors ? implode("\n", $errors) : $message]);
    }
}
