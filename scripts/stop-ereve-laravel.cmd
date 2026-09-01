@echo off
for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":8088" ^| findstr "LISTENING"') do (
  taskkill /PID %%a /F
)
