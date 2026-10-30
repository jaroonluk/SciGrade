<?php

declare(strict_types=1);

use App\Services\RegistrarGradePdfParser;
use App\Services\RegistrarPdfParseException;
use Illuminate\Support\Facades\Log;
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
    // เทียบไฟล์ที่เคยผ่าน
    'SC001001-01.pdf', 'SC101011-01.pdf', 'SC152201-01.pdf', 'SC201101-01.pdf', 'SC752106-01.pdf',
];

/** @var RegistrarGradePdfParser $gradeParser */
$gradeParser = app(RegistrarGradePdfParser::class);
$pdfParser = new Parser;
ini_set('memory_limit', '1024M');

$ref = new ReflectionClass($gradeParser);
$normalize = $ref->getMethod('normalizeText');
$normalize->setAccessible(true);
$parseStudents = $ref->getMethod('parseStudents');
$parseStudents->setAccessible(true);
$parseSummary = $ref->getMethod('parseGradeSummary');
$parseSummary->setAccessible(true);
$parseTeacher = $ref->getMethod('parseTeacherAndSection');
$parseTeacher->setAccessible(true);
$sectionFromFilename = $ref->getMethod('sectionFromFilename');
$sectionFromFilename->setAccessible(true);
$isImageOnly = $ref->getMethod('isLikelyImageOnlyReg');
$isImageOnly->setAccessible(true);

$lastLog = null;
Log::listen(function ($event) use (&$lastLog): void {
    if (($event->message ?? null) === 'Registrar grade PDF parse failed') {
        $lastLog = $event->context ?? [];
    }
});

$results = [];

