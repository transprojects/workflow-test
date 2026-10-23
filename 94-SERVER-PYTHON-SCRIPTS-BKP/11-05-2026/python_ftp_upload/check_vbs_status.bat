@echo off
echo =====================================================
echo        VBS Cron Task Status Checker
echo =====================================================
echo.

:: Check if any wscript.exe (VBS runner) is running
echo Checking for running VBS tasks...
for /f "tokens=* delims=" %%a in ('tasklist ^| findstr /i "wscript.exe"') do (
    set FOUND=1
)

if not defined FOUND (
    echo ❌ No VBS cron tasks are running.
    echo.
    pause
    exit /b
)

echo ✔ VBS task(s) detected.
echo.
echo -----------------------------------------------------
echo Listing VBS command lines and process IDs:
echo -----------------------------------------------------

wmic process where "name='wscript.exe'" get ProcessId,CommandLine

echo.
echo =====================================================
echo  If you want to stop the cron, run:
echo      taskkill /F /IM wscript.exe
echo =====================================================
echo.

pause
