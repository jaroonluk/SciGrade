<?php

namespace App\Models;

use App\Enums\GradeApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class GradeReport extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'grade_report';

    protected $primaryKey = 'grade_id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'created',
        'term',
        'year',
        'subject_code',
        'subject_code2',
        'subject',
        'username',
        'score_a',
        'score_bb',
        'score_b',
        'score_cc',
        'score_c',
        'score_dd',
        'score_d',
        'score_f',
        'mean',
        'sd',
        'reasonid',
        'reason',
        'teacher',
        'approv',
        'dateapprove1',
        'dateapprove2',
        'type_course',
        'programid',
        'degree',
        'selecttype',
        'totalnumstdevz',
        'totalevaluationscore',
        'statuseva',
        'intflag',
    ];

    protected function casts(): array
    {
        return [
            'created' => 'date',
            'approv' => 'integer',
            'reasonid' => 'integer',
            'degree' => 'integer',
            'selecttype' => 'integer',
            'statuseva' => 'integer',
            'intflag' => 'integer',
            'totalnumstdevz' => 'integer',
            'totalevaluationscore' => 'float',
        ];
    }

    public function gradeStds(): HasMany
    {
        return $this->hasMany(GradeStd::class, 'grade_id', 'grade_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(GradeReportFile::class, 'grade_id', 'grade_id')
            ->orderByDesc('uploaded_at')
            ->orderByDesc('file_id');
    }

    public function approvalLogs(): HasMany
    {
        return $this->hasMany(GradeReportApprovalLog::class, 'grade_id', 'grade_id')
            ->orderByDesc('created_at')
            ->orderByDesc('log_id');
    }

    public function latestDeptApprovalLog(): HasOne
    {
        return $this->hasOne(GradeReportApprovalLog::class, 'grade_id', 'grade_id')
            ->whereIn('action', ['department_approved', 'department_rejected', 'department_reset', 'department_send_back'])
            ->latestOfMany('log_id');
    }

    public function latestCentralApprovalLog(): HasOne
    {
        return $this->hasOne(GradeReportApprovalLog::class, 'grade_id', 'grade_id')
            ->whereIn('action', ['central_approved', 'central_rejected', 'central_send_back', 'central_checked'])
            ->latestOfMany('log_id');
    }

    public function statusLabel(): string
    {
        return match ((int) $this->approv) {
            1 => 'ผ่านที่ประชุมกรรมการสาขาวิชา',
            3 => 'ตรวจแล้ว — รอกรรมการคณะฯ',
            2 => 'ผ่านที่ประชุมกรรมการคณะ',
            -1 => 'ส่งกลับแก้ไข',
            default => 'รอดำเนินการ / ยังไม่อนุมัติ',
        };
    }

    public function statusShortLabel(): string
    {
        return $this->workflowStatusLabel();
    }

    public function workflowStatusLabel(): string
    {
        if ((int) $this->approv === 0 && $this->awaitingDeptResubmit()) {
            return 'ส่งการแก้ไขแล้ว — รอสาขา';
        }

        return match ((int) $this->approv) {
            1 => 'สาขาอนุมัติ',
            3 => 'ตรวจแล้ว',
            2 => 'คณะอนุมัติ',
            -1 => 'ส่งกลับแก้ไข',
            default => 'บันทึกแล้ว',
        };
    }

    public function instructorTrackStatusLabel(): string
    {
        $approv = (int) $this->approv;

        if ($approv === -1) {
            return 'ส่งกลับให้แก้ไข';
        }

        if ($approv === 0 && $this->awaitingDeptResubmit()) {
            return 'ส่งการแก้ไขแล้ว — รอสาขา';
        }

        return match ($approv) {
            1, 3 => 'สาขาอนุมัติแล้ว — รอคณะ',
            2 => 'คณะอนุมัติแล้ว — เสร็จสิ้น',
            default => 'ส่งแล้ว — รอสาขาตรวจสอบ',
        };
    }

    public function instructorTrackStatusClass(): string
    {
        return match ((int) $this->approv) {
            1, 3 => 'status-dept',
            2 => 'status-approved',
            -1 => 'status-rejected',
            default => 'status-pending',
        };
    }

    public function approvalResultLabel(): string
    {
        return match ((int) $this->approv) {
            1 => 'ผ่านการรับรองผลสอบ',
            3 => 'ตรวจแล้ว — รอกรรมการคณะฯ',
            2 => 'ผ่านการรับรองผลสอบ (คณะ)',
            -1 => 'ยังไม่ผ่านการรับรองผลสอบ',
            default => 'ยังไม่ผ่านการรับรองผลสอบ',
        };
    }

    public function approvalStep(): int
    {
        return match ((int) $this->approv) {
            1, 3 => 1,
            2 => 2,
            default => 0,
        };
    }

    public function canEdit(): bool
    {
        return in_array((int) $this->approv, [0, -1], true) && ! $this->awaitingDeptResubmit();
    }

    public function canSubmitCorrections(): bool
    {
        return (int) $this->approv === -1;
    }

    public function awaitingDeptResubmit(): bool
    {
        if ((int) $this->approv !== 0) {
            return false;
        }

        $logs = $this->relationLoaded('approvalLogs')
            ? $this->approvalLogs
            : $this->approvalLogs()->orderByDesc('log_id')->get();

        $latest = $logs->first();

        return $latest !== null && $latest->action === 'instructor_resubmitted';
    }

    public function canUploadFiles(): bool
    {
        return $this->canEdit();
    }

    public function canDeptRevertToSaved(): bool
    {
        return (int) $this->approv === GradeApprovalStatus::DepartmentApproved->value;
    }

    public function canDeptAttachRegistrar(): bool
    {
        return in_array((int) $this->approv, [
            GradeApprovalStatus::Saved->value,
            GradeApprovalStatus::DepartmentApproved->value,
        ], true);
    }

    /** Admin สาขาลบ REG-Admin ได้ก่อนที่ Admin กลางจะเปลี่ยนสถานะ */
    public function canDeptDeleteRegistrar(): bool
    {
        return $this->canDeptAttachRegistrar();
    }

    public function canFacultyApprove(): bool
    {
        return in_array((int) $this->approv, GradeApprovalStatus::facultyReviewableValues(), true);
    }

    public function canMarkFacultyChecked(): bool
    {
        return (int) $this->approv === GradeApprovalStatus::DepartmentApproved->value;
    }

    public function canPrint(): bool
    {
        return $this->gradeStds->isNotEmpty();
    }

    public function totalStudents(): int
    {
        return (int) $this->gradeStds->sum(fn ($row) => (int) $row->total_std);
    }

    /**
     * จำนวนนักศึกษาที่รายงานผลตามกลุ่มเรียน (ถ้าไม่ระบุกลุ่ม คืนผลรวมทั้งรายวิชา)
     */
    public function sectionStudentCount(?int $section = null): int
    {
        if (! $this->relationLoaded('gradeStds')) {
            $this->load('gradeStds');
        }

        if ($section === null) {
            return $this->totalStudents();
        }

        foreach ($this->gradeStds as $row) {
            $sec = trim((string) ($row->sec ?? ''));
            if ($sec === '' || $sec === '-') {
                continue;
            }

            if ((int) preg_replace('/\D/', '', $sec) === $section) {
                return (int) ($row->total_std ?? 0);
            }
        }

        return 0;
    }

    /**
     * กลุ่มเรียน (Section) พร้อมจำนวนผู้ลงทะเบียนในรายงาน
     *
     * @return Collection<int, array{sec: string, total: int}>
     */
    public function enrollmentSections(): Collection
    {
        return $this->gradeStds
            ->map(function ($row) {
                $sec = trim((string) ($row->sec ?? ''));

                return [
                    'sec' => $sec === '' ? '-' : $sec,
                    'total' => (int) ($row->total_std ?? 0),
                ];
            })
            ->sortBy(fn (array $item) => sprintf('%08s', $item['sec']), SORT_NATURAL)
            ->values();
    }

    /**
     * ชื่อไฟล์ตอนดาวน์โหลด REG ของ Admin สาขา:
     * รหัสวิชา-กลุ่ม-จำนวนนักศึกษาของกลุ่มนั้น.pdf
     *
     * @param  int|null  $section  กลุ่มเรียนเฉพาะไฟล์ (เช่น 1 → 01) — ถ้าไม่ระบุและมีหลายกลุ่มจะรวมเป็น 01_02
     */
    public function deptRegistrarDownloadName(?int $section = null): string
    {
        if (! $this->relationLoaded('gradeStds')) {
            $this->load('gradeStds');
        }

        $code = preg_replace('/[^\w.\-]+/u', '_', trim((string) $this->subject_code)) ?: 'SUBJECT';

        if ($section !== null) {
            $group = str_pad((string) $section, 2, '0', STR_PAD_LEFT);
        } else {
            $sections = $this->enrollmentSections()
                ->pluck('sec')
                ->filter(fn ($sec) => $sec !== '' && $sec !== '-')
                ->map(function ($sec) {
                    $digits = preg_replace('/\D/', '', (string) $sec);

                    return $digits !== ''
                        ? str_pad((string) (int) $digits, 2, '0', STR_PAD_LEFT)
                        : preg_replace('/[^\w.\-]+/u', '_', (string) $sec);
                })
                ->unique()
                ->values();

            $group = $sections->isNotEmpty() ? $sections->implode('_') : '00';
        }

        $total = $this->sectionStudentCount($section);

        return sprintf('%s-%s-%d.pdf', $code, $group, $total);
    }

    public function termLabel(): string
    {
        return match ((int) $this->term) {
            1 => 'ภาคต้น',
            2 => 'ภาคปลาย',
            default => 'ภาคการศึกษาพิเศษ',
        };
    }

    /**
     * @return Collection<int, array{
     *     role_label: string,
     *     action_label: string,
     *     text: string,
     *     at: \Illuminate\Support\Carbon|null,
     *     approver: string|null,
     *     tone: string,
     * }>
     */
    public function instructorAdminComments(): Collection
    {
        $logs = $this->relationLoaded('approvalLogs')
            ? $this->approvalLogs
            : $this->approvalLogs()->with('approver')->get();

        $comments = $logs
            ->filter(fn (GradeReportApprovalLog $log) => trim((string) $log->remark) !== '')
            ->map(fn (GradeReportApprovalLog $log) => [
                'role_label' => $this->approverRoleLabel($log->approver_role),
                'action_label' => $this->approvalLogActionLabel($log->action),
                'text' => trim((string) $log->remark),
                'at' => $log->created_at,
                'approver' => $log->approver?->displayName(),
                'tone' => $this->approvalLogTone($log->action),
            ]);

        if ((int) $this->approv === -1) {
            $reason = trim((string) $this->reason);
            if ($reason !== '' && ! $comments->contains(fn (array $comment) => $comment['text'] === $reason)) {
                $latestFeedback = $logs->first(fn (GradeReportApprovalLog $log) => in_array($log->action, [
                    'department_rejected',
                    'department_send_back',
                    'central_rejected',
                    'central_send_back',
                ], true));

                $comments->prepend([
                    'role_label' => $latestFeedback
                        ? $this->approverRoleLabel($latestFeedback->approver_role)
                        : 'เจ้าหน้าที่',
                    'action_label' => $latestFeedback
                        ? $this->approvalLogActionLabel($latestFeedback->action)
                        : 'ส่งกลับแก้ไข',
                    'text' => $reason,
                    'at' => $latestFeedback?->created_at,
                    'approver' => $latestFeedback?->approver?->displayName(),
                    'tone' => 'warning',
                ]);
            }
        }

        return $comments
            ->sortByDesc(fn (array $comment) => $comment['at']?->timestamp ?? 0)
            ->values();
    }

    private function approverRoleLabel(?string $role): string
    {
        return match ($role) {
            'faculty_admin' => 'Admin กลาง',
            'dept_admin' => 'Admin สาขา',
            default => 'เจ้าหน้าที่',
        };
    }

    private function approvalLogActionLabel(string $action): string
    {
        return match ($action) {
            'department_approved', 'central_approved' => 'อนุมัติ',
            'central_checked' => 'ตรวจแล้ว',
            'department_rejected', 'central_rejected' => 'ไม่อนุมัติ',
            'department_send_back', 'central_send_back' => 'ส่งกลับแก้ไข',
            'instructor_resubmitted' => 'ส่งการแก้ไข',
            default => 'หมายเหตุ',
        };
    }

    private function approvalLogTone(string $action): string
    {
        return match ($action) {
            'department_rejected', 'central_rejected', 'department_send_back', 'central_send_back' => 'warning',
            default => 'info',
        };
    }
}
