$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Runtime.WindowsRuntime
$null = [Windows.Storage.StorageFile, Windows.Storage, ContentType = WindowsRuntime]
$null = [Windows.Data.Pdf.PdfDocument, Windows.Data.Pdf, ContentType = WindowsRuntime]
$null = [Windows.Storage.Streams.DataReader, Windows.Storage.Streams, ContentType = WindowsRuntime]
$null = [Windows.Storage.Streams.InMemoryRandomAccessStream, Windows.Storage.Streams, ContentType = WindowsRuntime]

function Await-WinRt($op, [type]$resultType) {
    $asTask = [System.WindowsRuntimeSystemExtensions].GetMethods() |
        Where-Object { $_.Name -eq 'AsTask' -and $_.IsGenericMethod -and $_.GetParameters().Count -eq 1 } |
        Select-Object -First 1
    $task = $asTask.MakeGenericMethod($resultType).Invoke($null, @($op))
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

$pdf = $args[0]
$outDir = $args[1]
$only = @()
if ($args.Count -gt 2) { $only = $args[2..($args.Count - 1)] | ForEach-Object { [int]$_ } }
New-Item -ItemType Directory -Force -Path $outDir | Out-Null
$file = Await-WinRt ([Windows.Storage.StorageFile]::GetFileFromPathAsync($pdf)) ([Windows.Storage.StorageFile])
$doc = Await-WinRt ([Windows.Data.Pdf.PdfDocument]::LoadFromFileAsync($file)) ([Windows.Data.Pdf.PdfDocument])
for ($i = 0; $i -lt $doc.PageCount; $i++) {
    if ($only.Count -gt 0 -and $only -notcontains $i) { continue }
    $page = $doc.GetPage([uint32]$i)
    $stream = New-Object Windows.Storage.Streams.InMemoryRandomAccessStream
    $opts = New-Object Windows.Data.Pdf.PdfPageRenderOptions
    $opts.DestinationWidth = [uint32]1700
    Await-Action ($page.RenderToStreamAsync($stream, $opts))
    $null = $stream.Seek(0)
    $reader = New-Object Windows.Storage.Streams.DataReader ($stream.GetInputStreamAt(0))
    $len = [uint32]$stream.Size
    [void](Await-WinRt ($reader.LoadAsync($len)) ([uint32]))
    $bytes = New-Object byte[] $len
    $reader.ReadBytes($bytes)
    $path = Join-Path $outDir ('page-{0:D3}.png' -f ($i + 1))
    [IO.File]::WriteAllBytes($path, $bytes)
    $reader.Dispose()
    $page.Dispose()
    $stream.Dispose()
    Write-Host $path
}
