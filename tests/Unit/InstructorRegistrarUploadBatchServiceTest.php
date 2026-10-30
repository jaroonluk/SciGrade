<?php

namespace Tests\Unit;

use App\Services\Instructor\InstructorPendingRegistrarService;
use App\Services\Instructor\InstructorRegistrarUploadBatchService;
use App\Services\RegistrarGradePdfParser;
use App\Services\RegistrarPdfParseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
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
            'has_manual_score_ranges' => true,
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

    private function stubManualRangeChecks(MockInterface $parser): void
    {
        $parser->shouldReceive('hasManualScoreRanges')
            ->byDefault()
            ->andReturnUsing(static function (array $parsed): bool {
                if (array_key_exists('has_manual_score_ranges', $parsed)) {
                    return (bool) $parsed['has_manual_score_ranges'];
                }

                return trim((string) ($parsed['score_a'] ?? '')) !== '';
            });
        $parser->shouldReceive('missingManualScoreRangesMessage')
            ->byDefault()
            ->andReturnUsing(static fn (?string $name = null): string => 'อ่าน MANUAL ไม่ได้'.($name ? ' (ไฟล์: '.$name.')' : ''));
    }

    #[Test]
    public function it_merges_same_subject_sections_and_skips_duplicate_section(): void
    {
        config(['filesystems.upload_disk' => 'local']);
        Storage::fake('local');

        $parser = Mockery::mock(RegistrarGradePdfParser::class);
        $this->stubManualRangeChecks($parser);
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

        $pending->shouldReceive('pendingItems')->andReturn([]);
        $pending->shouldReceive('remember')->twice();

        $service = Mockery::mock(InstructorRegistrarUploadBatchService::class, [$parser, $pending])
            ->makePartial();
        $service->shouldReceive('existingSectionsForCourse')->andReturn([]);

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
    public function it_rejects_files_when_term_or_year_does_not_match_selection(): void
    {
        config(['filesystems.upload_disk' => 'local']);
        Storage::fake('local');

        $parser = Mockery::mock(RegistrarGradePdfParser::class);
        $this->stubManualRangeChecks($parser);
        $pending = Mockery::mock(InstructorPendingRegistrarService::class);

        $file = UploadedFile::fake()->createWithContent('SC101011-01.pdf', '%PDF-term1');

        $parser->shouldReceive('parse')
            ->once()
            ->andReturn($this->makeParsed('SC101011', 1, [
                'term' => 1,
                'year' => 2568,
            ]));
        $pending->shouldNotReceive('forgetAll');
        $pending->shouldNotReceive('remember');

        $service = new InstructorRegistrarUploadBatchService($parser, $pending);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ไฟล์ มข.11 ไม่ตรงกับภาคการศึกษาและปีการศึกษาที่เลือกในหน้าจอ');

        // หน้าจอเลือกภาคปลาย แต่ไฟล์เป็นภาคต้น
        $service->process([$file], 2, 2568, 1);
    }

    #[Test]
    public function it_rejects_files_without_manual_score_ranges(): void
    {
        config(['filesystems.upload_disk' => 'local']);
        Storage::fake('local');

        $parser = Mockery::mock(RegistrarGradePdfParser::class);
        $this->stubManualRangeChecks($parser);
        $pending = Mockery::mock(InstructorPendingRegistrarService::class);

        $file = UploadedFile::fake()->createWithContent('SC001001-01.pdf', '%PDF-no-manual');

        $parser->shouldReceive('parse')
            ->once()
            ->andReturn($this->makeParsed('SC001001', 1, [
                'score_a' => null,
                'has_manual_score_ranges' => false,
            ]));
        $pending->shouldNotReceive('remember');

        $service = new InstructorRegistrarUploadBatchService($parser, $pending);

        $this->expectException(RegistrarPdfParseException::class);
        $this->expectExceptionMessage('อ่าน MANUAL ไม่ได้');

        $service->process([$file], 2, 2568, 1);
    }

    #[Test]
    public function it_rejects_mixed_subjects(): void
    {
        config(['filesystems.upload_disk' => 'local']);
        Storage::fake('local');

        $parser = Mockery::mock(RegistrarGradePdfParser::class);
        $this->stubManualRangeChecks($parser);
        $pending = Mockery::mock(InstructorPendingRegistrarService::class);

        $file1 = UploadedFile::fake()->createWithContent('A.pdf', '%PDF-a');
        $file2 = UploadedFile::fake()->createWithContent('B.pdf', '%PDF-b');

        $parser->shouldReceive('parse')->once()->andReturn($this->makeParsed('SC101011', 1));
        $parser->shouldReceive('parse')->once()->andReturn($this->makeParsed('SC401203', 1));
        $pending->shouldNotReceive('forgetAll');
        $pending->shouldNotReceive('remember');

        $service = new InstructorRegistrarUploadBatchService($parser, $pending);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ไฟล์ที่เลือกไม่ใช่วิชาเดียวกัน กรุณาอัปโหลดเฉพาะไฟล์ของรายวิชาเดียว');
        $service->process([$file1, $file2], 2, 2568, 1);
    }

    #[Test]
    public function it_skips_exact_content_duplicates(): void
    {
        config(['filesystems.upload_disk' => 'local']);
        Storage::fake('local');

        $parser = Mockery::mock(RegistrarGradePdfParser::class);
        $this->stubManualRangeChecks($parser);
        $pending = Mockery::mock(InstructorPendingRegistrarService::class);

        $content = '%PDF-same-bytes';
        $file1 = UploadedFile::fake()->createWithContent('first.pdf', $content);
        $file2 = UploadedFile::fake()->createWithContent('second.pdf', $content);

        $parser->shouldReceive('parse')->twice()->andReturn(
            $this->makeParsed('SC101011', 1),
            $this->makeParsed('SC101011', 2), // different section in metadata, but same bytes → content dup wins
        );
        $parser->shouldReceive('canonicalFilename')->andReturn('SC101011-01.pdf');

        $pending->shouldReceive('pendingItems')->andReturn([]);
        $pending->shouldReceive('remember')->once();

        $service = Mockery::mock(InstructorRegistrarUploadBatchService::class, [$parser, $pending])
            ->makePartial();
        $service->shouldReceive('existingSectionsForCourse')->andReturn([]);

        $result = $service->process([$file1, $file2], 2, 2568, 1);

        $this->assertCount(1, $result['accepted']);
        $this->assertCount(1, $result['duplicates']);
        $this->assertStringContainsString('เนื้อหาไฟล์ซ้ำ', $result['duplicates'][0]['reason']);
    }

    #[Test]
    public function it_skips_sections_that_already_exist_in_the_system(): void
    {
        config(['filesystems.upload_disk' => 'local']);
        Storage::fake('local');

        $parser = Mockery::mock(RegistrarGradePdfParser::class);
        $this->stubManualRangeChecks($parser);
        $pending = Mockery::mock(InstructorPendingRegistrarService::class);

        $file1 = UploadedFile::fake()->createWithContent('SC101011-01.pdf', '%PDF-sec1-unique');
        $file2 = UploadedFile::fake()->createWithContent('SC101011-02.pdf', '%PDF-sec2-unique');

        $parser->shouldReceive('parse')
            ->once()
            ->withArgs(fn ($path, $name) => $name === 'SC101011-01.pdf')
            ->andReturn($this->makeParsed('SC101011', 1));
        $parser->shouldReceive('parse')
            ->once()
            ->withArgs(fn ($path, $name) => $name === 'SC101011-02.pdf')
            ->andReturn($this->makeParsed('SC101011', 2));

        $parser->shouldReceive('canonicalFilename')
            ->andReturnUsing(fn (string $code, int $sec) => sprintf('%s-%02d.pdf', $code, $sec));

        $pending->shouldReceive('pendingItems')->andReturn([]);
        $pending->shouldReceive('remember')->once();

        $service = Mockery::mock(InstructorRegistrarUploadBatchService::class, [$parser, $pending])
            ->makePartial();
        $service->shouldReceive('existingSectionsForCourse')
            ->once()
            ->with('SC101011', 2, 2568)
            ->andReturn([1 => true]);

        $result = $service->process([$file1, $file2], 2, 2568, 99);

        $this->assertCount(1, $result['accepted']);
        $this->assertSame(2, $result['accepted'][0]['section']);
        $this->assertCount(1, $result['already_exists']);
        $this->assertSame(1, $result['already_exists'][0]['section']);
        $this->assertStringContainsString('มีข้อมูลในระบบแล้ว', $result['already_exists'][0]['reason']);
        $this->assertStringContainsString('ไม่ต้องอัปโหลดหรือกรอกเพิ่ม', $result['already_exists'][0]['reason']);
    }

    #[Test]
    public function it_returns_friendly_result_when_all_sections_already_exist(): void
    {
        config(['filesystems.upload_disk' => 'local']);
        Storage::fake('local');

        $parser = Mockery::mock(RegistrarGradePdfParser::class);
        $this->stubManualRangeChecks($parser);
        $pending = Mockery::mock(InstructorPendingRegistrarService::class);

        $file1 = UploadedFile::fake()->createWithContent('SC101011-01.pdf', '%PDF-sec1-only');

        $parser->shouldReceive('parse')->once()->andReturn($this->makeParsed('SC101011', 1));
        $pending->shouldNotReceive('forgetAll');
        $pending->shouldNotReceive('remember');

        $service = Mockery::mock(InstructorRegistrarUploadBatchService::class, [$parser, $pending])
            ->makePartial();
        $service->shouldReceive('existingSectionsForCourse')
            ->once()
            ->andReturn([1 => true]);

        $result = $service->process([$file1], 2, 2568, 1);

        $this->assertNull($result['merged']);
        $this->assertSame([], $result['accepted']);
        $this->assertCount(1, $result['already_exists']);
    }

    #[Test]
    public function it_merges_parsed_payloads_by_section(): void
    {
        $parser = Mockery::mock(RegistrarGradePdfParser::class);
        $pending = Mockery::mock(InstructorPendingRegistrarService::class);
        $service = new InstructorRegistrarUploadBatchService($parser, $pending);

        $base = $this->makeParsed('SC101011', 1, [
            'grade_i_students' => [
                ['name' => 'A', 'student_code' => '1', 'section' => 1],
            ],
        ]);
        $incoming = $this->makeParsed('SC101011', 2, [
            'grade_stds' => [[
                'sec' => 1,
                'fac' => 'SC',
                'num_a' => 99,
                'num_bb' => 0, 'num_b' => 0, 'num_cc' => 0, 'num_c' => 0,
                'num_dd' => 0, 'num_d' => 0, 'num_f' => 0, 'num_i' => 0,
                'num_s' => 0, 'num_v' => 0, 'num_w' => 0, 'num_ff' => 0, 'num_out' => 0,
                'type_course' => 1, 'numstdevz' => null, 'evaluationscore' => null,
            ], [
                'sec' => 2,
                'fac' => 'SC',
                'num_a' => 5,
                'num_bb' => 0, 'num_b' => 0, 'num_cc' => 0, 'num_c' => 0,
                'num_dd' => 0, 'num_d' => 0, 'num_f' => 0, 'num_i' => 0,
                'num_s' => 0, 'num_v' => 0, 'num_w' => 0, 'num_ff' => 0, 'num_out' => 0,
                'type_course' => 1, 'numstdevz' => null, 'evaluationscore' => null,
            ]],
            'grade_i_students' => [
                ['name' => 'B', 'student_code' => '2', 'section' => 2],
            ],
        ]);

        $merged = $service->mergeParsed($base, $incoming);

        $this->assertCount(2, $merged['grade_stds']);
        $this->assertSame(99, (int) $merged['grade_stds'][0]['num_a']);
        $this->assertSame(2, (int) $merged['grade_stds'][1]['sec']);
        $this->assertCount(2, $merged['grade_i_students']);
    }

    #[Test]
    public function it_builds_section_progress_from_reg_minus_existing(): void
    {
        $parser = Mockery::mock(RegistrarGradePdfParser::class);
        $pending = Mockery::mock(InstructorPendingRegistrarService::class);
        $pending->shouldReceive('pendingBySection')
            ->once()
            ->andReturn([
                2 => ['section' => 2, 'name' => 'SC602005-02.pdf', 'path' => 'tmp/a.pdf'],
            ]);

        $service = Mockery::mock(InstructorRegistrarUploadBatchService::class, [$parser, $pending])
            ->makePartial();
        $service->shouldReceive('regSectionsForCourse')
            ->once()
            ->with('SC602005', 1, 2568)
            ->andReturn([
                'sections' => [1, 2, 3],
                'from_reg' => true,
                'subject_name' => 'PROBABILITY AND STATISTICS',
            ]);
        $service->shouldReceive('existingSectionsForCourse')
            ->once()
            ->with('SC602005', 1, 2568)
            ->andReturn([1 => true]);

        $progress = $service->sectionProgress('SC602005', 1, 2568, 'PROBABILITY AND STATISTICS');

        $this->assertTrue($progress['gated']);
        $this->assertFalse($progress['complete']);
        $this->assertSame([2, 3], $progress['required_sections']);
        $this->assertSame(2, $progress['required_count']);
        $this->assertSame([2], $progress['attached_sections']);
        $this->assertSame(1, $progress['attached_count']);
        $this->assertSame([3], $progress['missing_sections']);
        $this->assertSame(1, $progress['missing_count']);
        $this->assertSame('SC602005', $progress['subject_code']);
        $this->assertStringContainsString('ภาคต้น', $progress['term_label']);
    }
}
