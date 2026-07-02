<?php

namespace App\Http\Controllers\DeptAdmin;

use App\Http\Controllers\Controller;
use App\Models\DeptSubmission;
use App\Models\DeptSubmissionFile;
use App\Services\DeptAdmin\DeptSubmissionService;
use App\Services\StaffAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DeptSubmissionFileController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly DeptSubmissionService $submissionService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $staff = $this->requireStaff();
        abort_unless(session('scigrade_role') === 'dept_admin', 403);

        $validated = $request->validate([
            'department_id' => ['required', 'integer'],
            'term' => ['required', 'integer', 'in:1,2,3'],
            'year' => ['required', 'integer', 'min:2500', 'max:2600'],
            'attachment' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
        ], [
            'attachment.mimes' => 'รองรับเฉพาะไฟล์ PDF หรือ Word',
            'attachment.required' => 'กรุณาเลือกไฟล์',
        ]);

        $departmentId = (int) $validated['department_id'];
        $this->submissionService->assertDepartmentAccess($staff, $departmentId);

        $submission = $this->submissionService->getOrCreateOpenSubmission(
            $departmentId,
            (int) $validated['term'],
            (int) $validated['year'],
        );

        $uploaded = $request->file('attachment');
        $storedPath = $this->submissionService->storeFile($submission, $uploaded, $staff->username);

        $file = DeptSubmissionFile::query()->create([
            'submission_id' => $submission->submission_id,
            'original_name' => basename($storedPath),
            'stored_path' => $storedPath,
            'uploaded_at' => now(),
            'username' => $staff->username,
        ]);

        return response()->json($this->formatFile($file), 201);
    }

    public function update(Request $request, DeptSubmissionFile $file): JsonResponse
    {
        $staff = $this->requireStaff();
        abort_unless(session('scigrade_role') === 'dept_admin', 403);

        $submission = $file->submission;
        abort_unless($submission, 404);
        $this->submissionService->assertDepartmentAccess($staff, (int) $submission->department_id);

        if (! $this->submissionService->canModify($submission)) {
            throw ValidationException::withMessages([
                'attachment' => 'รอบการส่งนี้ถูกรับเอกสารแล้ว ไม่สามารถแก้ไขไฟล์ได้',
            ]);
        }

        $validated = $request->validate([
            'original_name' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
        ], [
            'attachment.mimes' => 'รองรับเฉพาะไฟล์ PDF หรือ Word',
        ]);

        if ($request->hasFile('attachment')) {
            $storedPath = $this->submissionService->replaceFile($file, $request->file('attachment'));
            $file->stored_path = $storedPath;
            $file->original_name = basename($storedPath);
            $file->uploaded_at = now();
            $file->username = $staff->username;
        } elseif (! empty($validated['original_name'])) {
            $file->original_name = trim($validated['original_name']);
        } else {
            throw ValidationException::withMessages([
                'original_name' => 'กรุณาระบุชื่อไฟล์หรือเลือกไฟล์ใหม่',
            ]);
        }

        $file->save();

        return response()->json($this->formatFile($file->fresh()));
    }

    public function show(DeptSubmissionFile $file): BinaryFileResponse
    {
        $staff = $this->requireStaff();
        $submission = $file->submission;
        abort_unless($submission, 404);

        $role = session('scigrade_role', 'instructor');
        if ($role === 'dept_admin') {
            $this->submissionService->assertDepartmentAccess($staff, (int) $submission->department_id);
        } elseif ($role !== 'faculty_admin') {
            abort(403);
        }

        $absolutePath = Storage::disk('local')->path($file->stored_path);
        abort_unless(is_file($absolutePath), 404);

        return response()->file($absolutePath, [
            'Content-Disposition' => 'inline; filename="'.addslashes($file->original_name).'"',
        ]);
    }

    public function destroy(DeptSubmissionFile $file): JsonResponse
    {
        $staff = $this->requireStaff();
        abort_unless(session('scigrade_role') === 'dept_admin', 403);

        $submission = $file->submission;
        abort_unless($submission, 404);
        $this->submissionService->assertDepartmentAccess($staff, (int) $submission->department_id);

        if (! $this->submissionService->canModify($submission)) {
            return response()->json([
                'message' => 'รอบการส่งนี้ถูกรับเอกสารแล้ว ไม่สามารถลบไฟล์ได้',
            ], 422);
        }

        $file->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatFile(DeptSubmissionFile $file): array
    {
        return [
            'file_id' => $file->file_id,
            'submission_id' => $file->submission_id,
            'original_name' => $file->original_name,
            'uploaded_at' => $file->uploaded_at?->format('d/m/Y H:i'),
            'view_url' => route('dept-submissions.files.show', $file->file_id),
        ];
    }

    private function requireStaff()
    {
        $staff = $this->staffAuth->findByEmail(auth()->user()->email);
        abort_unless($staff, 403, 'ไม่พบข้อมูลเจ้าหน้าที่');
        $this->staffAuth->storeInSession($staff);

        return $staff;
    }
}
