<?php

namespace App\Http\Controllers;

use App\Models\DeptSubmission;
use App\Services\DeptAdmin\DeptSubmissionService;
use App\Services\StaffAuthService;
use App\Support\SciGradeRole;
use Illuminate\Http\JsonResponse;

class FacultyDeptSubmissionController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly DeptSubmissionService $submissionService,
    ) {}

    public function receive(DeptSubmission $submission): JsonResponse
    {
        abort_unless(SciGradeRole::isFacultyCapable(), 403);

        $staff = $this->staffAuth->findByEmail(auth()->user()->email);
        abort_unless($staff, 403, 'ไม่พบข้อมูลเจ้าหน้าที่');
        $this->staffAuth->storeInSession($staff);

        $updated = $this->submissionService->receiveSubmission($submission, $staff->username);

        return response()->json([
            'submission_id' => $updated->submission_id,
            'status' => $updated->status,
            'status_label' => $updated->statusLabel(),
            'received_at' => $updated->received_at?->format('d/m/Y H:i'),
        ]);
    }
}
