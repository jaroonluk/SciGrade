<?php

namespace Tests\Unit;

use App\Services\AuditLogService;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use App\Services\DeptAdmin\DeptRegistrarBulkUploadService;
use App\Services\GradeReportAttachmentNameService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeptRegistrarBulkUploadServiceTest extends TestCase
{
    #[Test]
    public function it_parses_course_code_and_section_from_filename(): void
    {
        $parsed = $this->service()->parseFilename('SC101011-01.pdf');

        $this->assertSame('SC101011', $parsed['course_code']);
        $this->assertSame('01', $parsed['section']);
        $this->assertSame(1, $parsed['section_int']);
    }

    #[Test]
    public function it_parses_uppercase_extension_and_windows_path(): void
    {
        $parsed = $this->service()->parseFilename('C:\\uploads\\sc315201-12.PDF');

        $this->assertSame('SC315201', $parsed['course_code']);
        $this->assertSame('12', $parsed['section']);
        $this->assertSame(12, $parsed['section_int']);
    }

    #[Test]
    public function it_rejects_filenames_that_do_not_match_the_convention(): void
    {
        $service = $this->service();

        $this->assertNull($service->parseFilename('SC101011.pdf'));
        $this->assertNull($service->parseFilename('SC101011-1.pdf'));
        $this->assertNull($service->parseFilename('report.docx'));
        $this->assertNull($service->parseFilename('SC101011-01 (1).pdf'));
    }

    private function service(): DeptRegistrarBulkUploadService
    {
        return new DeptRegistrarBulkUploadService(
            $this->createMock(DepartmentSubjectFilter::class),
            $this->createMock(GradeReportAttachmentNameService::class),
            $this->createMock(AuditLogService::class),
        );
    }
}
