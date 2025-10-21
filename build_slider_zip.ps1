$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$exportPath = Join-Path $scriptDir 'slider_step_by_step_export.txt'
$zipPath = Join-Path $scriptDir 'slider_step_by_step.zip'

if (-not (Test-Path $exportPath)) {
    throw "Export file 'slider_step_by_step_export.txt' was not found next to this script."
}

Add-Type -AssemblyName System.IO.Compression.FileSystem

if (Test-Path $zipPath) {
    Remove-Item $zipPath
}

$zipArchive = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
        $zipArchive,
        $exportPath,
        'slider_export.txt',
        [System.IO.Compression.CompressionLevel]::Optimal
    ) | Out-Null
}
finally {
    $zipArchive.Dispose()
}

$zipInfo = Get-Item $zipPath
Write-Host "Wrote $($zipInfo.Name) ($($zipInfo.Length) bytes)"
