<?php

namespace Tests\Unit;

use App\Services\AuditLogService;
use App\Services\DeptAdmin\DepartmentSubjectFilter;
use App\Services\DeptAdmin\DeptRegistrarBulkUploadService;
use App\Services\GradeReportAttachmentNameService;
use App\Services\RegistrarGradePdfParser;
use App\Services\RegistrarPdfParseException;
use Illuminate\Http\UploadedFile;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeptRegistrarBulkUploadServiceTest extends TestCase
{
    #[Test]
    public function it_still_parses_legacy_filename_helper(): void
    {
        $parsed = $this->service()->parseFilename('SC101011-01.pdf');

        $this->assertSame('SC101011', $parsed['course_code']);
        $this->assertSame('01', $parsed['section']);
        $this->assertSame(1, $parsed['section_int']);
    }

    #[Test]
    public function it_rejects_filenames_that_do_not_match_the_legacy_convention(): void
    {
        $service = $this->service();

        $this->assertNull($service->parseFilename('SC101011.pdf'));
        $this->assertNull($service->parseFilename('SC101011-1.pdf'));
        $this->assertNull($service->parseFilename('report.docx'));
        $this->assertNull($service->parseFilename('SC101011-01 (1).pdf'));
    }

    #[Test]
    public function preview_reports_term_year_mismatch_from_pdf_content(): void
    {
        $parser = Mockery::mock(RegistrarGradePdfParser::class);
        $parser->shouldReceive('parse')
            ->once()
            ->andReturn([
                'subject_code' => 'SC101011',
                'subject' => 'GENERAL BIOLOGY',
                'term' => 2,
                'year' => 2567,
                'grade_stds' => [['sec' => 1]],
            ]);

        $filter = Mockery::mock(DepartmentSubjectFilter::class);
        $filter->shouldNotReceive('applyDepartmentsToQuery');

        $service = new DeptRegistrarBulkUploadService(
            $filter,
            $this->createMock(GradeReportAttachmentNameService::class),
            $this->createMock(AuditLogService::class),
            $parser,
        );

        $file = UploadedFile::fake()->createWithContent('anything.pdf', '%PDF-fake');
        $rows = $service->preview([$file], 1, 2567, [25]);

        $this->assertCount(1, $rows);
        $this->assertFalse($rows[0]['ok']);
        $this->assertSame('term_year_mismatch', $rows[0]['error_code']);
        $this->assertSame('SC101011', $rows[0]['course_code']);
        $this->assertSame('01', $rows[0]['section']);
        $this->assertStringContainsString('ภาค/ปีการศึกษาในไฟล์ไม่ตรง', $rows[0]['reason']);
        $this->assertStringContainsString('ภาคต้น ปีการศึกษา 2567', $rows[0]['reason']);
        $this->assertStringContainsString('ภาคปลาย ปีการศึกษา 2567', $rows[0]['reason']);
    }

    #[Test]
    public function preview_reports_parse_failure_with_file_name(): void
    {
        $parser = Mockery::mock(RegistrarGradePdfParser::class);
        $parser->shouldReceive('parse')
            ->once()
            ->andThrow(new RegistrarPdfParseException('ไฟล์ PDF ไม่ตรงรูปแบบใบส่งผลการศึกษา'));

        $service = new DeptRegistrarBulkUploadService(
            Mockery::mock(DepartmentSubjectFilter::class),
            $this->createMock(GradeReportAttachmentNameService::class),
            $this->createMock(AuditLogService::class),
            $parser,
        );

        $file = UploadedFile::fake()->createWithContent('my-scan.pdf', '%PDF-bad');
        $rows = $service->preview([$file], 1, 2567, [25]);

        $this->assertFalse($rows[0]['ok']);
        $this->assertSame('parse_failed', $rows[0]['error_code']);
        $this->assertStringContainsString('my-scan.pdf', $rows[0]['reason']);
    }

    private function service(): DeptRegistrarBulkUploadService
    {
        return new DeptRegistrarBulkUploadService(
            $this->createMock(DepartmentSubjectFilter::class),
            $this->createMock(GradeReportAttachmentNameService::class),
            $this->createMock(AuditLogService::class),
            $this->createMock(RegistrarGradePdfParser::class),
        );
    }
}
