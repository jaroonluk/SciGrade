<?php

namespace App\Services\Instructor;

use App\Enums\GradeApprovalStatus;
use App\Models\GradeReport;
use App\Models\GradeReportApprovalLog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GradeReportSubmissionService
{
    public function submitCorrections(GradeReport $report, string $instructorUsername): GradeReport
    {
        return DB::connection('scigrad')->transaction(function () use ($report, $instructorUsername) {
            $report = GradeReport::query()->lockForUpdate()->findOrFail($report->grade_id);

            if ($report->username !== $instructorUsername) {
                throw new InvalidArgumentException('ไม่มีสิทธิ์ส่งรายการนี้');
            }

            if ((int) $report->approv !== GradeApprovalStatus::DepartmentRejected->value) {
                throw new InvalidArgumentException('รายการนี้ไม่อยู่ในสถานะที่สามารถส่งการแก้ไขได้');
            }

            if ($report->gradeStds()->doesntExist()) {
                throw new InvalidArgumentException('กรุณากรอกจำนวนนักศึกษาก่อนส่งการแก้ไข');
            }

            $from = (int) $report->approv;

            $report->update([
                'approv' => GradeApprovalStatus::Saved->value,
            ]);

            GradeReportApprovalLog::query()->create([
                'grade_id' => $report->grade_id,
                'action' => 'instructor_resubmitted',
                'from_status' => $from,
                'to_status' => GradeApprovalStatus::Saved->value,
                'approver_username' => $instructorUsername,
                'approver_role' => 'instructor',
                'remark' => null,
                'created_at' => now(),
            ]);

            return $report->fresh(['gradeStds', 'files', 'approvalLogs.approver']);
        });
    }
}
