$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Runtime.WindowsRuntime

$null = [Windows.Storage.StorageFile, Windows.Storage, ContentType = WindowsRuntime]
$null = [Windows.Data.Pdf.PdfDocument, Windows.Data.Pdf, ContentType = WindowsRuntime]
$null = [Windows.Media.Ocr.OcrEngine, Windows.Media.Ocr, ContentType = WindowsRuntime]
$null = [Windows.Graphics.Imaging.BitmapDecoder, Windows.Graphics.Imaging, ContentType = WindowsRuntime]
$null = [Windows.Storage.Streams.InMemoryRandomAccessStream, Windows.Storage.Streams, ContentType = WindowsRuntime]

function Await-WinRt($op, [type]$resultType) {
    $asTask = [System.WindowsRuntimeSystemExtensions].GetMethods() |
        Where-Object { $_.Name -eq 'AsTask' -and $_.IsGenericMethod -and $_.GetParameters().Count -eq 1 } |
        Select-Object -First 1
    $generic = $asTask.MakeGenericMethod($resultType)
    $task = $generic.Invoke($null, @($op))
    $task.Wait() | Out-Null
    if ($task.IsFaulted) { throw $task.Exception }
    return $task.Result
}

function Await-Action($op) {
    $asTask = [System.WindowsRuntimeSystemExtensions].GetMethods() |
        Where-Object { $_.Name -eq 'AsTask' -and -not $_.IsGenericMethod -and $_.GetParameters().Count -eq 1 } |
        Select-Object -First 1
    $task = $asTask.Invoke($null, @($op))
    $task.Wait() | Out-Null
    if ($task.IsFaulted) { throw $task.Exception }
}

$langs = [Windows.Media.Ocr.OcrEngine]::AvailableRecognizerLanguages
Write-Host 'OCR LANGS:'
$langs | ForEach-Object { Write-Host ("  " + $_.LanguageTag + " " + $_.DisplayName) }

$pdf = 'D:\ข้อมูลระบบ\SCiGRADE\ทดสอบระบบเกรดใหม่-20261022T082348Z-1-001\ทดสอบระบบเกรดใหม่\ทดสอบ เทอม 1-2568\SC002004-01.pdf'
if (-not (Test-Path -LiteralPath $pdf)) { Write-Host "PDF missing"; exit 1 }
$file = Await-WinRt ([Windows.Storage.StorageFile]::GetFileFromPathAsync($pdf)) ([Windows.Storage.StorageFile])
$doc = Await-WinRt ([Windows.Data.Pdf.PdfDocument]::LoadFromFileAsync($file)) ([Windows.Data.Pdf.PdfDocument])
Write-Host ("PAGES=" + $doc.PageCount)
$indexes = @(0, [Math]::Max(0, $doc.PageCount - 1))
foreach ($idx in ($indexes | Select-Object -Unique)) {
$page = $doc.GetPage([uint32]$idx)
$stream = New-Object Windows.Storage.Streams.InMemoryRandomAccessStream
$opts = New-Object Windows.Data.Pdf.PdfPageRenderOptions
$opts.DestinationWidth = [uint32]1600
Await-Action ($page.RenderToStreamAsync($stream))
$decoder = Await-WinRt ([Windows.Graphics.Imaging.BitmapDecoder]::CreateAsync($stream)) ([Windows.Graphics.Imaging.BitmapDecoder])
$bitmap = Await-WinRt ($decoder.GetSoftwareBitmapAsync()) ([Windows.Graphics.Imaging.SoftwareBitmap])
$engine = [Windows.Media.Ocr.OcrEngine]::TryCreateFromUserProfileLanguages()
if (-not $engine) { Write-Host 'NO ENGINE'; exit 1 }
$result = Await-WinRt ($engine.RecognizeAsync($bitmap)) ([Windows.Media.Ocr.OcrResult])
Write-Host ("--- PAGE " + $idx + " ---")
$text = $result.Text
if ($text.Length -gt 900) { $text = $text.Substring(0, 900) }
Write-Host $text
$page.Dispose()
$stream.Dispose()
}
