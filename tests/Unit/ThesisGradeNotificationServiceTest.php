<?php

namespace Tests\Unit;

use App\Models\ThesisGrade;
use App\Services\DeptAdmin\DepartmentAccessService;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use App\Services\KkuEmailService;
use App\Services\ThesisGrade\ThesisGradeNotificationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThesisGradeNotificationServiceTest extends TestCase
{
    #[Test]
    public function override_sends_only_to_test_inbox_and_notes_intended_admins(): void
    {
        config(['scigrade.dept_admin_mail_override' => 'jaroonluk@kku.ac.th']);

        $mail = $this->createMock(KkuEmailService::class);
        $mail->expects($this->once())
            ->method('send')
            ->with(
                ['jaroonluk@kku.ac.th'],
                $this->stringContains('SC899001'),
                $this->callback(function (string $html): bool {
                    return str_contains($html, 'ช่วงทดสอบก่อนเปิดระบบจริง')
                        && str_contains($html, 'jaroonluk@kku.ac.th')
                        && str_contains($html, 'dept-admin@kku.ac.th');
                }),
            );

        $this->notificationService($mail, ['dept-admin@kku.ac.th'])
            ->notifyDeptAdminsOfSubmit($this->report());
    }

    #[Test]
    public function empty_override_sends_to_real_dept_admins(): void
    {
        config(['scigrade.dept_admin_mail_override' => '']);

        $mail = $this->createMock(KkuEmailService::class);
        $mail->expects($this->once())
            ->method('send')
            ->with(
                ['dept-admin@kku.ac.th'],
                $this->stringContains('SC899001'),
                $this->callback(function (string $html): bool {
                    return ! str_contains($html, 'ช่วงทดสอบก่อนเปิดระบบจริง')
                        && str_contains($html, 'อ. ทดสอบ');
                }),
            );

        $this->notificationService($mail, ['dept-admin@kku.ac.th'])
            ->notifyDeptAdminsOfSubmit($this->report());
    }

    #[Test]
    public function empty_override_and_no_admins_does_not_send(): void
    {
        config(['scigrade.dept_admin_mail_override' => '']);

        $mail = $this->createMock(KkuEmailService::class);
        $mail->expects($this->never())->method('send');

        $this->notificationService($mail, [])
            ->notifyDeptAdminsOfSubmit($this->report());
    }

    #[Test]
    public function override_still_sends_when_no_dept_admin_is_found(): void
    {
        config(['scigrade.dept_admin_mail_override' => 'jaroonluk@kku.ac.th']);

        $mail = $this->createMock(KkuEmailService::class);
        $mail->expects($this->once())
            ->method('send')
            ->with(
                ['jaroonluk@kku.ac.th'],
                $this->anything(),
                $this->stringContains('ยังไม่พบอีเมล Admin สาขาของวิชานี้'),
            );

        $this->notificationService($mail, [])
            ->notifyDeptAdminsOfSubmit($this->report());
    }

    /**
     * @param  list<string>  $intended
     */
    private function notificationService(KkuEmailService $mail, array $intended): ThesisGradeNotificationService
    {
        $service = $this->getMockBuilder(ThesisGradeNotificationService::class)
            ->setConstructorArgs([
                $mail,
                $this->createMock(DepartmentSubjectFilter::class),
                $this->createMock(DepartmentAccessService::class),
            ])
            ->onlyMethods(['deptAdminEmailsForSubject'])
            ->getMock();

        $service->method('deptAdminEmailsForSubject')->willReturn($intended);

        return $service;
    }

    private function report(): ThesisGrade
    {
        $report = new ThesisGrade([
            'subject_code' => 'SC899001',
            'subject' => 'THESIS',
            'section' => '1',
            'term' => 2,
            'year' => 2568,
            'teacher' => 'อ. ทดสอบ',
            'username' => 'teacher',
        ]);
        $report->thesis_grade_id = 25;

        return $report;
    }
}
