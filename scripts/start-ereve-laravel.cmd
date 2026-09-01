@echo off
setlocal
cd /d "%~dp0.."
start "eReve Laravel 8088" /min cmd.exe /k ""C:\xampp\php\php.exe" artisan serve --host=127.0.0.1 --port=8088"
endlocal
