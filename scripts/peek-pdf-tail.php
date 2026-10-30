<?php

require __DIR__.'/../vendor/autoload.php';

$folder = 'D:\\ข้อมูลระบบ\\SCiGRADE\\ทดสอบระบบเกรดใหม่-20261022T082348Z-1-001\\ทดสอบระบบเกรดใหม่\\ทดสอบ เทอม 1-2567';
$files = ['SC101016-01.pdf', 'SC201101-01.pdf', 'SC152201-02.pdf', 'SC113601-01.pdf', '326493-01.pdf'];
$p = new Smalot\PdfParser\Parser;

foreach ($files as $n) {
    $t = $p->parseFile($folder.DIRECTORY_SEPARATOR.$n)->getText();
    $t = str_replace(["\r\n", "\r"], "\n", $t);
    echo "==== {$n} ====\n";
    echo "has %รวมMANUALเกรด: ".(str_contains($t, '%รวมMANUALเกรด') ? 'yes' : 'no')."\n";
    echo "has รวมMANUAL: ".(str_contains($t, 'รวมMANUAL') ? 'yes' : 'no')."\n";
    echo "has MANUAL: ".(stripos($t, 'MANUAL') !== false ? 'yes' : 'no')."\n";
    echo "has <<->>: ".(str_contains($t, '<<->>') ? 'yes' : 'no')."\n";
    echo "TAIL:\n".mb_substr($t, -700)."\n\n";
}
