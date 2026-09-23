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
                throw new InvalidArgumentException('รายการผ่านที่ประชุมสาขาแล้ว');
            }

            if ($from !== GradeApprovalStatus::DepartmentMeetingQueued->value) {
                throw new InvalidArgumentException('กรุณานำเข้าที่ประชุมสาขาก่อน แล้วจึงกดผ่านที่ประชุมสาขา');
            }

            $report->update([
                'approv' => GradeApprovalStatus::DepartmentApproved->value,
                'dateapprove1' => now()->toDateString(),
            ]);

            $this->writeLog($report, 'department_approved', $from, GradeApprovalStatus::DepartmentApproved->value, $approverUsername, $remark);

            return $report->fresh(['gradeStds', 'files', 'latestDeptApprovalLog.approver']);
        });
    }

    /**
     * นำเข้ารายวิชาเข้าที่ประชุมสาขา (สถานะกลางก่อนผ่านมติ)
     */
    public function queueForMeeting(GradeReport $report, string $approverUsername, ?string $remark = null): GradeReport
    {
        return DB::connection('scigrad')->transaction(function () use ($report, $approverUsername, $remark) {
            $report = GradeReport::query()->lockForUpdate()->findOrFail($report->grade_id);
            $from = (int) $report->approv;

            if ($from === GradeApprovalStatus::CentralApproved->value) {
                throw new InvalidArgumentException('รายการผ่านการอนุมัติคณะแล้ว ไม่สามารถเปลี่ยนสถานะจากสาขาได้');
            }

            if (in_array($from, GradeApprovalStatus::facultyReviewableValues(), true)) {
                throw new InvalidArgumentException('รายการผ่านที่ประชุมสาขาแล้ว');
            }

            if ($from === GradeApprovalStatus::DepartmentMeetingQueued->value) {
                throw new InvalidArgumentException('รายการนี้อยู่ในสถานะนำเข้าที่ประชุมสาขาแล้ว');
            }

            if ($from !== GradeApprovalStatus::Saved->value) {
                throw new InvalidArgumentException('รายการนี้ไม่อยู่ในสถานะที่สามารถนำเข้าที่ประชุมสาขาได้');
            }

            $report->update([
                'approv' => GradeApprovalStatus::DepartmentMeetingQueued->value,
            ]);

            $this->writeLog(
                $report,
                'department_meeting_queued',
                $from,
                GradeApprovalStatus::DepartmentMeetingQueued->value,
                $approverUsername,
                $remark,
            );

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
                throw new InvalidArgumentException('รายการผ่านที่ประชุมสาขาแล้ว ไม่สามารถเปลี่ยนเป็นไม่ผ่านได้');
            }

            if (! in_array($from, GradeApprovalStatus::departmentPreMeetingValues(), true)) {
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
                throw new InvalidArgumentException('รายการผ่านที่ประชุมสาขาแล้ว ไม่สามารถส่งกลับให้แก้ไขได้');
            }

            if (! in_array($from, GradeApprovalStatus::departmentPreMeetingValues(), true)) {
                throw new InvalidArgumentException('สามารถส่งกลับให้อาจารย์แก้ไขได้เฉพาะรายการที่ยังไม่ผ่านที่ประชุมสาขา');
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

            if (! in_array($from, [
                GradeApprovalStatus::DepartmentApproved->value,
                GradeApprovalStatus::DepartmentMeetingQueued->value,
            ], true)) {
                throw new InvalidArgumentException('สามารถเปลี่ยนกลับเป็น “บันทึกแล้ว” ได้เฉพาะรายการที่นำเข้าหรือผ่านที่ประชุมสาขาแล้วเท่านั้น');
            }

            $report->update([
                'approv' => GradeApprovalStatus::Saved->value,
                'dateapprove1' => null,
            ]);

            $this->writeLog($report, 'department_reset', $from, GradeApprovalStatus::Saved->value, $approverUsername, $remark);

            return $report->fresh(['gradeStds', 'files', 'latestDeptApprovalLog.approver']);
        });
    }

    /**
     * ตั้งสถานะแสดงผลฝั่งสาขา: 1=ส่งแล้ว, 2=นำเข้าที่ประชุมสาขา, 3=ผ่านที่ประชุมสาขา
     * (ข้ามถ้าอยู่สถานะนั้นแล้ว — ใช้ตอนอัปเดตทุก Section ของวิชา)
     */
    public function setDisplayStatus(GradeReport $report, int $displayStatus, string $approverUsername, ?string $remark = null): GradeReport
    {
        if (! in_array($displayStatus, [1, 2, 3], true)) {
            throw new InvalidArgumentException('สถานะที่สาขาตั้งได้มีเพียง ส่งแล้ว / นำเข้าที่ประชุมสาขา / ผ่านที่ประชุมสาขา');
        }

        $report = GradeReport::query()->findOrFail($report->grade_id);
        $from = (int) $report->approv;

        if (in_array($from, [
            GradeApprovalStatus::CentralApproved->value,
            GradeApprovalStatus::FacultyChecked->value,
        ], true)) {
            throw new InvalidArgumentException('รายการผ่านคณะฯ แล้ว ไม่สามารถเปลี่ยนสถานะจากสาขาได้');
        }

        $currentDisplay = match ($from) {
            GradeApprovalStatus::DepartmentMeetingQueued->value => 2,
            GradeApprovalStatus::DepartmentApproved->value => 3,
            default => 1,
        };

        if ($currentDisplay === $displayStatus) {
            return $report->fresh(['gradeStds', 'files', 'latestDeptApprovalLog.approver']) ?? $report;
        }

        if ($displayStatus === 1) {
            return $this->resetToSaved($report, $approverUsername, $remark);
        }

        if ($displayStatus === 2) {
            if ($from === GradeApprovalStatus::DepartmentApproved->value) {
                $report = $this->resetToSaved($report, $approverUsername, $remark);
            }

            return $this->queueForMeeting($report, $approverUsername, $remark);
        }

        // displayStatus === 3
        if ($from === GradeApprovalStatus::Saved->value) {
            $report = $this->queueForMeeting($report, $approverUsername, $remark);
        } elseif ($from !== GradeApprovalStatus::DepartmentMeetingQueued->value) {
            throw new InvalidArgumentException('รายการนี้ไม่อยู่ในสถานะที่สามารถผ่านที่ประชุมสาขาได้');
        }

        return $this->approve($report, $approverUsername, $remark);
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
