<?php
$j = json_decode(file_get_contents(__DIR__.'/../storage/app/test-reports/mk11-upload-test-term1-2567.json'), true);
echo "PASS={$j['passed']} FAIL={$j['failed']} TOTAL={$j['total']}\n";
foreach ($j['by_reason'] as $k => $c) {
    echo "$k => $c\n";
}
echo "----\n";
foreach ($j['failed_files'] as $r) {
    $reason = substr(str_replace(["\r", "\n"], ' ', (string) $r['reason']), 0, 160);
    echo "{$r['file']} | {$r['error_code']} | {$reason}\n";
}
