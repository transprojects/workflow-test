@echo off

SET PYTHON_EXE="C:\Program Files\Python310\python.exe"
SET SCRIPT="D:\Localserver\Apache\htdocs\workflow-new\python_productionfiles_backup\Transaction-Files-Version\backup_department_cron.py"

:loop
cls
echo =======================================================
echo   BACKUP CRON STARTED: %date% %time%
echo =======================================================

%PYTHON_EXE% %SCRIPT%

echo.
echo Backup completed at %time%
echo -------------------------------------------------------
echo Waiting 15 minutes before next run...
echo -------------------------------------------------------
echo.

REM ======== 900 seconds = 15 minutes ========
timeout /t 900 >nul

goto loop
