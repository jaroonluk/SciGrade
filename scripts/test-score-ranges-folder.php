<?php

declare(strict_types=1);

use App\Services\RegistrarGradePdfParser;
use App\Services\RegistrarPdfParseException;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$folder = 'D:\\ข้อมูลระบบ\\SCiGRADE\\ทดสอบระบบเกรดใหม่-20261022T082348Z-1-001\\ทดสอบระบบเกรดใหม่\\ทดสอบ เทอม 1-2567';
$term = 1;
$year = 2567;

/** @var RegistrarGradePdfParser $parser */
$parser = app(RegistrarGradePdfParser::class);

$scoreKeys = ['score_a', 'score_bb', 'score_b', 'score_cc', 'score_c', 'score_dd', 'score_d', 'score_f'];

$files = glob($folder.DIRECTORY_SEPARATOR.'*.pdf') ?: [];
sort($files, SORT_NATURAL | SORT_FLAG_CASE);

$results = [];
$withRanges = 0;
$withoutRanges = 0;
$parseFail = 0;

foreach ($files as $path) {
    $name = basename($path);
    $row = [
        'file' => $name,
        'ok' => false,
        'subject_code' => null,
        'section' => null,
        'has_manual_ranges' => false,
        'range_count' => 0,
        'ranges' => [],
        'error' => null,
    ];

    try {
        $parsed = $parser->parse($path, $name, $term, $year);
        $row['ok'] = true;
        $row['subject_code'] = $parsed['subject_code'] ?? null;
        $row['section'] = $parsed['grade_stds'][0]['sec'] ?? null;

        $ranges = [];
        foreach ($scoreKeys as $key) {
            $val = $parsed[$key] ?? null;
            if (is_string($val) && trim($val) !== '') {
                $ranges[$key] = $val;
            }
        }
        $row['ranges'] = $ranges;
        $row['range_count'] = count($ranges);
        $row['has_manual_ranges'] = $parser->hasManualScoreRanges($parsed);

        if ($row['has_manual_ranges']) {
            $withRanges++;
        } else {
            $withoutRanges++;
        }
        echo ($row['has_manual_ranges'] ? 'RANGES' : 'NO_RANGE')." | {$name} | {$row['subject_code']} sec={$row['section']} n={$row['range_count']}\n";
    } catch (RegistrarPdfParseException $e) {
        $parseFail++;
        $row['error'] = $e->getMessage();
        echo "FAIL | {$name} | ".$e->getMessage()."\n";
    } catch (Throwable $e) {
        $parseFail++;
        $row['error'] = $e->getMessage();
        echo "ERR | {$name} | ".$e->getMessage()."\n";
    }

    $results[] = $row;
}

$summary = [
    'tested_at' => now()->toDateTimeString(),
    'folder' => $folder,
    'term' => $term,
    'year' => $year,
    'total' => count($files),
    'parse_ok' => count($files) - $parseFail,
    'parse_fail' => $parseFail,
    'with_manual_ranges' => $withRanges,
    'without_manual_ranges' => $withoutRanges,
    'results' => $results,
];

$out = __DIR__.'/../storage/app/test-reports/mk11-score-ranges-term1-2567.json';
file_put_contents($out, json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "----\n";
echo "TOTAL={$summary['total']} OK={$summary['parse_ok']} FAIL={$parseFail} WITH_RANGES={$withRanges} NO_RANGE={$withoutRanges}\n";
echo "JSON={$out}\n";
