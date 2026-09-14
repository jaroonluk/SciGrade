<?php

declare(strict_types=1);

/**
 * Rasterize the SciGrade graduate-grade favicon to PNG/ICO without GD/Imagick.
 */

$public = dirname(__DIR__).DIRECTORY_SEPARATOR.'public';

function pngChunk(string $type, string $data): string
{
    return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data) & 0xFFFFFFFF);
}

function encodePng(int $w, int $h, string $rgba): string
{
    $raw = '';
    $row = $w * 4;
    for ($y = 0; $y < $h; $y++) {
        $raw .= "\x00".substr($rgba, $y * $row, $row);
    }

    return "\x89PNG\r\n\x1a\n"
        .pngChunk('IHDR', pack('NNCCCCC', $w, $h, 8, 6, 0, 0, 0))
        .pngChunk('IDAT', gzcompress($raw, 9))
        .pngChunk('IEND', '');
}

function icoFromPngs(array $images): string
{
    $n = count($images);
    $out = pack('vvv', 0, 1, $n);
    $offset = 6 + 16 * $n;
    $payload = '';
    foreach ($images as $img) {
        $w = $img['w'] >= 256 ? 0 : $img['w'];
        $h = $img['h'] >= 256 ? 0 : $img['h'];
        $out .= pack('CCCCvvVV', $w, $h, 0, 0, 1, 32, strlen($img['data']), $offset);
        $offset += strlen($img['data']);
        $payload .= $img['data'];
    }

    return $out.$payload;
}

function insideRoundRect(float $x, float $y, float $l, float $t, float $r, float $b, float $rad): bool
{
    if ($x < $l || $x > $r || $y < $t || $y > $b) {
        return false;
    }
    $cx = $x < $l + $rad ? $l + $rad : ($x > $r - $rad ? $r - $rad : $x);
    $cy = $y < $t + $rad ? $t + $rad : ($y > $b - $rad ? $b - $rad : $y);
    if ($cx === $x || $cy === $y) {
        return true;
    }
    $dx = $x - $cx;
    $dy = $y - $cy;

    return ($dx * $dx + $dy * $dy) <= $rad * $rad;
}

function insidePoly(float $x, float $y, array $pts): bool
{
    $n = count($pts);
    $inside = false;
    for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
        $xi = $pts[$i][0];
        $yi = $pts[$i][1];
        $xj = $pts[$j][0];
        $yj = $pts[$j][1];
        if ((($yi > $y) !== ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1e-9) + $xi)) {
            $inside = ! $inside;
        }
    }

    return $inside;
}

function distToSeg(float $px, float $py, float $x1, float $y1, float $x2, float $y2): float
{
    $vx = $x2 - $x1;
    $vy = $y2 - $y1;
    $len2 = $vx * $vx + $vy * $vy;
    $t = $len2 <= 1e-9 ? 0.0 : (($px - $x1) * $vx + ($py - $y1) * $vy) / $len2;
    $t = max(0.0, min(1.0, $t));
    $dx = $px - ($x1 + $t * $vx);
    $dy = $py - ($y1 + $t * $vy);

    return hypot($dx, $dy);
}

function hexRgb(string $hex): array
{
    $hex = ltrim($hex, '#');

    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

function renderIcon(int $size): string
{
    $ss = 4;
    $W = $size * $ss;
    $scale = $W / 32.0;
    $n = $W * $W;
    $r = array_fill(0, $n, 0);
    $g = array_fill(0, $n, 0);
    $b = array_fill(0, $n, 0);
    $a = array_fill(0, $n, 0);

    $set = static function (int $i, array $rgb) use (&$r, &$g, &$b, &$a): void {
        $r[$i] = $rgb[0];
        $g[$i] = $rgb[1];
        $b[$i] = $rgb[2];
        $a[$i] = 255;
    };

    $bg = hexRgb('8B4513');
    $paper = hexRgb('FFF8F0');
    $line = hexRgb('C4725C');
    $cap = hexRgb('F3D27A');
    $tassel = hexRgb('FFE9A8');

    $capPoly = [[16.2, 4.1], [29.0, 9.3], [16.2, 14.5], [3.4, 9.3]];

    for ($py = 0; $py < $W; $py++) {
        for ($px = 0; $px < $W; $px++) {
            $x = ($px + 0.5) / $scale;
            $y = ($py + 0.5) / $scale;
            $i = $py * $W + $px;

            if (insideRoundRect($x, $y, 0.15, 0.15, 31.85, 31.85, 8.0)) {
                $set($i, $bg);
            }
            if (insideRoundRect($x, $y, 6.4, 12.2, 19.8, 26.4, 1.6)) {
                $set($i, $paper);
            }
            foreach ([[16.6, 7.2], [19.6, 7.2], [22.6, 4.4]] as [$ly, $lw]) {
                if (distToSeg($x, $y, 9.4, $ly, 9.4 + $lw, $ly) <= 0.7) {
                    $set($i, $line);
                }
            }
            if (insidePoly($x, $y, $capPoly)) {
                $set($i, $cap);
            }
            if (distToSeg($x, $y, 26.4, 10.1, 26.4, 16.2) <= 0.8) {
                $set($i, $cap);
            }
            // tassel arc (right side of cap)
            $dx = $x - 16.2;
            $dy = $y - 10.1;
            $rad = hypot($dx, $dy);
            if ($x >= 16.2 && $y >= 10.1 && $rad >= 9.4 && $rad <= 11.0 && $y <= 18.9) {
                $set($i, $cap);
            }
            if (hypot($x - 26.4, $y - 17.1) <= 1.45) {
                $set($i, $tassel);
            }
        }
    }

    $out = '';
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            $sr = $sg = $sb = $sa = 0;
            for ($oy = 0; $oy < $ss; $oy++) {
                for ($ox = 0; $ox < $ss; $ox++) {
                    $i = ($y * $ss + $oy) * $W + ($x * $ss + $ox);
                    $sr += $r[$i];
                    $sg += $g[$i];
                    $sb += $b[$i];
                    $sa += $a[$i];
                }
            }
            $div = $ss * $ss;
            $out .= chr((int) round($sr / $div)).chr((int) round($sg / $div)).chr((int) round($sb / $div)).chr((int) round($sa / $div));
        }
    }

    return $out;
}

$png16 = encodePng(16, 16, renderIcon(16));
$png32 = encodePng(32, 32, renderIcon(32));
$png180 = encodePng(180, 180, renderIcon(180));

file_put_contents($public.DIRECTORY_SEPARATOR.'favicon-32x32.png', $png32);
file_put_contents($public.DIRECTORY_SEPARATOR.'apple-touch-icon.png', $png180);
file_put_contents($public.DIRECTORY_SEPARATOR.'favicon.ico', icoFromPngs([
    ['w' => 16, 'h' => 16, 'data' => $png16],
    ['w' => 32, 'h' => 32, 'data' => $png32],
]));

echo "Wrote favicon.ico, favicon-32x32.png, apple-touch-icon.png\n";
