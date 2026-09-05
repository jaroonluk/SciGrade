<?php

namespace App\Services\ThesisGrade;

use App\Models\ThesisGrade;
use InvalidArgumentException;

class ThesisGradeApprovalService
{
    public function receive(ThesisGrade $report, string $actor): void
    {
        if ($report->status !== ThesisGrade::STATUS_SUBMITTED) {
            throw new InvalidArgumentException('รับเรื่องได้เฉพาะรายการที่รอสาขา');
        }

        $report->update([
            'status' => ThesisGrade::STATUS_RECEIVED,
            'received_at' => now(),
            'received_by' => $actor,
            'return_reason' => null,
        ]);
    }

    public function sendBack(ThesisGrade $report, string $actor, string $reason): void
    {
        if (! in_array($report->status, [ThesisGrade::STATUS_SUBMITTED, ThesisGrade::STATUS_RECEIVED], true)) {
            throw new InvalidArgumentException('ส่งกลับได้เฉพาะรายการที่รอสาขาหรือสาขารับแล้วและคณะยังไม่รับเรื่อง');
        }

        $this->markReturned($report, $actor, $reason);
    }

    public function facultyReceive(ThesisGrade $report, string $actor): void
    {
        if ($report->status !== ThesisGrade::STATUS_RECEIVED) {
            throw new InvalidArgumentException('คณะรับเรื่องได้เฉพาะรายการที่สาขารับแล้ว');
        }

        $report->update([
            'status' => ThesisGrade::STATUS_APPROVED,
            'faculty_received_at' => now(),
            'faculty_received_by' => $actor,
            'return_reason' => null,
        ]);
    }

    public function facultySendBack(ThesisGrade $report, string $actor, string $reason): void
    {
        if (! in_array($report->status, [ThesisGrade::STATUS_RECEIVED, ThesisGrade::STATUS_APPROVED], true)) {
            throw new InvalidArgumentException('คณะส่งกลับได้เฉพาะรายการที่สาขารับแล้วหรือคณะรับแล้ว');
        }

        $this->markReturned($report, $actor, $reason);
    }

    private function markReturned(ThesisGrade $report, string $actor, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('กรุณาระบุเหตุผลที่ส่งกลับ');
        }

        $report->update([
            'status' => ThesisGrade::STATUS_RETURNED,
            'return_reason' => $reason,
            'returned_at' => now(),
            'returned_by' => $actor,
            'received_at' => null,
            'received_by' => null,
            'faculty_received_at' => null,
            'faculty_received_by' => null,
        ]);
    }
}
