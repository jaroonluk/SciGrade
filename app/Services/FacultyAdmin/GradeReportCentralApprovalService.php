<?php

namespace App\Services\FacultyAdmin;

use App\Enums\GradeApprovalStatus;
use App\Models\GradeReport;
use App\Models\GradeReportApprovalLog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GradeReportCentralApprovalService
{
    public function approve(GradeReport $report, string $approverUsername, ?string $remark = null): GradeReport
    {
        return DB::connection('scigrad')->transaction(function () use ($report, $approverUsername, $remark) {
            $report = GradeReport::query()->lockForUpdate()->findOrFail($report->grade_id);
            $from = (int) $report->approv;

            if ($from === GradeApprovalStatus::CentralApproved->value) {
                throw new InvalidArgumentException('รายการผ่านการอนุมัติคณะแล้ว');
            }

            if ($from !== GradeApprovalStatus::DepartmentApproved->value) {
                throw new InvalidArgumentException('สามารถอนุมัติระดับคณะได้เฉพาะรายการที่สาขาอนุมัติแล้วเท่านั้น');
            }

            $report->update([
                'approv' => GradeApprovalStatus::CentralApproved->value,
                'dateapprove2' => now()->toDateString(),
            ]);

            $this->writeLog($report, 'central_approved', $from, GradeApprovalStatus::CentralApproved->value, $approverUsername, $remark);

            return $report->fresh(['gradeStds', 'files', 'latestCentralApprovalLog.approver']);
        });
    }

    public function reject(GradeReport $report, string $approverUsername, ?string $remark = null): GradeReport
    {
        return DB::connection('scigrad')->transaction(function () use ($report, $approverUsername, $remark) {
            $report = GradeReport::query()->lockForUpdate()->findOrFail($report->grade_id);
            $from = (int) $report->approv;

            if ($from === GradeApprovalStatus::CentralApproved->value) {
                throw new InvalidArgumentException('รายการผ่านการอนุมัติคณะแล้ว ไม่สามารถส่งกลับได้');
            }

            if ($from !== GradeApprovalStatus::DepartmentApproved->value) {
                throw new InvalidArgumentException('สามารถไม่อนุมัติระดับคณะได้เฉพาะรายการที่สาขาอนุมัติแล้วเท่านั้น');
            }

            $report->update([
                'approv' => GradeApprovalStatus::DepartmentRejected->value,
                'reason' => $remark ?? $report->reason,
                'dateapprove2' => now()->toDateString(),
            ]);

            $this->writeLog($report, 'central_rejected', $from, GradeApprovalStatus::DepartmentRejected->value, $approverUsername, $remark);

            return $report->fresh(['gradeStds', 'files', 'latestCentralApprovalLog.approver']);
        });
    }

    public function sendBackForInstructorEdit(GradeReport $report, string $approverUsername, ?string $remark = null): GradeReport
    {
        return DB::connection('scigrad')->transaction(function () use ($report, $approverUsername, $remark) {
            $report = GradeReport::query()->lockForUpdate()->findOrFail($report->grade_id);
            $from = (int) $report->approv;

            if ($from !== GradeApprovalStatus::CentralApproved->value) {
                throw new InvalidArgumentException('สามารถส่งกลับให้อาจารย์แก้ไขได้เฉพาะรายการที่คณะอนุมัติแล้วเท่านั้น');
            }

            $report->update([
                'approv' => GradeApprovalStatus::DepartmentRejected->value,
                'reason' => $remark ?? 'ส่งกลับให้อาจารย์แก้ไข',
                'dateapprove2' => null,
            ]);

            $this->writeLog(
                $report,
                'central_send_back',
                $from,
                GradeApprovalStatus::DepartmentRejected->value,
                $approverUsername,
                $remark,
            );

            return $report->fresh(['gradeStds', 'files', 'latestCentralApprovalLog.approver']);
        });
    }

    private function writeLog(
        GradeReport $report,
        string $action,
        int $from,
        int $to,
        string $approverUsername,
        ?string $remark,
    ): void {
        GradeReportApprovalLog::query()->create([
            'grade_id' => $report->grade_id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'approver_username' => $approverUsername,
            'approver_role' => 'faculty_admin',
            'remark' => $remark,
            'created_at' => now(),
        ]);
    }
}
