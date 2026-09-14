<?php

namespace App\Http\Controllers\DeptAdmin;

use App\Http\Controllers\Controller;
use App\Models\ThesisGrade;
use App\Models\ThesisGradeFile;
use App\Models\ThesisGradeStudent;
use App\Services\AuditLogService;
use App\Services\DeptAdmin\DepartmentAccessService;
use App\Services\StaffAuthService;
use App\Services\ThesisGrade\ThesisGradeApprovalService;
use App\Services\ThesisGrade\ThesisGradeAttachmentNameService;
use App\Services\ThesisGrade\ThesisGradeDocxExportService;
use App\Services\ThesisGrade\ThesisGradeQueryService;
use App\Services\ThesisGrade\ThesisGradeZipService;
use App\Support\AcademicTerm;
use App\Support\ThesisGradeS0Letter;
use App\Support\UploadStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ThesisGradeReviewController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly DepartmentAccessService $departmentAccess,
        private readonly ThesisGradeQueryService $queryService,
        private readonly ThesisGradeApprovalService $approval,
        private readonly ThesisGradeZipService $zipService,
        private readonly ThesisGradeAttachmentNameService $names,
        private readonly ThesisGradeDocxExportService $docxExport,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        $staff = $this->requireStaff();
        $departments = $this->departmentAccess->allowedDepartments($staff);
        $departmentIds = $departments->pluck('department_id')->map(fn ($id) => (int) $id)->all();

        $filters = [
            'term' => (int) $request->input('term', AcademicTerm::defaultTerm()),
            'year' => (int) $request->input('year', AcademicTerm::defaultYear()),
            'status' => (string) $request->input('status', ThesisGrade::STATUS_SUBMITTED),
            'department_id' => $request->filled('department_id') ? (int) $request->input('department_id') : null,
            'subject_code' => trim((string) $request->input('subject_code', '')),
            'q' => trim((string) $request->input('q', '')),
        ];

        if ($filters['department_id'] && ! $this->departmentAccess->canAccessDepartment($staff, $filters['department_id'])) {
            abort(403, 'ไม่มีสิทธิ์เข้าถึงสาขานี้');
        }

        $reports = $this->queryService
            ->deptQuery($departmentIds, $filters)
            ->paginate((int) $request->input('per_page', 20))
            ->withQueryString();

        return view('dept-admin.thesis-grades.index', [
            'reports' => $reports,
            'departments' => $departments,
            'filters' => $filters,
            'years' => AcademicTerm::yearOptions(),
        ]);
    }

    public function show(ThesisGrade $thesisGrade): View
    {
        $this->authorize('reviewDept', $thesisGrade);
        $thesisGrade->load(['students', 'files']);

        return view('dept-admin.thesis-grades.show', [
            'report' => $thesisGrade,
            's0FormUrl' => (string) config('scigrade.s0_letter_form_url'),
        ]);
    }

    public function receive(ThesisGrade $thesisGrade): RedirectResponse
    {
        $this->authorize('reviewDept', $thesisGrade);

        try {
            $this->approval->receive($thesisGrade, $this->staffUsername());
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditLog->record('thesis_grade.receive', 'thesis_grade', $thesisGrade->thesis_grade_id, [
            'subject_code' => $thesisGrade->subject_code,
            'section' => $thesisGrade->section,
        ], actorRole: 'dept_admin');

        return back()->with('status', 'ผ่านที่ประชุมสาขาฯ เรียบร้อย');
    }

    public function showFile(ThesisGrade $thesisGrade, ThesisGradeFile $file): StreamedResponse
    {
        $this->authorize('reviewDept', $thesisGrade);
        abort_unless((int) $file->thesis_grade_id === (int) $thesisGrade->thesis_grade_id, 404);

        return UploadStorage::inlineResponse($file->stored_path, $file->original_name, 'application/pdf');
    }

    public function downloadReport(ThesisGrade $thesisGrade): BinaryFileResponse
    {
        $this->authorize('reviewDept', $thesisGrade);
        $thesisGrade->load('files');

        try {
            return $this->zipService->downloadReports(
                collect([$thesisGrade]),
                preg_replace('/\.pdf$/i', '-files.zip', $thesisGrade->tsFilename()) ?: 'thesis-files.zip',
            );
        } catch (RuntimeException $e) {
            abort(404, $e->getMessage());
        }
    }

    public function downloadSelected(Request $request): BinaryFileResponse|RedirectResponse
    {
        $staff = $this->requireStaff();
        $departmentIds = $this->departmentAccess->allowedDepartmentIds($staff);
        $ids = array_values(array_filter(array_map('intval', (array) $request->input('ids', []))));

        if ($ids === [] && $request->boolean('all_filtered')) {
            $filters = [
                'term' => $request->filled('term') ? (int) $request->input('term') : null,
                'year' => $request->filled('year') ? (int) $request->input('year') : null,
                'status' => (string) $request->input('status', ''),
                'department_id' => $request->filled('department_id') ? (int) $request->input('department_id') : null,
                'subject_code' => trim((string) $request->input('subject_code', '')),
                'q' => trim((string) $request->input('q', '')),
            ];
            $reports = $this->queryService
                ->deptQuery($departmentIds, $filters)
                ->with('files')
                ->get();
        } else {
            if ($ids === []) {
                return back()->with('error', 'เลือกอย่างน้อย 1 รายการ หรือดาวน์โหลดทั้งหมดตามเงื่อนไข');
            }

            $reports = $this->queryService
                ->deptQuery($departmentIds, [])
                ->whereIn('thesis_grade_id', $ids)
                ->with('files')
                ->get();
        }

        if ($reports->isEmpty()) {
            return back()->with('error', 'ไม่พบรายการที่เลือก');
        }

        try {
            return $this->zipService->downloadReports(
                $reports,
                'TS-dept-'.now()->format('Ymd-His').'.zip',
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function storeChairFiles(Request $request, ThesisGrade $thesisGrade): JsonResponse|RedirectResponse
    {
        $this->authorize('reviewDept', $thesisGrade);
        abort_unless(in_array($thesisGrade->status, [ThesisGrade::STATUS_SUBMITTED, ThesisGrade::STATUS_RECEIVED], true), 403);

        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'mimes:pdf', 'max:15360'],
        ], [
            'files.*.mimes' => 'อัปโหลดได้เฉพาะไฟล์ PDF',
            'files.*.max' => 'ขนาดไฟล์ต้องไม่เกิน 15 MB',
        ]);

        $saved = [];
        /** @var list<UploadedFile> $uploads */
        $uploads = $validated['files'];
        foreach ($uploads as $uploaded) {
            $storedPath = $this->names->storeUploadedFile(
                $thesisGrade,
                $uploaded,
                ThesisGradeFile::TYPE_CHAIR_SIGNED,
            );
            $file = ThesisGradeFile::query()->create([
                'thesis_grade_id' => $thesisGrade->thesis_grade_id,
                'student_id' => null,
                'file_type' => ThesisGradeFile::TYPE_CHAIR_SIGNED,
                'original_name' => basename($storedPath),
                'stored_path' => $storedPath,
                'uploaded_at' => now(),
                'username' => $this->staffUsername(),
            ]);
            $saved[] = [
                'file_id' => $file->file_id,
                'original_name' => $file->original_name,
                'url' => route('dept-admin.thesis-grades.files.show', [$thesisGrade, $file]),
            ];
        }

        if ($request->expectsJson()) {
            return response()->json(['files' => $saved]);
        }

        return back()->with('status', 'อัปโหลดไฟล์ประธานหลักสูตรแล้ว '.count($saved).' ไฟล์');
    }

    public function destroyChairFile(ThesisGrade $thesisGrade, ThesisGradeFile $file): RedirectResponse
    {
        $this->authorize('reviewDept', $thesisGrade);
        abort_unless((int) $file->thesis_grade_id === (int) $thesisGrade->thesis_grade_id, 404);
        abort_unless($file->isChairSigned(), 404);
        abort_unless(in_array($thesisGrade->status, [ThesisGrade::STATUS_SUBMITTED, ThesisGrade::STATUS_RECEIVED], true), 403);

        $file->delete();

        return back()->with('status', 'ลบไฟล์ประธานหลักสูตรแล้ว');
    }

    public function s0Letter(Request $request, ThesisGrade $thesisGrade, ThesisGradeStudent $student): View
    {
        $this->authorize('reviewDept', $thesisGrade);
        abort_unless((int) $student->thesis_grade_id === (int) $thesisGrade->thesis_grade_id, 404);

        $back = (string) $request->headers->get('referer', '');
        $appUrl = rtrim((string) config('app.url'), '/');
        $backUrl = $back !== '' && str_starts_with($back, $appUrl)
            ? $back
            : route('dept-admin.thesis-grades.show', $thesisGrade);

        return view('thesis-grades.s0-letter', [
            'fields' => ThesisGradeS0Letter::fields($thesisGrade, $student),
            'backUrl' => $backUrl,
            'officialFormUrl' => (string) config('scigrade.s0_letter_form_url'),
            'docxUrl' => route('dept-admin.thesis-grades.s0.docx', [$thesisGrade, $student]),
        ]);
    }

    public function exportS0(ThesisGrade $thesisGrade, ThesisGradeStudent $student): BinaryFileResponse
    {
        $this->authorize('reviewDept', $thesisGrade);
        abort_unless((int) $student->thesis_grade_id === (int) $thesisGrade->thesis_grade_id, 404);

        return $this->docxExport->downloadS0Letter($thesisGrade, $student);
    }

    private function requireStaff()
    {
        $staff = $this->staffAuth->findByEmail(auth()->user()->email);
        abort_if($staff === null, 403, 'ไม่พบข้อมูลบุคลากรในระบบ');
        $this->staffAuth->storeInSession($staff);

        return $staff;
    }

    private function staffUsername(): string
    {
        return (string) (session('staff_username') ?: $this->requireStaff()->username);
    }
}
