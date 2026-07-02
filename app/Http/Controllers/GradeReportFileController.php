<?php

namespace App\Http\Controllers;

use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Services\StaffAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GradeReportFileController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
    ) {}

    public function store(Request $request, GradeReport $gradeReport): JsonResponse
    {
        abort_unless($this->ownsReport($gradeReport), 403);

        if (! $gradeReport->canUploadFiles()) {
            return response()->json([
                'message' => 'ไม่สามารถอัปโหลดไฟล์ได้ เนื่องจากรายงานผ่านการอนุมัติแล้ว กรุณารอเจ้าหน้าที่คืนสถานะเป็นรออนุมัติ',
            ], 422);
        }

        $request->validate([
            'attachment' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ], [
            'attachment.mimes' => 'รองรับเฉพาะไฟล์ PDF',
            'attachment.required' => 'กรุณาเลือกไฟล์ PDF',
        ]);

        $uploaded = $request->file('attachment');
        $storedPath = $uploaded->store(
            'grade-report-files/'.$gradeReport->grade_id,
            'local',
        );

        $file = GradeReportFile::query()->create([
            'grade_id' => $gradeReport->grade_id,
            'original_name' => $uploaded->getClientOriginalName(),
            'stored_path' => $storedPath,
            'uploaded_at' => now(),
            'username' => $this->staffUsername(),
        ]);

        return response()->json($this->formatFile($file), 201);
    }

    public function show(Request $request, GradeReport $gradeReport, GradeReportFile $file): BinaryFileResponse
    {
        abort_unless($this->canViewFiles($gradeReport), 403);
        abort_unless((int) $file->grade_id === (int) $gradeReport->grade_id, 404);

        $absolutePath = Storage::disk('local')->path($file->stored_path);
        abort_unless(is_file($absolutePath), 404);

        return response()->file($absolutePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.addslashes($file->original_name).'"',
        ]);
    }

    public function destroy(Request $request, GradeReport $gradeReport, GradeReportFile $file): JsonResponse
    {
        abort_unless($this->ownsReport($gradeReport), 403);
        abort_unless((int) $file->grade_id === (int) $gradeReport->grade_id, 404);

        if (! $gradeReport->canUploadFiles()) {
            return response()->json([
                'message' => 'ไม่สามารถลบไฟล์ได้ เนื่องจากรายงานผ่านการอนุมัติแล้ว',
            ], 422);
        }

        $file->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatFile(GradeReportFile $file): array
    {
        return [
            'file_id' => $file->file_id,
            'grade_id' => $file->grade_id,
            'original_name' => $file->original_name,
            'uploaded_at' => $file->uploaded_at?->format('Y-m-d H:i'),
            'view_url' => route('grade-reports.files.show', [
                'gradeReport' => $file->grade_id,
                'file' => $file->file_id,
            ]),
        ];
    }

    private function ownsReport(GradeReport $gradeReport): bool
    {
        return $gradeReport->username === $this->staffUsername();
    }

    private function canViewFiles(GradeReport $gradeReport): bool
    {
        if ($this->ownsReport($gradeReport)) {
            return true;
        }

        $role = session('scigrade_role', 'instructor');

        return in_array($role, ['dept_admin', 'faculty_admin'], true);
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
