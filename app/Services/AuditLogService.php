<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Support\SciGradeRole;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditLogService
{
    /**
     * บันทึก audit log — ไม่ทำให้ flow หลักล้มถ้าตารางยังไม่มีหรือ insert ไม่สำเร็จ
     *
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $event,
        ?string $subjectType = null,
        string|int|null $subjectId = null,
        array $metadata = [],
        ?string $actorUsername = null,
        ?string $actorRole = null,
    ): void {
        try {
            $request = request();
            $isImpersonating = SciGradeRole::isImpersonating();

            $actor = $actorUsername ?? session('staff_username');
            $actor = $actor !== null && $actor !== '' ? (string) $actor : null;

            $impersonator = null;
            if ($isImpersonating) {
                $impersonator = SciGradeRole::realStaffUsername();
                // ตอน impersonate: actor = คนที่ถูกเข้าแทน, impersonator = Super Admin จริง
                if ($actorUsername === null) {
                    $actor = session('staff_username') ? (string) session('staff_username') : $actor;
                }
            }

            $userAgent = $request?->userAgent();
            if (is_string($userAgent) && strlen($userAgent) > 500) {
                $userAgent = substr($userAgent, 0, 500);
            }

            $path = $request?->path();
            if (is_string($path) && strlen($path) > 255) {
                $path = substr($path, 0, 255);
            }

            AuditLog::query()->create([
                'event' => $event,
                'actor_username' => $actor,
                'actor_role' => $actorRole ?? (session()->has('scigrade_role') ? SciGradeRole::current() : null),
                'impersonator_username' => $impersonator,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId !== null ? (string) $subjectId : null,
                'ip_address' => $request?->ip(),
                'user_agent' => $userAgent,
                'request_method' => $request?->method(),
                'request_path' => $path,
                'metadata' => $metadata === [] ? null : $metadata,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to write audit_log_scigrad', [
                'event' => $event,
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);
        }
    }
}
