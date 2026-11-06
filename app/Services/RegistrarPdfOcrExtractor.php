<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * อ่านข้อความจาก PDF ที่ไม่มี text layer ด้วยการเรนเดอร์หน้าเป็นภาพแล้วส่งเข้า Tesseract
 * ใช้เมื่อ smalot/pdfparser อ่านโครงสร้างใบ มข.11 ไม่ได้
 */
class RegistrarPdfOcrExtractor
{
    private const MAX_PAGES = 40;

    private const PARALLEL = 4;

    public function extract(string $pdfPath): ?string
    {
        if ($pdfPath === '' || ! is_readable($pdfPath)) {
            return null;
        }

        $tesseract = $this->findTesseract();
        if ($tesseract === null) {
            $this->log('tesseract binary not found');

            return null;
        }

        $tessdata = $this->findTessdataDir();
        $lang = $this->language($tessdata);

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $workDir = storage_path('app/ocr-tmp/'.uniqid('reg-', true));
        if (! @mkdir($workDir, 0777, true) && ! is_dir($workDir)) {
            $this->log('cannot create OCR work directory');

            return null;
        }

        try {
            $images = $this->renderPages($pdfPath, $workDir);
            if ($images === []) {
                $this->log('PDF page render produced no images');

                return null;
            }

            $text = $this->ocrImages($tesseract, $tessdata, $lang, $images);
            $text = trim($text);

            return $text === '' ? null : $text;
        } catch (\Throwable $e) {
            $this->log($e->getMessage());

            return null;
        } finally {
            $this->deleteTree($workDir);
        }
    }

    private function findTesseract(): ?string
    {
        $fromEnv = getenv('TESSERACT_PATH');
        if (is_string($fromEnv) && $fromEnv !== '' && is_file($fromEnv)) {
            return $fromEnv;
        }

        $candidates = [
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
        ];
        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return $this->findOnPath('tesseract');
    }

    private function findTessdataDir(): ?string
    {
        $dir = storage_path('app/ocr-tools/tessdata');
        if (is_file($dir.DIRECTORY_SEPARATOR.'eng.traineddata')) {
            return $dir;
        }

        return null;
    }

    private function language(?string $tessdata): string
    {
        if ($tessdata !== null && is_file($tessdata.DIRECTORY_SEPARATOR.'tha.traineddata')) {
            return 'tha+eng';
        }

        return 'eng';
    }

    /**
     * @return list<string>
     */
    private function renderPages(string $pdfPath, string $workDir): array
    {
        $imageDir = $workDir.DIRECTORY_SEPARATOR.'pages';
        if (! @mkdir($imageDir, 0777, true) && ! is_dir($imageDir)) {
            return [];
        }

        $pdftoppm = $this->findOnPath('pdftoppm');
        if ($pdftoppm !== null) {
            $prefix = $imageDir.DIRECTORY_SEPARATOR.'page';
            $cmd = escapeshellarg($pdftoppm)
                .' -png -r 170 -f 1 -l '.self::MAX_PAGES.' '
                .escapeshellarg($pdfPath).' '
                .escapeshellarg($prefix);
            $this->runCommand($cmd);
        } elseif (DIRECTORY_SEPARATOR === '\\') {
            $script = base_path('scripts/render-pdf-pages.ps1');
            if (! is_file($script)) {
                return [];
            }
            $cmd = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -File '
                .escapeshellarg($script).' '
                .escapeshellarg($pdfPath).' '
                .escapeshellarg($imageDir);
            $this->runCommand($cmd);
        }

        $images = glob($imageDir.DIRECTORY_SEPARATOR.'*.png') ?: [];
        sort($images, SORT_STRING);

        return array_slice($images, 0, self::MAX_PAGES);
    }

    /**
     * @param  list<string>  $images
     */
    private function ocrImages(string $tesseract, ?string $tessdata, string $lang, array $images): string
    {
        $chunks = array_fill(0, count($images), '');
        $pending = $images;
        /** @var array<int, array{process: resource, started: float, output: string}> $running */
        $running = [];

        while ($pending !== [] || $running !== []) {
            while (count($running) < self::PARALLEL && $pending !== []) {
                $index = array_key_first($pending);
                $image = $pending[$index];
                unset($pending[$index]);
                $running[$index] = $this->startTesseract($tesseract, $tessdata, $lang, $image);
            }

            foreach ($running as $index => $proc) {
                $status = proc_get_status($proc['process']);
                if ($status['running']) {
                    if ((microtime(true) - $proc['started']) > 90) {
                        proc_terminate($proc['process']);
                    } else {
                        continue;
                    }
                    $status = proc_get_status($proc['process']);
                    if ($status['running']) {
                        continue;
                    }
                }
                $txt = $proc['output'].'.txt';
                $chunks[$index] = is_file($txt) ? (string) file_get_contents($txt) : '';
                proc_close($proc['process']);
                unset($running[$index]);
            }

            if ($running !== []) {
                usleep(150000);
            }
        }

        return implode("\n", $chunks);
    }

    /**
     * @return array{process: resource, started: float, output: string}
     */
    private function startTesseract(string $tesseract, ?string $tessdata, string $lang, string $image): array
    {
        $outBase = $image.'.ocr';
        $cmd = escapeshellarg($tesseract).' '.escapeshellarg($image).' '.escapeshellarg($outBase)
            .' -l '.escapeshellarg($lang).' --psm 4';
        if ($tessdata !== null) {
            $cmd .= ' --tessdata-dir '.escapeshellarg($tessdata);
        }

        $nullDevice = DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null';
        $process = proc_open($cmd, [
            0 => ['file', $nullDevice, 'r'],
            1 => ['file', $nullDevice, 'w'],
            2 => ['file', $nullDevice, 'w'],
        ], $pipes);
        if (! is_resource($process)) {
            throw new \RuntimeException('Cannot start tesseract');
        }

        return [
            'process' => $process,
            'started' => microtime(true),
            'output' => $outBase,
        ];
    }

    private function runCommand(string $command): void
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptors, $pipes);
        if (! is_resource($process)) {
            return;
        }
        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
    }

    private function findOnPath(string $binary): ?string
    {
        $command = DIRECTORY_SEPARATOR === '\\'
            ? 'where.exe '.escapeshellarg($binary).' 2>NUL'
            : 'command -v '.escapeshellarg($binary).' 2>/dev/null';
        $output = [];
        $code = 1;
        @exec($command, $output, $code);
        if ($code !== 0) {
            return null;
        }
        $path = trim((string) ($output[0] ?? ''));

        return $path !== '' && is_file($path) ? $path : null;
    }

    private function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $this->deleteTree($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    private function log(string $message): void
    {
        try {
            Log::warning('Registrar PDF OCR skipped', ['detail' => $message]);
        } catch (\Throwable) {
            // CLI diagnostics may run without the logger
        }
    }
}
