<?php

namespace App\Http\Controllers;

use App\Models\ThesisGrade;
use App\Models\ThesisGradeFile;
use App\Models\ThesisGradeStudent;
use App\Services\StaffAuthService;
use App\Services\ThesisGrade\PdfSignatureInspector;
use App\Services\ThesisGrade\ThesisGradeAttachmentNameService;
use App\Support\UploadStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ThesisGradeFileController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly ThesisGradeAttachmentNameService $names,
        private readonly PdfSignatureInspector $signatures,
    ) {}

    public function store(Request $request, ThesisGrade $thesisGrade): JsonResponse
    {
        $this->authorize('update', $thesisGrade);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:15360'],
            'file_type' => ['required', 'string', Rule::in(ThesisGradeFile::instructorUploadTypes())],
            'student_id' => ['nullable', 'integer'],
        ], [
            'file.mimes' => 'อัปโหลดได้เฉพาะไฟล์ PDF',
            'file.max' => 'ขนาดไฟล์ต้องไม่เกิน 15 MB',
        ]);

        $student = null;
        if ($validated['file_type'] === ThesisGradeFile::TYPE_S0_LETTER) {
            $studentId = (int) ($validated['student_id'] ?? 0);
            $student = $thesisGrade->students()->whereKey($studentId)->first();
            if (! $student) {
                return response()->json(['message' => 'เลือกนักศึกษาสำหรับหนังสือชี้แจง S=0'], 422);
            }
        }

        /** @var UploadedFile $uploaded */
        $uploaded = $validated['file'];

        $signature = ['signed' => true, 'status' => 'signed', 'message' => ''];
        if ($validated['file_type'] === ThesisGradeFile::TYPE_TS_REPORT) {
            $signature = $this->signatures->inspectUploaded($uploaded);
        }

        $storedPath = $this->names->storeUploadedFile(
            $thesisGrade,
            $uploaded,
            $validated['file_type'],
            $student,
        );

        $file = ThesisGradeFile::query()->create([
            'thesis_grade_id' => $thesisGrade->thesis_grade_id,
            'student_id' => $student?->student_id,
            'file_type' => $validated['file_type'],
            'original_name' => basename($storedPath),
            'stored_path' => $storedPath,
            'uploaded_at' => now(),
            'username' => $this->staffUsername(),
        ]);

        return response()->json([
            'file' => $this->formatFile($file, $student),
            'preview_name' => $file->original_name,
            'signature_status' => $signature['status'],
            'signature_signed' => $signature['signed'],
            'signature_message' => $signature['message'],
            'show_next_actions' => $validated['file_type'] === ThesisGradeFile::TYPE_TS_REPORT,
            'create_url' => route('thesis-grades.create', [
                'term' => $thesisGrade->term,
                'year' => $thesisGrade->year,
            ]),
            'index_url' => route('thesis-grades.index', [
                'term' => $thesisGrade->term,
                'year' => $thesisGrade->year,
            ]),
        ]);
    }

    public function show(ThesisGrade $thesisGrade, ThesisGradeFile $file): StreamedResponse
    {
        $this->authorize('view', $thesisGrade);
        abort_unless((int) $file->thesis_grade_id === (int) $thesisGrade->thesis_grade_id, 404);

        return UploadStorage::inlineResponse($file->stored_path, $file->original_name, 'application/pdf');
    }

    public function destroy(ThesisGrade $thesisGrade, ThesisGradeFile $file): JsonResponse
    {
        $this->authorize('update', $thesisGrade);
        abort_unless((int) $file->thesis_grade_id === (int) $thesisGrade->thesis_grade_id, 404);

        $file->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatFile(ThesisGradeFile $file, ?ThesisGradeStudent $student = null): array
    {
        return [
            'file_id' => $file->file_id,
            'file_type' => $file->resolvedType(),
            'type_label' => $file->typeLabel(),
            'original_name' => $file->original_name,
            'student_id' => $file->student_id,
            'student_code' => $student?->student_code,
            'url' => route('thesis-grades.files.show', [$file->thesis_grade_id, $file->file_id]),
        ];
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

        return (string) ($username ?: '');
    }
}