foreach ($names as $name) {
    $path = $folder.DIRECTORY_SEPARATOR.$name;
    $row = [
        'file' => $name,
        'exists' => is_file($path),
        'size' => is_file($path) ? filesize($path) : 0,
        'group' => in_array($name, ['SC001001-01.pdf', 'SC101011-01.pdf', 'SC152201-01.pdf', 'SC201101-01.pdf', 'SC752106-01.pdf'], true) ? 'passed_sample' : 'failed',
        'parser_ok' => false,
        'logged_reason' => null,
        'stage' => null,
        'details' => [],
        'preview' => '',
        'exception' => null,
    ];

    if (! $row['exists']) {
        $row['stage'] = 'file_missing';
        $results[] = $row;
        echo "{$name}: MISSING\n";
        continue;
    }

    $lastLog = null;

    // 1) ลอง parse จริง
    try {
        $parsed = $gradeParser->parse($path, $name, 1, 2567);
        $row['parser_ok'] = true;
        $row['stage'] = 'ok';
        $row['details'] = [
            'subject_code' => $parsed['subject_code'] ?? null,
            'section' => $parsed['grade_stds'][0]['sec'] ?? null,
            'term' => $parsed['term'] ?? null,
            'year' => $parsed['year'] ?? null,
            'students_i' => count($parsed['grade_i_students'] ?? []),
        ];
        $results[] = $row;
        echo "{$name}: OK subject={$row['details']['subject_code']} sec={$row['details']['section']}\n";
        continue;
    } catch (RegistrarPdfParseException $e) {
        $row['exception'] = $e->getMessage();
        $row['logged_reason'] = $lastLog['reason'] ?? null;
    } catch (Throwable $e) {
        $row['exception'] = $e->getMessage();
        $row['stage'] = 'unexpected';
        $results[] = $row;
        echo "{$name}: UNEXPECTED {$e->getMessage()}\n";
        continue;
    }

    // 2) ไล่ขั้นละเอียด
    try {
        $raw = $pdfParser->parseFile($path)->getText();
    } catch (Throwable $e) {
        $row['stage'] = 'pdf_extract';
        $row['details']['extract_error'] = $e->getMessage();
        $results[] = $row;
        echo "{$name}: pdf_extract\n";
        continue;
    }

    if (! is_string($raw) || trim($raw) === '') {
        $row['stage'] = 'empty_text';
        $results[] = $row;
        echo "{$name}: empty_text\n";
        continue;
    }

    $text = $normalize->invoke($gradeParser, $raw);
    $row['preview'] = mb_substr(preg_replace('/\s+/u', ' ', $text) ?? '', 0, 320);
    $row['details']['text_len'] = mb_strlen($text);

    if ($isImageOnly->invoke($gradeParser, $text)) {
        $row['stage'] = 'image_only_heuristic';
        $results[] = $row;
        echo "{$name}: image_only_heuristic (logged=".($row['logged_reason'] ?? '-').")\n";
        continue;
    }

    if (! str_contains($text, 'ใบส่งผลการศึกษา')) {
        $row['stage'] = 'missing_title';
        // หาคำใกล้เคียง
        if (preg_match('/ใบ[^\n]{0,40}/u', $text, $m)) {
            $row['details']['title_near'] = $m[0];
        }
        $results[] = $row;
        echo "{$name}: missing_title\n";
        continue;
    }

    $hasControl = preg_match('/control\s*code\s*:\s*(\d+)/iu', $text, $cm)
        || preg_match('/controlcode\s*:\s*(\d+)/iu', $text, $cm);
    if (! $hasControl) {
        $row['stage'] = 'missing_control_code';
        if (preg_match('/control[^\n]{0,40}/iu', $text, $m)) {
            $row['details']['control_near'] = $m[0];
        }
        $results[] = $row;
        echo "{$name}: missing_control_code\n";
        continue;
    }
    $row['details']['control_code'] = $cm[1] ?? null;

    if (! preg_match('/(?:^|\n)\s*([A-Z]{2}\d{5,8})\s*:\s*([^\n]+)/u', $text, $sm)) {
        $row['stage'] = 'missing_subject';
        // รูปแบบอื่น
        if (preg_match('/([A-Z]{2}\d{5,8})\s*[:=\-]\s*([^\n]+)/u', $text, $alt)) {
            $row['details']['alt_subject'] = $alt[1].' => '.trim($alt[2]);
        } elseif (preg_match('/(\d{6})\s*[:=\-]\s*([^\n]+)/u', $text, $num)) {
            $row['details']['numeric_subject'] = $num[1].' => '.trim($num[2]);
        } elseif (preg_match('/([A-Z]{2}\d{5,8})/u', $text, $loose)) {
            $row['details']['loose_code'] = $loose[1];
        }
        $results[] = $row;
        echo "{$name}: missing_subject logged=".($row['logged_reason'] ?? '-')."\n";
        continue;
    }
    $row['details']['subject'] = strtoupper(trim($sm[1])).' : '.trim($sm[2]);

    $sectionFallback = $sectionFromFilename->invoke($gradeParser, $name);
    [$teacher, $sectionFromPdf] = $parseTeacher->invoke($gradeParser, $text, $sectionFallback);
    $section = $sectionFromPdf ?? $sectionFallback;
    if ($section === null && preg_match('/กลุ่ม\s*(\d+)/u', $text, $gm)) {
        $section = (int) $gm[1];
    }
    $row['details']['teacher'] = $teacher;
    $row['details']['section'] = $section;
    $row['details']['section_from_pdf'] = $sectionFromPdf;
    $row['details']['section_from_filename'] = $sectionFallback;
    if ($section === null) {
        $row['stage'] = 'missing_section';
        $results[] = $row;
        echo "{$name}: missing_section\n";
        continue;
    }

    $students = $parseStudents->invoke($gradeParser, $text);
    $row['details']['student_count'] = count($students);
    $row['details']['student_sample'] = array_slice($students, 0, 3);
    if ($students === []) {
        $row['stage'] = 'missing_students';
        // หาเลขรหัสนักศึกษาแบบหลวม
        $row['details']['digit_ids'] = preg_match_all('/\b\d{8,12}\b/u', $text) ?: 0;
        $results[] = $row;
        echo "{$name}: missing_students (digit_ids={$row['details']['digit_ids']})\n";
        continue;
    }

    $summary = $parseSummary->invoke($gradeParser, $text);
    $sumCounts = array_sum($summary['counts'] ?? []);
    $row['details']['summary_count_total'] = $sumCounts;
    $row['details']['summary_ranges'] = array_keys($summary['ranges'] ?? []);
    $row['details']['summary_counts_nonzero'] = array_filter($summary['counts'] ?? [], fn ($v) => (int) $v > 0);
    if ($sumCounts === 0 && ($summary['ranges'] ?? []) === []) {
        $row['stage'] = 'missing_summary';
        if (preg_match('/สรุป[^\n]{0,80}/u', $text, $m)) {
            $row['details']['summary_near'] = $m[0];
        }
        $results[] = $row;
        echo "{$name}: missing_summary\n";
        continue;
    }

    $row['stage'] = 'failed_after_known_checks';
    $row['details']['logged_reason'] = $row['logged_reason'];
    $results[] = $row;
    echo "{$name}: failed_after_known_checks logged=".($row['logged_reason'] ?? '-')."\n";
}

$grouped = [];
foreach ($results as $r) {
    $grouped[$r['stage'] ?? 'unknown'][] = $r['file'];
}

$out = [
    'summary_by_stage' => array_map('count', $grouped),
    'files_by_stage' => $grouped,
    'results' => $results,
];
$pathOut = storage_path('app/test-reports/mk11-failed-precise.json');
file_put_contents($pathOut, json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "\nSaved {$pathOut}\n";
echo "Stages:\n";
foreach ($grouped as $stage => $files) {
    echo "- {$stage}: ".count($files)."\n";
}
