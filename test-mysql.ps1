param(
    [string]$HostName = '127.0.0.1',
    [int]$Port = 3306,
    [string]$UserName = 'root'
)
$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
$taskPhpCommand = Get-Command php -ErrorAction SilentlyContinue
$taskPortablePhp = Join-Path (Split-Path $PSScriptRoot -Parent) '.tools/php/php.exe'
if ($taskPhpCommand) { $taskPhpPath = $taskPhpCommand.Source }
elseif (Test-Path -LiteralPath $taskPortablePhp) { $taskPhpPath = $taskPortablePhp }
else { throw 'Can PHP 8.3+. Xem README.md.' }
$taskNames = @('APP_ENV', 'DB_CONNECTION', 'DB_DATABASE', 'DB_HOST', 'DB_PORT', 'DB_USERNAME', 'DB_URL')
$taskPrevious = @{}
foreach ($taskName in $taskNames) { $taskPrevious[$taskName] = [Environment]::GetEnvironmentVariable($taskName, 'Process') }
try {
    $env:APP_ENV = 'testing'
    $env:DB_CONNECTION = 'mysql'
    $env:DB_DATABASE = 'threads_city_blog_test'
    $env:DB_HOST = $HostName
    $env:DB_PORT = [string]$Port
    $env:DB_USERNAME = $UserName
    $env:DB_URL = ''
    & $taskPhpPath artisan config:clear
    if ($LASTEXITCODE -ne 0) { throw 'Khong clear duoc config cache.' }
    & $taskPhpPath vendor/bin/phpunit --no-progress
    $taskTestExit = $LASTEXITCODE
} finally {
    foreach ($taskName in $taskNames) {
        [Environment]::SetEnvironmentVariable($taskName, $taskPrevious[$taskName], 'Process')
    }
}
exit $taskTestExit
