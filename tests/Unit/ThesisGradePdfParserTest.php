<?php

namespace Tests\Unit;

use App\Services\ThesisGrade\ThesisGradePdfParser;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class ThesisGradePdfParserTest extends TestCase
{
    #[Test]
    public function it_reads_credits_from_native_ts_pdf_and_treats_empty_note_placeholder_as_null(): void
    {
        $path = base_path('project_old/reg/TS-SC557899-01-2-2568.pdf');
        if (! is_file($path)) {
            $this->markTestSkipped('TS-SC557899 sample PDF not available.');
        }

        $rows = $this->extractTableRows($path);
        $byCode = [];
        foreach ($rows as $row) {
            $byCode[$row['student_code']] = $row;
        }

        $this->assertArrayHasKey('665020053-3', $byCode);
        $this->assertSame(2.0, $byCode['665020053-3']['credits_registered']);
        $this->assertSame(2.0, $byCode['665020053-3']['credits_passed']);
        $this->assertSame('S', $byCode['665020053-3']['grade']);
        $this->assertNull($byCode['665020053-3']['note']);

        $this->assertSame(7.0, $byCode['675020045-3']['credits_registered']);
        $this->assertSame(5.0, $byCode['675020045-3']['credits_passed']);
        $this->assertNull($byCode['675020045-3']['note']);
        $this->assertCount(7, $rows);
    }

    #[Test]
    public function it_reads_offset_and_wrapped_remark_column_from_printed_ts_table(): void
    {
        $path = base_path('project_old/reg/3.1 ผลการเรียนวิทยานิพนธ์.pdf');
        if (! is_file($path)) {
            $this->markTestSkipped('Combined thesis sample PDF not available.');
        }

        $rows = $this->extractTableRows($path);
        $byCode = [];
        foreach ($rows as $row) {
            $byCode[$row['student_code']] = $row;
        }

        $this->assertArrayHasKey('665020053-3', $byCode);
        $this->assertNotNull($byCode['665020053-3']['note']);
        $this->assertStringContainsString('รศ.ดร.ธนายุทธ', (string) $byCode['665020053-3']['note']);
        $this->assertStringContainsString('สอบ', (string) $byCode['665020053-3']['note']);
        $this->assertStringContainsString('14', (string) $byCode['665020053-3']['note']);
        $this->assertStringContainsString('พ.ค.', (string) $byCode['665020053-3']['note']);

        $this->assertStringContainsString('ชาคริต', (string) $byCode['665020055-9']['note']);
        $this->assertStringContainsString('สอบ', (string) $byCode['665020055-9']['note']);

        $this->assertStringContainsString('ดริศ', (string) $byCode['675020008-9']['note']);
        $this->assertStringContainsString('พรจักร', (string) $byCode['675020034-8']['note']);
        $this->assertStringContainsString('30', (string) $byCode['675020034-8']['note']);

        $this->assertSame('Dr.David Nugroho', $byCode['687020026-2']['note']);
    }

    #[Test]
    public function it_detects_image_only_photoshop_ts_and_prefills_from_filename(): void
    {
        $path = base_path('project_old/file_test/2.1-SC157899-01-2-2568.pdf');
        if (! is_file($path)) {
            $this->markTestSkipped('Image TS sample PDF not available.');
        }

        $service = new ThesisGradePdfParser(new Parser);
        $parsed = $service->parse($path, basename($path), 1, 2567);

        $this->assertSame('SC157899', $parsed['subject_code']);
        $this->assertSame('01', $parsed['section']);
        $this->assertSame(2, $parsed['term']);
        $this->assertSame(2568, $parsed['year']);
        $this->assertSame([], $parsed['students']);
        $this->assertNull($parsed['teacher']);
        $this->assertTrue(collect($parsed['warnings'])->contains(
            fn ($w) => str_contains((string) $w, 'ไฟล์นี้เป็น PDF แบบภาพ')
                && str_contains((string) $w, 'มข.11')
                && str_contains((string) $w, 'REG')
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractTableRows(string $path): array
    {
        $parser = new ThesisGradePdfParser(new Parser);
        $method = new ReflectionMethod(ThesisGradePdfParser::class, 'extractStudentTableRows');
        $document = (new Parser)->parseFile($path);

        /** @var list<array<string, mixed>> $rows */
        $rows = $method->invoke($parser, $document);

        return $rows;
    }
}
