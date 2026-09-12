param([switch]$Watch, [int]$OwnerPid, [string]$RunPath, [switch]$SmokeTest, [switch]$DesktopSmokeTest, [switch]$Browser, [string]$BindAddress)
$ErrorActionPreference = 'Stop'
$demoRoot = [IO.Path]::GetFullPath((Join-Path ([IO.Path]::GetTempPath()) 'BashaMob-Demo'))
function Remove-Demo([string]$Target) {
    $resolved = [IO.Path]::GetFullPath($Target)
    if ([IO.Path]::GetDirectoryName($resolved) -ne $demoRoot -or [IO.Path]::GetFileName($resolved) -notmatch '^[a-f0-9]{32}$') { throw 'Unsafe cleanup path.' }
    if (Test-Path -LiteralPath $resolved) { Remove-Item -LiteralPath $resolved -Recurse -Force }
}
if ($Watch) {
    while (Get-Process -Id $OwnerPid -ErrorAction SilentlyContinue) { Start-Sleep -Seconds 1 }
    # Match the unique document root, never other PHP servers.
    Get-CimInstance Win32_Process -Filter "Name = 'php.exe' OR Name = 'Robocopy.exe' OR Name = 'msedge.exe'" | Where-Object { $_.CommandLine -and $_.CommandLine.Contains($RunPath) } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }
    for ($attempt = 0; $attempt -lt 10; $attempt++) {
        try { Remove-Demo $RunPath; exit 0 } catch { Start-Sleep -Milliseconds 500 }
    }
    exit 1
}
$source = Split-Path $PSScriptRoot -Parent
$php = (Get-Command php.exe -ErrorAction SilentlyContinue).Source
if (!$php -and (Test-Path 'C:\xampp\php\php.exe')) { $php = 'C:\xampp\php\php.exe' }
$edge = @("${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe", "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe", "$env:LOCALAPPDATA\Microsoft\Edge\Application\msedge.exe") | Where-Object { Test-Path -LiteralPath $_ } | Select-Object -First 1
if (!$Browser -and (!$SmokeTest -or $DesktopSmokeTest) -and !$edge) { throw 'Microsoft Edge is required for the desktop window.' }
if (!$php) { throw 'PHP 8.2+ is required. Install XAMPP or add PHP to PATH.' }
if (!(Test-Path "$source\vendor\autoload.php") -or !(Test-Path "$source\public\build\manifest.json")) { throw 'Run composer install and npm run build first.' }
$modules = & $php -m
if ($LASTEXITCODE -ne 0 -or $modules -notcontains 'pdo_sqlite') { throw 'Enable pdo_sqlite in php.ini.' }
$listenAddress = '127.0.0.1'
if ($Browser) {
    if ($DesktopSmokeTest) { throw 'Use -SmokeTest with -Browser, not -DesktopSmokeTest.' }
    $localAddresses = @(Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.AddressState -eq 'Preferred' } | Select-Object -ExpandProperty IPAddress)
    if (!$BindAddress) {
        $BindAddress = Get-NetIPConfiguration | Where-Object { $_.IPv4DefaultGateway -and $_.IPv4Address } | ForEach-Object { $_.IPv4Address.IPAddress } | Where-Object { $_ -match '^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[01])\.)' } | Select-Object -First 1
    }
    if (!$BindAddress -or $localAddresses -notcontains $BindAddress -or $BindAddress -notmatch '^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[01])\.)') {
        throw 'Connect the host to a private Wi-Fi/Ethernet network, or supply -BindAddress with its local private IPv4 address.'
    }
    $listenAddress = $BindAddress
}
$run = Join-Path $demoRoot ([guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $run -Force | Out-Null
$desktopProfile = Join-Path $run 'desktop-profile'
function Stop-Desktop {
    Get-CimInstance Win32_Process -Filter "Name = 'msedge.exe'" | Where-Object { $_.CommandLine -and $_.CommandLine.Contains($desktopProfile) } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }
}
$server = $null
try {
    Start-Process powershell.exe -WindowStyle Hidden -ArgumentList @('-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', "`"$PSCommandPath`"", '-Watch', '-OwnerPid', $PID, '-RunPath', "`"$run`"") | Out-Null
    Write-Host 'Preparing an isolated demo. No production data is copied...'
    foreach ($folder in @('app','bootstrap','config','database','resources','routes','vendor','public')) {
        Write-Host "Preparing $folder..."
        & robocopy.exe "$source\$folder" "$run\$folder" /E /XJ /MT:8 /R:1 /W:1 /NFL /NDL /NJH /NJS /NP /XD "$source\bootstrap\cache" "$source\public\storage" "$source\public\uploads" /XF '*.sqlite' '*.sqlite-*' '*.db' '*.sql' '.env*' 'hot' | Out-Null
        if ($LASTEXITCODE -ge 8) { throw "Could not copy $folder." }
    }
    Copy-Item -LiteralPath "$source\artisan","$source\composer.json" -Destination $run
    foreach ($folder in @('bootstrap/cache','storage/app/public','storage/app/private','storage/framework/cache/data','storage/framework/sessions','storage/framework/views','storage/logs')) { New-Item -ItemType Directory -Path (Join-Path $run $folder) -Force | Out-Null }
    $listener = [Net.Sockets.TcpListener]::new([Net.IPAddress]::Parse($listenAddress), 0)
    $listener.Start(); $port = $listener.LocalEndpoint.Port; $listener.Stop()
    $url = "http://${listenAddress}:$port"
    $bytes = New-Object byte[] 32
    $rng = [Security.Cryptography.RandomNumberGenerator]::Create(); $rng.GetBytes($bytes); $rng.Dispose()
    $key = [Convert]::ToBase64String($bytes)
    $environment = @"
APP_NAME="BashaMob Demo"
APP_ENV=demo
APP_KEY=base64:$key
APP_DEBUG=false
APP_URL=$url
APP_LOCALE=ar
DB_CONNECTION=sqlite
DB_DATABASE="$($run.Replace('\','/'))/database/demo.sqlite"
DB_URL=
SESSION_DRIVER=file
SESSION_COOKIE=bashamob_demo_$port
CACHE_STORE=file
QUEUE_CONNECTION=sync
MAIL_MAILER=log
LOG_CHANNEL=single
OWNER_EMAIL=demo@bashamob.local
OWNER_PASSWORD=Demo!2026Local
"@
    # Clear inherited app settings so the demo cannot use live database/API credentials.
    Get-ChildItem Env: | Where-Object { $_.Name -match '^(APP_|DB_|MAIL_|QUEUE_|CACHE_|SESSION_|REDIS_|AWS_|WHATSAPP_|OPENAI_|OWNER_|FILESYSTEM_)' } | ForEach-Object { [Environment]::SetEnvironmentVariable($_.Name, $null, 'Process') }
    [IO.File]::WriteAllText("$run\.env", $environment, [Text.UTF8Encoding]::new($false))
    New-Item -ItemType File -Path "$run\database\demo.sqlite" | Out-Null
    Push-Location $run
    try {
        & $php "$run\artisan" migrate --force --no-interaction
        if ($LASTEXITCODE -ne 0) { throw 'Demo migrations failed.' }
        & $php "$run\artisan" db:seed --class=DemoSeeder --force --no-interaction
        if ($LASTEXITCODE -ne 0) { throw 'Demo seed failed.' }
        & $php "$run\artisan" storage:link --no-interaction
        if ($LASTEXITCODE -ne 0) { throw 'Demo storage setup failed.' }
    } finally { Pop-Location }
    $router = "$run\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php"
    $server = Start-Process $php -WindowStyle Hidden -WorkingDirectory "$run\public" -ArgumentList @('-S', "${listenAddress}:$port", '-t', "`"$run\public`"", "`"$router`"") -RedirectStandardOutput "$run\storage\logs\server.log" -RedirectStandardError "$run\storage\logs\server-error.log" -PassThru
    $ready = $false
    for ($i = 0; $i -lt 40; $i++) {
        if ($server.HasExited) { throw 'Demo server exited. Check PHP configuration.' }
        try { $response = Invoke-WebRequest "$url/staff/login" -UseBasicParsing -TimeoutSec 2; if ($response.StatusCode -eq 200) { $ready = $true; break } } catch { Start-Sleep -Milliseconds 250 }
    }
    if (!$ready) { throw 'Demo did not become ready.' }
    if ($SmokeTest) {
        $homeResponse = Invoke-WebRequest "$url/" -UseBasicParsing -TimeoutSec 10
        if ($homeResponse.StatusCode -ne 200 -or $homeResponse.Content -notmatch 'data-liquid-indicator') { throw 'The updated navigation did not render.' }
        $backgroundResponse = Invoke-WebRequest "$url/images/theme/main-bg.png" -UseBasicParsing -TimeoutSec 10
        if ($backgroundResponse.StatusCode -ne 200) { throw 'The shared background is unavailable.' }
        Write-Host 'PASS: updated navigation and theme background over HTTP.'
    }
    $credentials = "BashaMob Demo - local trial account`r`nUsername / Email: demo@bashamob.local`r`nPassword: Demo!2026Local`r`n`r`nRun demo.cmd to open the application.`r`nClosing the application deletes trial data.`r`nThese credentials are for the demo only, not customer production installations.`r`n"
    [IO.File]::WriteAllText("$source\DEMO-LOGIN.txt", $credentials, [Text.UTF8Encoding]::new($true))
    if ($SmokeTest -and !$DesktopSmokeTest) { Write-Host 'PASS: isolated migrations, demo seed, storage and HTTP login.' } elseif ($Browser) {
        [IO.File]::WriteAllText("$source\DEMO-URL.txt", "$url/staff/login", [Text.UTF8Encoding]::new($false))
        Write-Host ""
        Write-Host "Open $url/staff/login on Windows, Mac, iPhone or Android on the same private network."
        Write-Host 'Keep this window open. Close it or press Ctrl+C to stop the demo and delete trial data.'
        Write-Host 'If Windows asks about PHP network access, use the private-network option.'
        while ($true) {
            if ($server.HasExited) { throw 'The demo server stopped.' }
            Start-Sleep -Seconds 1
        }
    } else {
        # A dedicated profile isolates the application from existing browser windows.
        $desktop = Start-Process -FilePath $edge -ArgumentList @("--user-data-dir=`"$desktopProfile`"", "--app=$url/staff/login", '--no-first-run', '--no-default-browser-check', '--disable-background-mode') -PassThru
        Write-Host 'BashaMob is open. Close the application window to erase trial data.'
        $window = $null
        for ($attempt = 0; $attempt -lt 60; $attempt++) {
            $window = Get-CimInstance Win32_Process -Filter "Name = 'msedge.exe'" | Where-Object { $_.CommandLine -and $_.CommandLine.Contains($desktopProfile) } | ForEach-Object { Get-Process -Id $_.ProcessId -ErrorAction SilentlyContinue } | Where-Object { $_.MainWindowHandle -ne 0 } | Select-Object -First 1
            if ($window) { break }
            Start-Sleep -Milliseconds 500
        }
        if (!$window) { throw 'Desktop window did not open.' }
        if ($DesktopSmokeTest) {
            Write-Host 'PASS: desktop application window opened.'
            Stop-Desktop
        } else {
            while (!$window.HasExited) {
                $window.Refresh()
                if ($window.MainWindowHandle -eq 0) { break }
                Start-Sleep -Milliseconds 500
            }
        }
    }
} finally {
    Stop-Desktop
    if ($server -and !$server.HasExited) { Stop-Process -Id $server.Id -Force; $server.WaitForExit() }
    for ($attempt = 0; $attempt -lt 10; $attempt++) {
        try { Remove-Demo $run; break } catch { if ($attempt -eq 9) { throw }; Start-Sleep -Milliseconds 500 }
    }
    Write-Host 'Demo data deleted.'
}
