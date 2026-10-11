param([int]$Port = 8000)
$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
$taskPhpCommand = Get-Command php -ErrorAction SilentlyContinue
$taskPortablePhp = Join-Path (Split-Path $PSScriptRoot -Parent) '.tools/php/php.exe'
if ($taskPhpCommand) {
    $taskPhpPath = $taskPhpCommand.Source
} elseif (Test-Path -LiteralPath $taskPortablePhp) {
    $taskPhpPath = $taskPortablePhp
    $env:PHPRC = Join-Path (Split-Path $PSScriptRoot -Parent) '.tools/php.ini'
} else {
    throw 'Can PHP 8.3+ va Composer. Xem README.md de cai dat.'
}
if (!(Test-Path -LiteralPath 'vendor/autoload.php')) { throw 'Chay composer install truoc. Xem README.md.' }
if (!(Test-Path -LiteralPath '.env')) {
    Copy-Item -LiteralPath '.env.example' -Destination '.env'
    & $taskPhpPath artisan key:generate
    if ($LASTEXITCODE -ne 0) { throw 'Khong tao duoc APP_KEY.' }
}
& $taskPhpPath artisan serve --host=127.0.0.1 --port=$Port
exit $LASTEXITCODE
