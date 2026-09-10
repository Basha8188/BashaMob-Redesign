param([switch]$Watch, [int]$OwnerPid, [string]$RunPath, [switch]$SmokeTest)
$ErrorActionPreference = 'Stop'
$demoRoot = [IO.Path]::GetFullPath((Join-Path ([IO.Path]::GetTempPath()) 'BashaMob-Demo'))
function Remove-Demo([string]$Target) {
    $resolved = [IO.Path]::GetFullPath($Target)
    if ([IO.Path]::GetDirectoryName($resolved) -ne $demoRoot -or [IO.Path]::GetFileName($resolved) -notmatch '^[a-f0-9]{32}$') { throw 'Unsafe cleanup path.' }
    if (Test-Path -LiteralPath $resolved) { Remove-Item -LiteralPath $resolved -Recurse -Force }
}
if ($Watch) {
    $owner = Get-Process -Id $OwnerPid -ErrorAction SilentlyContinue
    if ($owner) { $owner.WaitForExit() }
    # Match the unique document root, never other PHP servers.
    Get-CimInstance Win32_Process -Filter "Name = 'php.exe' OR Name = 'Robocopy.exe'" | Where-Object { $_.CommandLine -and $_.CommandLine.Contains($RunPath) } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }
    for ($attempt = 0; $attempt -lt 10; $attempt++) {
        try { Remove-Demo $RunPath; exit 0 } catch { Start-Sleep -Milliseconds 500 }
    }
    exit 1
}
$source = Split-Path $PSScriptRoot -Parent
$php = (Get-Command php.exe -ErrorAction SilentlyContinue).Source
if (!$php -and (Test-Path 'C:\xampp\php\php.exe')) { $php = 'C:\xampp\php\php.exe' }
if (!$php) { throw 'PHP 8.2+ is required. Install XAMPP or add PHP to PATH.' }
if (!(Test-Path "$source\vendor\autoload.php") -or !(Test-Path "$source\public\build\manifest.json")) { throw 'Run composer install and npm run build first.' }
$modules = & $php -m
if ($LASTEXITCODE -ne 0 -or $modules -notcontains 'pdo_sqlite') { throw 'Enable pdo_sqlite in php.ini.' }
$run = Join-Path $demoRoot ([guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $run -Force | Out-Null
$server = $null
try {
    Start-Process powershell.exe -WindowStyle Hidden -ArgumentList @('-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', "`"$PSCommandPath`"", '-Watch', '-OwnerPid', $PID, '-RunPath', "`"$run`"") | Out-Null
    Write-Host 'Preparing an isolated demo. No production data is copied...'
    foreach ($folder in @('app','bootstrap','config','database','resources','routes','vendor','public')) {
        & robocopy.exe "$source\$folder" "$run\$folder" /E /XJ /R:1 /W:1 /NFL /NDL /NJH /NJS /NP /XD "$source\bootstrap\cache" "$source\public\storage" "$source\public\uploads" /XF '*.sqlite' '*.sqlite-*' '*.db' '*.sql' '.env*' 'hot' | Out-Null
        if ($LASTEXITCODE -ge 8) { throw "Could not copy $folder." }
    }
    Copy-Item -LiteralPath "$source\artisan","$source\composer.json" -Destination $run
    foreach ($folder in @('bootstrap/cache','storage/app/public','storage/app/private','storage/framework/cache/data','storage/framework/sessions','storage/framework/views','storage/logs')) { New-Item -ItemType Directory -Path (Join-Path $run $folder) -Force | Out-Null }
    $listener = [Net.Sockets.TcpListener]::new([Net.IPAddress]::Loopback, 0)
    $listener.Start(); $port = $listener.LocalEndpoint.Port; $listener.Stop()
    $url = "http://127.0.0.1:$port"
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
    $server = Start-Process $php -WindowStyle Hidden -WorkingDirectory "$run\public" -ArgumentList @('-S', "127.0.0.1:$port", '-t', "`"$run\public`"", "`"$router`"") -RedirectStandardOutput "$run\storage\logs\server.log" -RedirectStandardError "$run\storage\logs\server-error.log" -PassThru
    $ready = $false
    for ($i = 0; $i -lt 40; $i++) {
        if ($server.HasExited) { throw 'Demo server exited. Check PHP configuration.' }
        try { $response = Invoke-WebRequest "$url/staff/login" -UseBasicParsing -TimeoutSec 2; if ($response.StatusCode -eq 200) { $ready = $true; break } } catch { Start-Sleep -Milliseconds 250 }
    }
    if (!$ready) { throw 'Demo did not become ready.' }
    if ($SmokeTest) { Write-Host 'PASS: isolated migrations, demo seed, storage and HTTP login.' } else {
        Add-Type -AssemblyName System.Windows.Forms
        $form = New-Object Windows.Forms.Form
        $form.Text = 'BashaMob Demo - Close to erase demo data'
        $form.ClientSize = New-Object Drawing.Size(560,200)
        $form.StartPosition = 'CenterScreen'
        $label = New-Object Windows.Forms.Label
        $label.SetBounds(20,20,520,110)
        $label.Text = "Demo is running: $url`r`n`r`nEmail: demo@bashamob.local`r`nPassword: Demo!2026Local`r`n`r`nClose THIS window to stop and erase all demo data."
        $form.Controls.Add($label)
        $button = New-Object Windows.Forms.Button
        $button.Text = 'Open demo'; $button.SetBounds(20,140,150,35)
        $button.Add_Click({ Start-Process "$url/staff/login" })
        $form.Controls.Add($button)
        Start-Process "$url/staff/login"
        [void]$form.ShowDialog()
    }
} finally {
    if ($server -and !$server.HasExited) { Stop-Process -Id $server.Id -Force; $server.WaitForExit() }
    Remove-Demo $run
    Write-Host 'Demo data deleted.'
}
