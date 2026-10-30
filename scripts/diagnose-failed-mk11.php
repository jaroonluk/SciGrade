<?php

declare(strict_types=1);

use Smalot\PdfParser\Parser;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$folder = 'D:\\ข้อมูลระบบ\\SCiGRADE\\ทดสอบระบบเกรดใหม่-20261022T082348Z-1-001\\ทดสอบระบบเกรดใหม่\\ทดสอบ เทอม 1-2567';
$names = [
    '326493-01.pdf', 'SC101016-01.pdf', 'SC113210-01.pdf', 'SC113601-01.pdf', 'SC113602-01.pdf', 'SC114774-01.pdf',
    'SC152201-02.pdf', 'SC152202-02.pdf', 'SC152203-02.pdf', 'SC153321-01.pdf', 'SC153501-01.pdf', 'SC153502-01.pdf',
    'SC153603-01.pdf', 'SC154403-01.pdf', 'SC154403-02.pdf', 'SC201101-02.pdf', 'SC201101-03.pdf', 'SC202002-01.pdf',
    'SC202002-02.pdf', 'SC202002-03.pdf', 'SC523319-01.pdf', 'SC563319-01.pdf', 'SC752106-02.pdf',
];

// ไฟล์ที่ผ่าน เพื่อเทียบ
$okSample = 'SC001001-01.pdf';

$parser = new Parser;
ini_set('memory_limit', '1024M');

$normalize = static function (string $raw): string {
    $text = str_replace(["\r\n", "\r"], "\n", $raw);
    $text = preg_replace("/\u{00A0}/u", ' ', $text) ?? $text;
    $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
    $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

    return trim($text);
};

$diagnose = static function (string $path, string $name) use ($parser, $normalize): array {
    $out = [
        'file' => $name,
        'size' => @filesize($path) ?: 0,
        'extract_ok' => false,
        'text_len' => 0,
        'compact_len' => 0,
        'has_title' => false,
        'has_control' => false,
        'has_subject' => false,
        'subject_match' => null,
        'has_term' => false,
        'term_match' => null,
        'has_section' => false,
        'section_hint' => null,
        'student_like_rows' => 0,
        'has_summary_hint' => false,
        'likely_image_only' => false,
        'fail_stage' => null,
        'preview' => '',
        'error' => null,
    ];

    try {
        $text = $parser->parseFile($path)->getText();
    } catch (Throwable $e) {
        $out['fail_stage'] = 'pdf_extract';
        $out['error'] = $e->getMessage();

        return $out;
    }

    if (! is_string($text) || trim($text) === '') {
        $out['fail_stage'] = 'empty_text';
        $out['likely_image_only'] = true;

        return $out;
    }

    $out['extract_ok'] = true;
    $norm = $normalize($text);
    $compact = preg_replace('/\s+/u', '', $norm) ?? '';
    $out['text_len'] = mb_strlen($norm);
    $out['compact_len'] = mb_strlen($compact);
    $out['preview'] = mb_substr(preg_replace('/\s+/u', ' ', $norm) ?? '', 0, 280);

    $out['has_title'] = str_contains($norm, 'ใบส่งผลการศึกษา');
    $out['has_control'] = preg_match('/control\s*code\s*:\s*(\d+)/iu', $norm) === 1
        || preg_match('/controlcode\s*:\s*(\d+)/iu', $norm) === 1;
    $out['has_subject'] = preg_match('/(?:^|\n)\s*([A-Z]{2}\d{5,8})\s*:\s*([^\n]+)/u', $norm, $sm) === 1;
    if ($out['has_subject']) {
        $out['subject_match'] = strtoupper(trim($sm[1])).' : '.trim($sm[2]);
    } else {
        // ลองรูปแบบอื่นที่อาจเจอ
        if (preg_match('/([A-Z]{2}\d{5,8})/u', $norm, $alt)) {
            $out['subject_match'] = 'loose: '.$alt[1];
        }
    }

    $out['has_term'] = preg_match('/ภาคการศึกษาที่\s*(\d+)\s*\/\s*(\d{4})/u', $norm, $tm) === 1;
    if ($out['has_term']) {
        $out['term_match'] = $tm[1].'/'.$tm[2];
    }

    if (preg_match('/กลุ่ม\s*(\d+)/u', $norm, $gm)) {
        $out['has_section'] = true;
        $out['section_hint'] = (int) $gm[1];
    } elseif (preg_match('/section\s*[:\s]*(\d+)/iu', $norm, $sm2)) {
        $out['has_section'] = true;
        $out['section_hint'] = (int) $sm2[1];
    }

    $out['student_like_rows'] = preg_match_all('/\b\d{8,10}\b/u', $norm) ?: 0;
    $out['has_summary_hint'] = str_contains($norm, 'สรุป')
        || preg_match('/\bA\b.*\bB\+/u', $norm) === 1
        || str_contains($norm, 'จำนวน');

    $out['likely_image_only'] = $compact === '' || mb_strlen($compact) < 40
        || (! $out['has_title'] && ! $out['has_control'] && ! $out['has_subject']);

    if ($out['likely_image_only'] && ! $out['has_title'] && ! $out['has_control'] && ! $out['has_subject']) {
        $out['fail_stage'] = 'image_only_or_no_markers';
    } elseif (! $out['has_title']) {
        $out['fail_stage'] = 'missing_title';
    } elseif (! $out['has_control']) {
        $out['fail_stage'] = 'missing_control_code';
    } elseif (! preg_match('/(?:^|\n)\s*([A-Z]{2}\d{5,8})\s*:\s*([^\n]+)/u', $norm)) {
        $out['fail_stage'] = 'missing_subject_line';
    } elseif (! $out['has_section'] && ! preg_match('/-(\d{2})\.pdf$/i', $name)) {
        $out['fail_stage'] = 'missing_section';
    } elseif ($out['student_like_rows'] === 0) {
        $out['fail_stage'] = 'missing_students_likely';
    } elseif (! $out['has_summary_hint']) {
        $out['fail_stage'] = 'missing_summary_likely';
    } else {
        $out['fail_stage'] = 'unknown_or_deeper_parse';
    }

    return $out;
};

$results = [];
foreach ($names as $name) {
    $path = $folder.DIRECTORY_SEPARATOR.$name;
    $results[] = $diagnose($path, $name);
    echo $name.' => '.$results[array_key_last($results)]['fail_stage']
        .' | title='.(int) $results[array_key_last($results)]['has_title']
        .' ctrl='.(int) $results[array_key_last($results)]['has_control']
        .' subj='.(int) $results[array_key_last($results)]['has_subject']
        .' len='.$results[array_key_last($results)]['text_len']
        ."\n";
}

$okPath = $folder.DIRECTORY_SEPARATOR.$okSample;
$ok = $diagnose($okPath, $okSample);
echo "\nOK SAMPLE {$okSample} => stage={$ok['fail_stage']} title=".(int) $ok['has_title']
    .' ctrl='.(int) $ok['has_control'].' subj='.(int) $ok['has_subject']
    .' subject='.($ok['subject_match'] ?? '').' term='.($ok['term_match'] ?? '')
    .' len='.$ok['text_len']."\n";

$outPath = storage_path('app/test-reports/mk11-failed-diagnosis.json');
file_put_contents($outPath, json_encode([
    'ok_sample' => $ok,
    'failed' => $results,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "Saved {$outPath}\n";
