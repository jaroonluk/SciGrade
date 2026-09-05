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
            throw new InvalidArgumentException('ส่งกลับได้เฉพาะรายการที่รอสาขาหรือรับแล้ว');
        }

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
        ]);
    }
}
