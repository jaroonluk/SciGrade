<?php
$json = file_get_contents('https://api.github.com/repos/UB-Mannheim/tesseract/releases/latest', false, stream_context_create([
    'http' => ['header' => "User-Agent: SciGrade\r\n"],
]));
$j = json_decode((string) $json, true);
echo ($j['tag_name'] ?? 'none')."\n";
foreach ($j['assets'] ?? [] as $a) {
    echo $a['name'].' '.$a['browser_download_url']."\n";
}
