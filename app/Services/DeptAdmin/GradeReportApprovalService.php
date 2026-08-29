<?php

namespace App\Services\DeptAdmin;

use App\Enums\GradeApprovalStatus;
use App\Models\GradeReport;
use App\Models\GradeReportApprovalLog;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GradeReportApprovalService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}
    public function approve(GradeReport $report, string $approverUsername, ?string $remark = null): GradeReport
    {
        return DB::connection('scigrad')->transaction(function () use ($report, $approverUsername, $remark) {
            $report = GradeReport::query()->lockForUpdate()->findOrFail($report->grade_id);
            $from = (int) $report->approv;

            if ($from === GradeApprovalStatus::CentralApproved->value) {
                throw new InvalidArgumentException('รายการผ่านการอนุมัติคณะแล้ว ไม่สามารถเปลี่ยนสถานะจากสาขาได้');
            }

            if (in_array($from, GradeApprovalStatus::facultyReviewableValues(), true)) {
                throw new InvalidArgumentException('รายการผ่านการรับรองผลสอบแล้ว');
            }

            if ($from !== GradeApprovalStatus::Saved->value) {
                throw new InvalidArgumentException('รายการนี้ไม่อยู่ในสถานะที่สามารถอนุมัติได้');
            }

            $report->update([
                'approv' => GradeApprovalStatus::DepartmentApproved->value,
                'dateapprove1' => now()->toDateString(),
            ]);

            $this->writeLog($report, 'department_approved', $from, GradeApprovalStatus::DepartmentApproved->value, $approverUsername, $remark);

            return $report->fresh(['gradeStds', 'files', 'latestDeptApprovalLog.approver']);
        });
    }

    public function reject(GradeReport $report, string $approverUsername, ?string $remark = null): GradeReport
    {
        return DB::connection('scigrad')->transaction(function () use ($report, $approverUsername, $remark) {
            $report = GradeReport::query()->lockForUpdate()->findOrFail($report->grade_id);
            $from = (int) $report->approv;

            if ($from === GradeApprovalStatus::CentralApproved->value) {
                throw new InvalidArgumentException('รายการผ่านการอนุมัติคณะแล้ว ไม่สามารถเปลี่ยนสถานะจากสาขาได้');
            }

            if (in_array($from, GradeApprovalStatus::facultyReviewableValues(), true)) {
                throw new InvalidArgumentException('รายการผ่านการรับรองผลสอบแล้ว ไม่สามารถเปลี่ยนเป็นไม่ผ่านได้');
            }

            if ($from !== GradeApprovalStatus::Saved->value) {
                throw new InvalidArgumentException('รายการนี้ไม่อยู่ในสถานะที่สามารถไม่อนุมัติได้');
            }

            $report->update([
                'approv' => GradeApprovalStatus::DepartmentRejected->value,
                'reason' => $remark ?? $report->reason,
                'dateapprove2' => now()->toDateString(),
            ]);

            $this->writeLog($report, 'department_rejected', $from, GradeApprovalStatus::DepartmentRejected->value, $approverUsername, $remark);

            return $report->fresh(['gradeStds', 'files', 'latestDeptApprovalLog.approver']);
        });
    }

    public function sendBackForInstructorEdit(GradeReport $report, string $approverUsername, ?string $remark = null): GradeReport
    {
        return DB::connection('scigrad')->transaction(function () use ($report, $approverUsername, $remark) {
            $report = GradeReport::query()->lockForUpdate()->findOrFail($report->grade_id);
            $from = (int) $report->approv;

            if (in_array($from, GradeApprovalStatus::facultyReviewableValues(), true)) {
                throw new InvalidArgumentException('รายการผ่านการรับรองผลสอบแล้ว ไม่สามารถส่งกลับให้แก้ไขได้');
            }

            if ($from !== GradeApprovalStatus::Saved->value) {
                throw new InvalidArgumentException('สามารถส่งกลับให้อาจารย์แก้ไขได้เฉพาะรายการที่อาจารย์ส่งแล้วและยังไม่ผ่านการรับรองจากสาขา');
            }

            if ($report->awaitingDeptResubmit()) {
                throw new InvalidArgumentException('รายการนี้รอส่งรายงานผลการสอบไล่อีกครั้ง ไม่สามารถส่งกลับให้แก้ไขได้');
            }

            $report->update([
                'approv' => GradeApprovalStatus::DepartmentRejected->value,
                'reason' => $remark ?? 'ส่งกลับให้อาจารย์แก้ไข',
            ]);

            $this->writeLog(
                $report,
                'department_send_back',
                $from,
                GradeApprovalStatus::DepartmentRejected->value,
                $approverUsername,
                $remark,
            );

            return $report->fresh(['gradeStds', 'files', 'latestDeptApprovalLog.approver', 'approvalLogs']);
        });
    }

    public function resetToSaved(GradeReport $report, string $approverUsername, ?string $remark = null): GradeReport
    {
        return DB::connection('scigrad')->transaction(function () use ($report, $approverUsername, $remark) {
            $report = GradeReport::query()->lockForUpdate()->findOrFail($report->grade_id);
            $from = (int) $report->approv;

            if ($from === GradeApprovalStatus::CentralApproved->value) {
                throw new InvalidArgumentException('รายการผ่านการอนุมัติคณะแล้ว ไม่สามารถเปลี่ยนสถานะจากสาขาได้');
            }

            if ($from === GradeApprovalStatus::FacultyChecked->value) {
                throw new InvalidArgumentException('รายการถูกตรวจเอกสารแล้ว ไม่สามารถเปลี่ยนสถานะจากสาขาได้');
            }

            if ($from !== GradeApprovalStatus::DepartmentApproved->value) {
                throw new InvalidArgumentException('สามารถเปลี่ยนกลับเป็น “ส่งแล้ว” ได้เฉพาะรายการที่ผ่านสาขาฯ แล้วเท่านั้น');
            }

            $report->update([
                'approv' => GradeApprovalStatus::Saved->value,
                'dateapprove1' => null,
            ]);

            $this->writeLog($report, 'department_reset', $from, GradeApprovalStatus::Saved->value, $approverUsername, $remark);

            return $report->fresh(['gradeStds', 'files', 'latestDeptApprovalLog.approver']);
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
            'approver_role' => 'dept_admin',
            'remark' => $remark,
            'created_at' => now(),
        ]);

        $this->auditLog->record(
            'grade_report.review',
            subjectType: 'grade_report',
            subjectId: $report->grade_id,
            metadata: [
                'action' => $action,
                'from_status' => $from,
                'to_status' => $to,
                'remark' => $remark,
                'subject_code' => $report->subject_code,
            ],
            actorUsername: $approverUsername,
            actorRole: 'dept_admin',
        );
    }
}
