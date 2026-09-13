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
    public function department_can_receive_without_chair_files(): void
    {
        $report = new class(['status' => ThesisGrade::STATUS_SUBMITTED]) extends ThesisGrade
        {
            public array $lastUpdate = [];

            public function update(array $attributes = [], array $options = []): bool
            {
                $this->lastUpdate = $attributes;
                $this->fill($attributes);

                return true;
            }
        };

        (new ThesisGradeApprovalService)->receive($report, 'deptadmin');

        $this->assertSame(ThesisGrade::STATUS_RECEIVED, $report->status);
        $this->assertSame('deptadmin', $report->lastUpdate['received_by']);
    }

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
