<?php

namespace Tests\Unit;

use App\Services\RegistrarGradePdfParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrarGradePdfParserTest extends TestCase
{
    #[Test]
    public function it_parses_college_headers_without_faculty_prefix(): void
    {
        $path = base_path('project_old/file_test/SC700001-04.pdf');
        if (! is_file($path)) {
            $this->markTestSkipped('SC700001-04 sample PDF not available.');
        }

        $parsed = (new RegistrarGradePdfParser)->parse($path, 'SC700001-04.pdf', 2, 2568);
        $fac = explode(',', (string) $parsed['grade_stds'][0]['fac']);

        $this->assertSame('SC700001', $parsed['subject_code']);
        $this->assertContains('CP', $fac); // วิทยาลัยการคอมพิวเตอร์
        $this->assertContains('COLA', $fac); // วิทยาลัยการปกครองท้องถิ่น
        $this->assertContains('SC', $fac);
        $this->assertContains('KKBS', $fac);
    }

    #[Test]
    public function it_parses_sc069991_s_au_summary_format(): void
    {
        $path = base_path('project_old/file_test/SC069991-01.pdf');
        if (! is_file($path)) {
            $this->markTestSkipped('SC069991-01 sample PDF not available.');
        }

        $parsed = (new RegistrarGradePdfParser)->parse($path, 'any.pdf', 1, 2568);
        $std = $parsed['grade_stds'][0];

        $this->assertSame('SC069991', $parsed['subject_code']);
        $this->assertSame(2, $parsed['term']);
        $this->assertSame(2568, $parsed['year']);
        $this->assertSame(7, $parsed['degree']);
        $this->assertSame(4, $parsed['type_course']);
        $this->assertSame(1, $std['sec']);
        $this->assertSame('SC', $std['fac']);
        $this->assertSame(1, $std['num_s']); // S AU → S
        $this->assertSame(0, $std['num_v']);
        $this->assertStringContainsString('รัชดาภรณ์', $parsed['teacher']);
    }

    #[Test]
    public function it_maps_s_au_to_s_and_u_to_u_and_skips_unknown_grades(): void
    {
        $text = <<<'TXT'
หมายเหตุชื่อ-สกุลเกรดรหัสประจำตัวลำดับ
ปริญญาตรี ภาคปกติ   คณะวิทยาศาสตร์
SC999001 : TEST COURSE
ใบส่งผลการศึกษา
ภาคการศึกษาที่ 1 / 2568
ผู้สอน
มหาวิทยาลัยขอนแก่น
อ.ทดสอบ ระบบ	กลุ่ม 1
รายวิชา
ระดับการศึกษา
วิทยาเขต ขอนแก่น
หน่วยกิต 1 (1-0-2)
คณะวิทยาศาสตร์
<>   นายหนึ่ง  ดีS683020001-01
<>   นายสอง  ผ่านAU683020002-82
<>   นายสาม  ไม่ผ่านU683020003-63
<>   นายสี่  พิเศษX683020004-44
<>   นายห้า  ได้A683020005-25
%รวมMANUALเกรด
20.001<<->>S
20.001<<->>AU
20.001<<->>U
20.001<<->>X
20.001<<->>A
100.005รวม
CONTROL CODE:1234567
controlcode : 1234567
TXT;

        $parsed = (new RegistrarGradePdfParser)->parseText($text, 'ignore.pdf', 1, 2568);
        $std = $parsed['grade_stds'][0];

        $this->assertSame(2, $std['num_s']); // S + AU
        $this->assertSame(1, $std['num_v']); // U → คอลัมน์ U
        $this->assertSame(1, $std['num_a']);
        $this->assertSame(0, $std['num_b']);
    }

    #[Test]
    public function it_parses_sc201101_compact_summary_arrows(): void
    {
        $path = base_path('project_old/file_test/SC201101-02.pdf');
        if (! is_file($path)) {
            $this->markTestSkipped('SC201101-02 sample PDF not available.');
        }

        $parsed = (new RegistrarGradePdfParser)->parse($path, 'any-name.pdf', 1, 2568);
        $std = $parsed['grade_stds'][0];

        $this->assertSame('SC201101', $parsed['subject_code']);
        $this->assertSame(2, $parsed['term']);
        $this->assertSame(2568, $parsed['year']);
        $this->assertSame(2, $parsed['type_course']);
        $this->assertSame(2, $std['sec']);
        $this->assertSame('PH', $std['fac']);
        $this->assertSame(3, $std['num_a']);
        $this->assertSame(8, $std['num_bb']);
        $this->assertSame(17, $std['num_b']);
        $this->assertSame(13, $std['num_cc']);
        $this->assertSame(12, $std['num_c']);
        $this->assertSame(0, $std['num_dd']);
        $this->assertSame(1, $std['num_d']);
        $this->assertSame(0, $std['num_f']);
        $this->assertSame(54, $std['num_a'] + $std['num_bb'] + $std['num_b'] + $std['num_cc']
            + $std['num_c'] + $std['num_dd'] + $std['num_d'] + $std['num_f'] + $std['num_w']);
        $this->assertSame('100-80', $parsed['score_a']);
        $this->assertSame('79-72', $parsed['score_bb']);
        $this->assertSame('23-0', $parsed['score_f']);
    }

    #[Test]
    public function it_parses_sc101011_text_sample_with_new_registrar_layout(): void
    {
        $path = base_path('tests/Fixtures/registrar-pdfs/SC101011-sample.txt');
        if (! is_file($path)) {
            $this->markTestSkipped('SC101011 text sample not available.');
        }

        $parsed = (new RegistrarGradePdfParser)->parseText(
            file_get_contents($path),
            'random-upload-name.pdf',
            1,
            2566,
        );

        $this->assertSame('SC101011', $parsed['subject_code']);
        $this->assertSame('BIOLOGY FOR AGRICULTURE I', $parsed['subject']);
        $this->assertSame(1, $parsed['term']);
        $this->assertSame(2566, $parsed['year']);
        $this->assertStringContainsString('ศุจีภรณ์', $parsed['teacher']);
        $this->assertSame(1, $parsed['grade_stds'][0]['sec']);
        $this->assertSame('AG', $parsed['grade_stds'][0]['fac']);
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
    }

    #[Test]
    public function it_selects_all_student_faculties_from_content(): void
    {
        $path = base_path('tests/Fixtures/registrar-pdfs/SC101011-multi-fac-sample.txt');
        if (! is_file($path)) {
            $this->markTestSkipped('Multi-faculty sample not available.');
        }

        $parsed = (new RegistrarGradePdfParser)->parseText(
            file_get_contents($path),
            'not-following-name-convention.pdf',
            2,
            2568,
        );

        $fac = explode(',', (string) $parsed['grade_stds'][0]['fac']);
        sort($fac);

        $this->assertSame(['AG', 'SC'], $fac);
        $this->assertSame(1, $parsed['grade_stds'][0]['sec']);
        $this->assertSame(
            'SC101011-01.pdf',
            (new RegistrarGradePdfParser)->canonicalFilename($parsed['subject_code'], (int) $parsed['grade_stds'][0]['sec']),
        );
    }

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
    public function it_parses_sc401203_summary_table_counts(): void
    {
        $path = base_path('tests/Fixtures/registrar-pdfs/SC401203-01.pdf');
        if (! is_file($path)) {
            $this->markTestSkipped('SC401203-01 fixture not available.');
        }

        $parsed = (new RegistrarGradePdfParser)->parse($path, 'SC401203-01.pdf', 1, 2569);
        $std = $parsed['grade_stds'][0];

        $this->assertSame('SC401203', $parsed['subject_code']);
        $this->assertSame(0, $std['num_a']);
        $this->assertSame(1, $std['num_bb']);
        $this->assertSame(6, $std['num_b']);
        $this->assertSame(5, $std['num_cc']);
        $this->assertSame(1, $std['num_c']);
        $this->assertSame(0, $std['num_dd']);
        $this->assertSame(1, $std['num_d']);
        $this->assertSame(0, $std['num_f']);
        $this->assertSame(14, $std['num_a'] + $std['num_bb'] + $std['num_b'] + $std['num_cc']
            + $std['num_c'] + $std['num_dd'] + $std['num_d'] + $std['num_f'] + $std['num_w']);
    }

    #[Test]
    public function it_parses_decimal_registrar_pdf(): void
    {
        $path = base_path('project_old/SC700001-08.pdf');
        if (! is_file($path)) {
            $this->markTestSkipped('Decimal sample PDF not available.');
        }

        $parsed = (new RegistrarGradePdfParser)->parse($path, 'SC700001-08.pdf', 2, 2568);

        $this->assertSame('SC700001', $parsed['subject_code']);
        $this->assertSame(0, $parsed['intflag']);
        $this->assertSame('100-75', $parsed['score_a']);
        $this->assertSame('74.99-67.00', $parsed['score_bb']);
        $this->assertSame(21, $parsed['grade_stds'][0]['num_a']);
        $this->assertSame(33, $parsed['grade_stds'][0]['num_a']
            + $parsed['grade_stds'][0]['num_bb']
            + $parsed['grade_stds'][0]['num_b']
            + $parsed['grade_stds'][0]['num_cc']
            + $parsed['grade_stds'][0]['num_c']);
    }

    #[Test]
    public function it_parses_teacher_with_ajarn_prefix(): void
    {
        $path = base_path('project_old/SC700001-06.pdf');
        if (! is_file($path)) {
            $this->markTestSkipped('SC700001-06 sample PDF not available.');
        }

        $parsed = (new RegistrarGradePdfParser)->parse($path, 'SC700001-06.pdf', 2, 2568);

        $this->assertStringContainsString('ลลิตา', $parsed['teacher']);
        $this->assertStringContainsString('ฐานวิสัย', $parsed['teacher']);
        $this->assertSame(6, $parsed['grade_stds'][0]['sec']);
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
