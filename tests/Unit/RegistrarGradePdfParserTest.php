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
    public function it_parses_sc602005_decimal_boundary_summary(): void
    {
        // รูปแบบทศนิยมจาก SC602005: % + จำนวน + ขอบเขตล่าง.50 ติดกัน
        $text = <<<'TXT'
CONTROL CODE:6603715
หมายเหตุชื่อ-สกุลเกรดรหัสประจำตัวลำดับ
ปริญญาตรี ภาคปกติ คณะวิทยาศาสตร์
SC602005 : PROBABILITY AND STATISTICS
ใบส่งผลการศึกษา
ภาคการศึกษาที่ 1 / 2567
ผู้สอน
มหาวิทยาลัยขอนแก่น
ผศ.ดร.ทดสอบ ระบบ กลุ่ม 1
รายวิชา
ระดับการศึกษา
วิทยาเขต ขอนแก่น
หน่วยกิต 3 (3-0-6)
นายหนึ่ง ทดสอบA663020001-11
นายสอง ทดสอบB663020002-22
%รวมMANUALเกรด
12.86979.50 >>A
5.71474.50 - 79.49B+
4.29369.50 - 74.49B
24.291759.50 - 69.49C+
15.711149.50 - 59.49C
28.572039.50 - 49.49D+
1.43134.50 - 39.49D
4.2930 - 34.49F
2.862<<->>W
100.0070รวม
CONTROL CODE:6603715
TXT;

        $parsed = (new RegistrarGradePdfParser)->parseText($text, 'SC602005-01.pdf', 1, 2567);
        $std = $parsed['grade_stds'][0];

        $this->assertSame('SC602005', $parsed['subject_code']);
        $this->assertTrue($parsed['has_manual_score_ranges']);
        $this->assertSame(0, $parsed['intflag']);
        $this->assertSame('100.00-79.50', $parsed['score_a']);
        $this->assertSame('79.49-74.50', $parsed['score_bb']);
        $this->assertSame('74.49-69.50', $parsed['score_b']);
        $this->assertSame('69.49-59.50', $parsed['score_cc']);
        $this->assertSame('59.49-49.50', $parsed['score_c']);
        $this->assertSame('49.49-39.50', $parsed['score_dd']);
        $this->assertSame('39.49-34.50', $parsed['score_d']);
        $this->assertSame('34.49-0.00', $parsed['score_f']);
        $this->assertSame(9, $std['num_a']);
        $this->assertSame(4, $std['num_bb']);
        $this->assertSame(3, $std['num_b']);
        $this->assertSame(17, $std['num_cc']);
        $this->assertSame(11, $std['num_c']);
        $this->assertSame(20, $std['num_dd']);
        $this->assertSame(1, $std['num_d']);
        $this->assertSame(3, $std['num_f']);
        $this->assertSame(2, $std['num_w']);
    }

    #[Test]
    public function it_parses_glued_manual_and_count_columns_accurately(): void
    {
        // รูปแบบจริงจากใบ มข.11 ที่ PDF ดึงข้อความคอลัมน์ %+รวม+MANUAL+เกรด ติดกัน
        // ตรงกับตาราง: A 100-80 (1), B+ 79-74 (3), ... F 34-0 (4), รวม 143
        $text = <<<'TXT'
CONTROL CODE:7461907
หมายเหตุชื่อ-สกุลเกรดรหัสประจำตัวลำดับ
ปริญญาตรี ภาคปกติ คณะวิทยาศาสตร์
SC202002 : Chemistry Laboratory for Agricultural Science
ใบส่งผลการศึกษา
ภาคการศึกษาที่ 1 / 2567
ผู้สอน
มหาวิทยาลัยขอนแก่น
อ.ดร.ทดสอบ ระบบ กลุ่ม 1
รายวิชา
ระดับการศึกษา
วิทยาเขต ขอนแก่น
หน่วยกิต 1 (0-3-2)
นายหนึ่ง ทดสอบA663020001-11
นายสอง ทดสอบB+663020002-22
%รวมMANUALเกรด
0.701100-80A
2.10379-74B+
9.091373-69B
48.957068-59C+
31.474558-50C
4.20649-41D+
0.70140-35D
2.804F
100.00143รวม
CONTROL CODE:7461907
34-0
TXT;

        $parsed = (new RegistrarGradePdfParser)->parseText($text, 'SC202002-01.pdf', 1, 2567);
        $std = $parsed['grade_stds'][0];

        $this->assertSame('SC202002', $parsed['subject_code']);
        $this->assertTrue($parsed['has_manual_score_ranges']);
        $this->assertSame('100-80', $parsed['score_a']);
        $this->assertSame('79-74', $parsed['score_bb']);
        $this->assertSame('73-69', $parsed['score_b']);
        $this->assertSame('68-59', $parsed['score_cc']);
        $this->assertSame('58-50', $parsed['score_c']);
        $this->assertSame('49-41', $parsed['score_dd']);
        $this->assertSame('40-35', $parsed['score_d']);
        $this->assertSame('34-0', $parsed['score_f']);
        $this->assertSame(1, $std['num_a']);
        $this->assertSame(3, $std['num_bb']);
        $this->assertSame(13, $std['num_b']);
        $this->assertSame(70, $std['num_cc']);
        $this->assertSame(45, $std['num_c']);
        $this->assertSame(6, $std['num_dd']);
        $this->assertSame(1, $std['num_d']);
        $this->assertSame(4, $std['num_f']);
        $this->assertSame(143, $std['num_a'] + $std['num_bb'] + $std['num_b'] + $std['num_cc']
            + $std['num_c'] + $std['num_dd'] + $std['num_d'] + $std['num_f']);
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

    #[Test]
    public function it_joins_broken_manual_lines_and_reads_ranges(): void
    {
        $text = <<<'TXT'
CONTROL CODE:1188541
SC152102 : Test Subject
ใบส่งผลการศึกษา
ภาคการศึกษาที่ 2 / 2567
ผู้สอน มหาวิทยาลัยขอนแก่น อ.ทดสอบ กลุ่ม 1
หน่วยกิต 1 (0-3-2)
นายหนึ่ง ทดสอบA663020001-11
%รวมMANUALเกรด
20.007100
-80
A
5.71279
-75
B+
14.29574
-70
B
11.43469
-65
C+
20.00764
-60
C
11.43459
-55
D+
14.29554
-50
D
0.00049
-0
F
100.0035รวม
CONTROL CODE:1188541
controlcode : 1188541
TXT;

        $parsed = (new RegistrarGradePdfParser)->parseText($text, 'SC152102-01.pdf', 2, 2567);

        $this->assertTrue($parsed['has_manual_score_ranges']);
        $this->assertSame('100-80', $parsed['score_a']);
        $this->assertSame('79-75', $parsed['score_bb']);
        $this->assertSame('49-0', $parsed['score_f']);
    }

    #[Test]
    public function it_parses_ascending_min_max_glued_manual_rows(): void
    {
        $text = <<<'TXT'
CONTROL CODE:1965126
SC153602 : Test Subject
ใบส่งผลการศึกษา
ภาคการศึกษาที่ 2 / 2567
ผู้สอน มหาวิทยาลัยขอนแก่น อ.ทดสอบ กลุ่ม 1
หน่วยกิต 2 (1-3-4)
นายหนึ่ง ทดสอบB663020001-11
%รวมMANUALเกรด
0.00085-100A
9.09380-84B+
27.27975-79B
21.21770-74C+
36.361260-69C
0.00055-59D+
6.06250-54D
0.0000-49F
100.0033รวม
CONTROL CODE:1965126
TXT;

        $parsed = (new RegistrarGradePdfParser)->parseText($text, 'SC153602-01.pdf', 2, 2567);

        $this->assertTrue($parsed['has_manual_score_ranges']);
        $this->assertSame('100-85', $parsed['score_a']);
        $this->assertSame('84-80', $parsed['score_bb']);
        $this->assertSame('49-0', $parsed['score_f']);
    }

    #[Test]
    public function it_parses_trailing_spaced_max_min_ranges(): void
    {
        $text = <<<'TXT'
CONTROL CODE:4324853
SC101004 : Test Subject
ใบส่งผลการศึกษา
ภาคการศึกษาที่ 2 / 2567
ผู้สอน มหาวิทยาลัยขอนแก่น อ.ทดสอบ กลุ่ม 1
หน่วยกิต 1 (0-2-1)
นายหนึ่ง ทดสอบA663020001-11
%รวมMANUALเกรด
30.1128A
25.8124B+
30.1128B
3.233C+
1.081C
1.081D+
1.081D
3.233F
100.0093รวม
CONTROL CODE:4324853
controlcode : 4324853
100 - 80
79 - 75
74 - 70
69 - 65
64 - 60
59 - 55
54 - 50
49 - 0
TXT;

        $parsed = (new RegistrarGradePdfParser)->parseText($text, 'SC101004-01.pdf', 2, 2567);

        $this->assertTrue($parsed['has_manual_score_ranges']);
        $this->assertSame('100-80', $parsed['score_a']);
        $this->assertSame('49-0', $parsed['score_f']);
    }

    #[Test]
    public function it_does_not_treat_credit_hours_as_score_f(): void
    {
        $text = <<<'TXT'
CONTROL CODE:9522191
SC001001 : Test Subject
ใบส่งผลการศึกษา
ภาคการศึกษาที่ 2 / 2567
ผู้สอน มหาวิทยาลัยขอนแก่น อ.ทดสอบ กลุ่ม 1
หน่วยกิต 3 (3-0-6)
นายหนึ่ง ทดสอบA663020001-11
%รวมMANUALเกรด
44.3343<<->>A
38.1437<<->>B+
5.155<<->>B
4.124<<->>C+
3.093<<->>C
1.031<<->>D+
3.093<<->>F
1.031<<->>W
100.0097รวม
CONTROL CODE:9522191
controlcode : 9522191
TXT;

        $parsed = (new RegistrarGradePdfParser)->parseText($text, 'SC001001-01.pdf', 2, 2567);

        $this->assertFalse($parsed['has_manual_score_ranges']);
        $this->assertNull($parsed['score_a']);
        $this->assertNull($parsed['score_f']);
    }

    #[Test]
    public function it_accepts_su_manual_thresholds_as_readable(): void
    {
        $text = <<<'TXT'
CONTROL CODE:6177547
SC002001 : Test Subject
ใบส่งผลการศึกษา
ภาคการศึกษาที่ 2 / 2567
ผู้สอน มหาวิทยาลัยขอนแก่น อ.ทดสอบ กลุ่ม 1
หน่วยกิต 1 (0-2-2)
นายหนึ่ง ทดสอบS663020001-11
%รวมMANUALเกรด
95.0411580 >>S
0.8310 - 79.99U
4.135<<->>W
100.00121รวม
CONTROL CODE:6177547
TXT;

        $parsed = (new RegistrarGradePdfParser)->parseText($text, 'SC002001-01.pdf', 2, 2567);

        $this->assertTrue($parsed['has_manual_score_ranges']);
        $this->assertSame('100-80', $parsed['score_s']);
        $this->assertSame('79.99-0.00', $parsed['score_u']);
    }

    #[Test]
    public function it_parses_horizontal_grade_manual_percent_table(): void
    {
        $text = <<<'TXT'
CONTROL CODE:9205348
SC101014 : BIOLOGY FOR AGRICULTURE II LABORATORY
ใบส่งผลการศึกษา
ภาคการศึกษาที่ 2 / 2568
ผู้สอน มหาวิทยาลัยขอนแก่น อ.ทดสอบ กลุ่ม 1
หน่วยกิต 1 (0-2-1)
นายหนึ่ง ทดสอบA663020001-11
เกรด MANUAL รวม %
A 80 - 100 4 2.06
B+ 75 - 79 3 1.55
B 70 - 74 19 9.79
C+ 65 - 69 28 14.43
C 60 - 64 43 22.16
D+ 55 - 59 41 21.13
D 50 - 54 23 11.86
F 0 - 49 32 16.49
W - 1 0.52
รวม 194 100.00
CONTROL CODE:9205348
controlcode : 9205348
TXT;

        $parsed = (new RegistrarGradePdfParser)->parseText($text, 'SC101014-01.pdf', 2, 2568);

        $this->assertTrue($parsed['has_manual_score_ranges']);
        $this->assertSame('100-80', $parsed['score_a']);
        $this->assertSame('79-75', $parsed['score_bb']);
        $this->assertSame('49-0', $parsed['score_f']);
        $this->assertSame(4, $parsed['grade_stds'][0]['num_a']);
        $this->assertSame(32, $parsed['grade_stds'][0]['num_f']);
    }

    #[Test]
    public function it_reads_trailing_ranges_when_summary_is_t_score_empty(): void
    {
        $text = <<<'TXT'
CONTROL CODE:1181654
SC252104 : Organic Chemistry Laboratory II
ใบส่งผลการศึกษา
ภาคการศึกษาที่ 2 / 2568
ผู้สอน มหาวิทยาลัยขอนแก่น อ.ทดสอบ กลุ่ม 1
หน่วยกิต 1 (0-3-2)
นายหนึ่ง ทดสอบA663020001-11
%รวมT-SCOREเกรด
13.794<<->>A
24.147<<->>B+
27.598<<->>B
27.598<<->>C+
6.902<<->>C
100.0029รวม
CONTROL CODE:1181654
controlcode : 1181654100-80 79-72 71-64 63-56 55-48
TXT;

        $parsed = (new RegistrarGradePdfParser)->parseText($text, 'SC252104-01.pdf', 2, 2568);

        $this->assertTrue($parsed['has_manual_score_ranges']);
        $this->assertSame('100-80', $parsed['score_a']);
        $this->assertSame('79-72', $parsed['score_bb']);
        $this->assertSame('55-48', $parsed['score_c']);
    }

    #[Test]
    public function it_parses_tesseract_thai_spacing_and_manual_arrows(): void
    {
        $ocr = <<<'TXT'
ใบ ส ่ ง ผล ก า ร ศึ ก ษา
ร า ย ว ิ ชา $C002004 : FUNDAMENTAL OF PERSONAL IDENTIFICATION
ภา ค ก า ร ศึ ก ษา ท ี ่ 1 / 2568
ผ ศ . ด ร . ท ด ส อ บ ก ล ุ่ ม 1
1 643020528-6 A น า ย อ า ฤ ญ ช์
เก ร ด MANUAL ร ว ม %
A 80 >> 492 74.66
B+ 75 - 79.99 87 13.20
B 70 - 74.99 37 5.61
C+ 65 - 69.99 12 1.82
0 60 - 64.99 6 0.91
D+ 55 - 59.99 3 0.46
D 50 - 54.99 1 0.15
F 0 - 49.99 12 1.82
Ww <<->> 9 1.37
ร ว ม 659 100.00
controlcode : 51009048
TXT;

        $parser = new RegistrarGradePdfParser;
        $parsed = $parser->parseText($parser->normalizeOcrText($ocr), 'SC002004-01.pdf', 1, 2568);

        $this->assertSame('SC002004', $parsed['subject_code']);
        $this->assertSame(1, $parsed['term']);
        $this->assertSame(2568, $parsed['year']);
        $this->assertTrue($parsed['has_manual_score_ranges']);
        $this->assertSame('100-80', $parsed['score_a']);
        $this->assertSame('79.99-75.00', $parsed['score_bb']);
        $this->assertSame('64.99-60.00', $parsed['score_c']);
        $this->assertSame('49.99-0.00', $parsed['score_f']);
        $this->assertSame(492, $parsed['grade_stds'][0]['num_a']);
        $this->assertSame(6, $parsed['grade_stds'][0]['num_c']);
        $this->assertSame(12, $parsed['grade_stds'][0]['num_f']);
        $this->assertSame(9, $parsed['grade_stds'][0]['num_w']);
        $this->assertSame(1, $parsed['grade_stds'][0]['sec']);
        $this->assertSame('FUNDAMENTAL OF PERSONAL IDENTIFICATION', $parsed['subject']);
    }

    #[Test]
    public function it_repairs_garbled_ocr_subject_code_and_misread_grade_letters(): void
    {
        $ocr = <<<'TXT'
ภาคการศึกษาที่ 1 / 2568
รายวิชา ธ 6 ๐ 554231 : Theoretical Physics หน่วยกิต 3 (3-0-6)
ผู้สอนรศ.ดร.ทดสอบกลุ่ม 1
1 653020113-6 A นายทดสอบหนึ่ง
เกรด MANUAL รวม %
A 100-80 <<->> 2 100.00
8+ 75 - 79.99 0 0.00
8 70 - 74.99 0 0.00
C4 65 - 69.99 0 0.00
0 60 - 64.99 0 0.00
F 0-49.99 0 0.00
รวม 2 100.00
TXT;

        $parser = new RegistrarGradePdfParser;
        $parsed = $parser->parseText($parser->normalizeOcrText($ocr, 'SC554231-01.pdf'), 'SC554231-01.pdf', 1, 2568);

        $this->assertSame('SC554231', $parsed['subject_code']);
        $this->assertSame('Theoretical Physics', $parsed['subject']);
        $this->assertTrue($parsed['has_manual_score_ranges']);
        $this->assertSame('100-80', $parsed['score_a']);
        $this->assertSame('79.99-75.00', $parsed['score_bb']);
        $this->assertSame('74.99-70.00', $parsed['score_b']);
        $this->assertSame('69.99-65.00', $parsed['score_cc']);
        $this->assertSame('64.99-60.00', $parsed['score_c']);
        $this->assertSame('49.99-0.00', $parsed['score_f']);
        $this->assertSame(2, $parsed['grade_stds'][0]['num_a']);
    }
}
