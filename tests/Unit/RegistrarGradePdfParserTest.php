<?php

namespace Tests\Unit;

use App\Services\RegistrarGradePdfParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrarGradePdfParserTest extends TestCase
{
    #[Test]
    public function it_parses_sample_registrar_pdf(): void
    {
        $path = base_path('project_old/SC101011-01.pdf');
        if (! is_file($path)) {
            $this->markTestSkipped('Sample PDF not available.');
        }

        $parsed = (new RegistrarGradePdfParser)->parse($path, 'SC101011-01.pdf', 1, 2566);

        $this->assertSame('SC101011', $parsed['subject_code']);
        $this->assertSame('BIOLOGY FOR AGRICULTURE I', $parsed['subject']);
        $this->assertSame(1, $parsed['term']);
        $this->assertSame(2566, $parsed['year']);
        $this->assertSame(3, $parsed['degree']);
        $this->assertStringContainsString('ศุจีภรณ์', $parsed['teacher']);
        $this->assertCount(1, $parsed['grade_stds']);
        $this->assertSame(1, $parsed['grade_stds'][0]['sec']);
        $this->assertSame(2, $parsed['grade_stds'][0]['num_a']);
        $this->assertSame(290, $parsed['grade_stds'][0]['num_dd']);
        $this->assertSame(575, $parsed['grade_stds'][0]['num_a']
            + $parsed['grade_stds'][0]['num_bb']
            + $parsed['grade_stds'][0]['num_b']
            + $parsed['grade_stds'][0]['num_cc']
            + $parsed['grade_stds'][0]['num_c']
            + $parsed['grade_stds'][0]['num_dd']
            + $parsed['grade_stds'][0]['num_d']
            + $parsed['grade_stds'][0]['num_f']
            + $parsed['grade_stds'][0]['num_w']);
        $this->assertSame('100-80', $parsed['score_a']);
        $this->assertSame('43-35', $parsed['score_dd']);
        $this->assertStringContainsString('AG', $parsed['grade_stds'][0]['fac']);
    }

    #[Test]
    public function it_rejects_invalid_pdf(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($tmp, 'not a registrar pdf');

        $this->expectException(\App\Services\RegistrarPdfParseException::class);

        try {
            (new RegistrarGradePdfParser)->parse($tmp, 'SC101011-01.pdf', 1, 2566);
        } finally {
            @unlink($tmp);
        }
    }
}
