<?php

namespace Tests\Unit;

use App\Models\ThesisGrade;
use App\Services\ThesisGrade\ThesisGradeApprovalService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThesisGradeApprovalServiceTest extends TestCase
{
    #[Test]
    public function faculty_cannot_receive_before_department(): void
    {
        $report = new ThesisGrade(['status' => ThesisGrade::STATUS_SUBMITTED]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('คณะรับเรื่องได้เฉพาะรายการที่สาขารับแล้ว');

        (new ThesisGradeApprovalService)->facultyReceive($report, 'staff');
    }

    #[Test]
    public function department_cannot_send_back_after_faculty_received(): void
    {
        $report = new ThesisGrade(['status' => ThesisGrade::STATUS_APPROVED]);

        $this->expectException(InvalidArgumentException::class);

        (new ThesisGradeApprovalService)->sendBack($report, 'dept', 'แก้ไฟล์');
    }
}
