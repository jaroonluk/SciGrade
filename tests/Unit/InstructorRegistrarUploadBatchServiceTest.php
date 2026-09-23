<?php

namespace Tests\Unit;

use App\Services\Instructor\InstructorPendingRegistrarService;
use App\Services\Instructor\InstructorRegistrarUploadBatchService;
use App\Services\RegistrarGradePdfParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstructorRegistrarUploadBatchServiceTest extends TestCase
{
    private function makeParsed(string $subject, int $section, array $extra = []): array
    {
        return array_merge([
            'subject_code' => $subject,
            'subject' => 'Test Subject',
            'term' => 2,
            'year' => 2568,
            'degree' => 1,
            'teacher' => 'Teacher',
            'type_course' => 1,
            'intflag' => 1,
            'statuseva' => 2,
            'reasonid' => null,
            'reason' => null,
            'mean' => null,
            'sd' => null,
            'score_a' => '80-100',
            'score_bb' => null,
            'score_b' => null,
            'score_cc' => null,
            'score_c' => null,
            'score_dd' => null,
            'score_d' => null,
            'score_f' => null,
            'grade_stds' => [[
                'sec' => $section,
                'fac' => 'SC',
                'type_course' => 1,
                'num_a' => 10,
                'num_bb' => 0,
                'num_b' => 0,
                'num_cc' => 0,
                'num_c' => 0,
                'num_dd' => 0,
                'num_d' => 0,
                'num_f' => 0,
                'num_i' => 0,
                'num_s' => 0,
                'num_v' => 0,
                'num_w' => 0,
                'num_ff' => 0,
                'num_out' => 0,
                'numstdevz' => null,
                'evaluationscore' => null,
            ]],
        ], $extra);
    }

    #[Test]
    public function it_merges_same_subject_sections_and_skips_duplicate_section(): void
    {
        config(['filesystems.upload_disk' => 'local']);
        Storage::fake('local');

        $parser = Mockery::mock(RegistrarGradePdfParser::class);
        $pending = Mockery::mock(InstructorPendingRegistrarService::class);

        $file1 = UploadedFile::fake()->createWithContent('SC101011-01.pdf', '%PDF-sec1-unique');
        $file2 = UploadedFile::fake()->createWithContent('SC101011-02.pdf', '%PDF-sec2-unique');
        $file3 = UploadedFile::fake()->createWithContent('SC101011-01-copy.pdf', '%PDF-sec1-other');

        $parser->shouldReceive('parse')
            ->once()
            ->withArgs(fn ($path, $name) => $name === 'SC101011-01.pdf')
            ->andReturn($this->makeParsed('SC101011', 1));
        $parser->shouldReceive('parse')
            ->once()
            ->withArgs(fn ($path, $name) => $name === 'SC101011-02.pdf')
            ->andReturn($this->makeParsed('SC101011', 2));
        $parser->shouldReceive('parse')
            ->once()
            ->withArgs(fn ($path, $name) => $name === 'SC101011-01-copy.pdf')
            ->andReturn($this->makeParsed('SC101011', 1));

        $parser->shouldReceive('canonicalFilename')
            ->andReturnUsing(fn (string $code, int $sec) => sprintf('%s-%02d.pdf', $code, $sec));

        $pending->shouldReceive('forgetAll')->once();
        $pending->shouldReceive('remember')->twice();

        $service = new InstructorRegistrarUploadBatchService($parser, $pending);
        $result = $service->process([$file1, $file2, $file3], 2, 2568, 99);

        $this->assertCount(2, $result['accepted']);
        $this->assertCount(1, $result['duplicates']);
        $this->assertSame('SC101011-01-copy.pdf', $result['duplicates'][0]['name']);
        $this->assertSame('SC101011', $result['merged']['subject_code']);
        $this->assertCount(2, $result['merged']['grade_stds']);
        $this->assertSame(1, (int) $result['merged']['grade_stds'][0]['sec']);
        $this->assertSame(2, (int) $result['merged']['grade_stds'][1]['sec']);
    }

    #[Test]
    public function it_rejects_mixed_subjects(): void
    {
        config(['filesystems.upload_disk' => 'local']);
        Storage::fake('local');

        $parser = Mockery::mock(RegistrarGradePdfParser::class);
        $pending = Mockery::mock(InstructorPendingRegistrarService::class);

        $file1 = UploadedFile::fake()->createWithContent('A.pdf', '%PDF-a');
        $file2 = UploadedFile::fake()->createWithContent('B.pdf', '%PDF-b');

        $parser->shouldReceive('parse')->once()->andReturn($this->makeParsed('SC101011', 1));
        $parser->shouldReceive('parse')->once()->andReturn($this->makeParsed('SC401203', 1));
        $pending->shouldNotReceive('forgetAll');
        $pending->shouldNotReceive('remember');

        $service = new InstructorRegistrarUploadBatchService($parser, $pending);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ไม่ใช่วิชาเดียวกัน');
        $service->process([$file1, $file2], 2, 2568, 1);
    }

    #[Test]
    public function it_skips_exact_content_duplicates(): void
    {
        config(['filesystems.upload_disk' => 'local']);
        Storage::fake('local');

        $parser = Mockery::mock(RegistrarGradePdfParser::class);
        $pending = Mockery::mock(InstructorPendingRegistrarService::class);

        $content = '%PDF-same-bytes';
        $file1 = UploadedFile::fake()->createWithContent('first.pdf', $content);
        $file2 = UploadedFile::fake()->createWithContent('second.pdf', $content);

        $parser->shouldReceive('parse')->twice()->andReturn(
            $this->makeParsed('SC101011', 1),
            $this->makeParsed('SC101011', 2), // different section in metadata, but same bytes → content dup wins
        );
        $parser->shouldReceive('canonicalFilename')->andReturn('SC101011-01.pdf');

        $pending->shouldReceive('forgetAll')->once();
        $pending->shouldReceive('remember')->once();

        $service = new InstructorRegistrarUploadBatchService($parser, $pending);
        $result = $service->process([$file1, $file2], 2, 2568, 1);

        $this->assertCount(1, $result['accepted']);
        $this->assertCount(1, $result['duplicates']);
        $this->assertStringContainsString('เนื้อหาไฟล์ซ้ำ', $result['duplicates'][0]['reason']);
    }
}
