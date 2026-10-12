param([ValidateRange(1, 65535)][int]$Port = 8000)
$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
$taskPhpCommand = Get-Command php -ErrorAction SilentlyContinue
$taskPortablePhp = Join-Path (Split-Path $PSScriptRoot -Parent) '.tools/php/php.exe'
$taskLaragonPhpRoot = Join-Path (Split-Path (Split-Path $PSScriptRoot -Parent) -Parent) 'bin/php'
$taskPhpCandidates = @()
if ($taskPhpCommand) { $taskPhpCandidates += $taskPhpCommand.Source }
if (Test-Path -LiteralPath $taskPortablePhp) { $taskPhpCandidates += $taskPortablePhp }
if (Test-Path -LiteralPath $taskLaragonPhpRoot) {
    $taskPhpCandidates += Get-ChildItem -LiteralPath $taskLaragonPhpRoot -Directory |
        Sort-Object Name -Descending |
        ForEach-Object { Join-Path $_.FullName 'php.exe' } |
        Where-Object { Test-Path -LiteralPath $_ }
}
$taskPhpPath = $null
foreach ($taskCandidate in ($taskPhpCandidates | Select-Object -Unique)) {
    & $taskCandidate -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' 2>$null
    if ($LASTEXITCODE -eq 0) { $taskPhpPath = $taskCandidate; break }
}
if (!$taskPhpPath) { throw 'Can PHP 8.3+. Bat Laragon hoac them PHP vao PATH. Xem README.md.' }
if ($taskPhpPath -eq $taskPortablePhp) {
    $env:PHPRC = Join-Path (Split-Path $PSScriptRoot -Parent) '.tools/php.ini'
}
Write-Host "PHP: $taskPhpPath"
if (!(Test-Path -LiteralPath 'vendor/autoload.php')) { throw 'Chay composer install truoc. Xem README.md.' }
if (!(Test-Path -LiteralPath '.env')) {
    Copy-Item -LiteralPath '.env.example' -Destination '.env'
    & $taskPhpPath artisan key:generate
    if ($LASTEXITCODE -ne 0) { throw 'Khong tao duoc APP_KEY.' }
}
& $taskPhpPath artisan serve --host=127.0.0.1 --port=$Port
exit $LASTEXITCODE
