<?php
$j = json_decode(file_get_contents(__DIR__.'/../storage/app/test-reports/mk11-failed-precise.json'), true);
foreach (['missing_subject','missing_summary','missing_students','empty_text'] as $stage) {
  echo "==== $stage ====\n";
  foreach ($j['results'] as $r) {
    if (($r['stage'] ?? '') !== $stage) continue;
    echo $r['file']."\n";
    echo "  details=".json_encode($r['details'], JSON_UNESCAPED_UNICODE)."\n";
    echo "  preview=".mb_substr($r['preview'] ?? '', 0, 220)."\n\n";
  }
}
