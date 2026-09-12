@echo off
setlocal
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -STA -File "%~dp0scripts\demo.ps1" %*
set "demoExit=%errorlevel%"
if not "%demoExit%"=="0" pause
endlocal & exit /b %demoExit%
