$ErrorActionPreference = 'Stop'
$projectRoot = $PSScriptRoot
$phpPath = 'C:\laragon\bin\php\php-8.5.10-Win32-vs17-x64\php.exe'
if (!(Test-Path -LiteralPath $phpPath)) {
    throw 'The configured Laragon PHP installation was not found. Update $phpPath in this script.'
}
if (!(Test-Path -LiteralPath (Join-Path $projectRoot 'vendor/autoload.php'))) {
    throw 'Install Composer dependencies before starting CBIS.'
}
$runningWorkers = Get-CimInstance Win32_Process -Filter "Name = 'php.exe'"
foreach ($worker in @('queue:work', 'schedule:work')) {
    $alreadyRunning = $runningWorkers | Where-Object {
        $_.CommandLine -like "*$projectRoot*" -and $_.CommandLine -like "*$worker*"
    }
    if (!$alreadyRunning) {
        $arguments = @(('"{0}"' -f (Join-Path $projectRoot 'artisan')), $worker)
        if ($worker -eq 'queue:work') { $arguments += @('--tries=3', '--timeout=90') }
        $logName = $worker.Replace(':', '-')
        Start-Process -FilePath $phpPath -ArgumentList $arguments -WorkingDirectory $projectRoot -WindowStyle Hidden -RedirectStandardOutput (Join-Path $projectRoot "storage/logs/$logName.log") -RedirectStandardError (Join-Path $projectRoot "storage/logs/$logName-error.log")
    }
}
Write-Output 'CBIS background workers are running. Keep Apache and MySQL started in Laragon.'
Write-Output 'Open http://cbis-thesis.test in Chrome.'
