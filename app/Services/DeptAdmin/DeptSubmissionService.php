<?php

namespace App\Services\DeptAdmin;

use App\Models\DeptSubmission;
use App\Models\DeptSubmissionFile;
use App\Models\TblUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeptSubmissionService
{
    public function openSubmission(int $departmentId, int $term, int $year): ?DeptSubmission
    {
        return DeptSubmission::query()
            ->with('files')
            ->where('department_id', $departmentId)
            ->where('term', $term)
            ->where('year', $year)
            ->where('status', DeptSubmission::STATUS_OPEN)
            ->orderByDesc('submission_id')
            ->first();
    }

    public function getOrCreateOpenSubmission(int $departmentId, int $term, int $year): DeptSubmission
    {
        $existing = $this->openSubmission($departmentId, $term, $year);
        if ($existing) {
            return $existing;
        }

        $now = now();

        return DeptSubmission::query()->create([
            'department_id' => $departmentId,
            'term' => $term,
            'year' => $year,
            'status' => DeptSubmission::STATUS_OPEN,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function canModify(DeptSubmission $submission): bool
    {
        return $submission->isOpen();
    }

    public function storeFile(DeptSubmission $submission, UploadedFile $uploaded, string $username): string
    {
        if (! $this->canModify($submission)) {
            throw ValidationException::withMessages([
                'attachment' => 'รอบการส่งนี้ถูกรับเอกสารแล้ว ไม่สามารถอัปโหลดไฟล์ได้',
            ]);
        }

        $directory = 'dept-submission-files/'.$submission->submission_id;
        $filename = $this->uniqueFilename($directory, $uploaded->getClientOriginalName());

        return $uploaded->storeAs($directory, $filename, 'local');
    }

    public function replaceFile(DeptSubmissionFile $file, UploadedFile $uploaded): string
    {
        $submission = $file->submission;
        if (! $submission || ! $this->canModify($submission)) {
            throw ValidationException::withMessages([
                'attachment' => 'ไม่สามารถแก้ไขไฟล์ในรอบที่รับเอกสารแล้ว',
            ]);
        }

        Storage::disk('local')->delete($file->stored_path);

        $directory = 'dept-submission-files/'.$submission->submission_id;
        $filename = $this->uniqueFilename($directory, $uploaded->getClientOriginalName(), $file->file_id);

        return $uploaded->storeAs($directory, $filename, 'local');
    }

    public function receiveSubmission(DeptSubmission $submission, string $username): DeptSubmission
    {
        if (! $submission->isOpen()) {
            throw ValidationException::withMessages([
                'submission' => 'รอบการส่งนี้ถูกรับเอกสารแล้ว',
            ]);
        }

        $submission->update([
            'status' => DeptSubmission::STATUS_RECEIVED,
            'received_at' => now(),
            'received_by' => $username,
            'updated_at' => now(),
        ]);

        return $submission->fresh(['department', 'files']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, DeptSubmission>
     */
    public function openSubmissionsForFaculty()
    {
        return DeptSubmission::query()
            ->with(['department', 'files'])
            ->where('status', DeptSubmission::STATUS_OPEN)
            ->orderBy('year')
            ->orderBy('term')
            ->orderBy('department_id')
            ->get();
    }

    public function assertDepartmentAccess(TblUser $staff, int $departmentId): void
    {
        $access = app(DepartmentAccessService::class);
        abort_unless($access->canAccessDepartment($staff, $departmentId), 403, 'ไม่มีสิทธิ์จัดการสาขานี้');
    }

    private function uniqueFilename(string $directory, string $originalName, ?int $ignoreFileId = null): string
    {
        $base = pathinfo($originalName, PATHINFO_FILENAME);
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $safeBase = preg_replace('/[^\p{L}\p{N}\-_]+/u', '_', $base) ?: 'file';
        $safeExt = $ext !== '' ? '.'.strtolower($ext) : '';

        $candidate = $safeBase.$safeExt;
        $counter = 2;

        while ($this->filenameExistsInDirectory($directory, $candidate, $ignoreFileId)) {
            $candidate = $safeBase.'_'.$counter.$safeExt;
            $counter++;
        }

        return $candidate;
    }

    private function filenameExistsInDirectory(string $directory, string $filename, ?int $ignoreFileId = null): bool
    {
        if (Storage::disk('local')->exists($directory.'/'.$filename)) {
            return true;
        }

        $query = DeptSubmissionFile::query()->where('stored_path', $directory.'/'.$filename);
        if ($ignoreFileId) {
            $query->where('file_id', '!=', $ignoreFileId);
        }

        return $query->exists();
    }
}
