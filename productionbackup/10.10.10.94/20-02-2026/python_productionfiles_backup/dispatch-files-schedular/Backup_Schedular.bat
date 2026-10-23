@echo off

SET PYTHON_EXE="C:\Program Files\Python310\python.exe"
SET SCRIPT="D:\Localserver\Apache\htdocs\workflow-new\python_productionfiles_backup\dispatch-files-schedular\backup_schedular.py"

:loop
cls
echo =======================================================
echo   BACKUP SCHEDULAR CRON STARTED: %date% %time%
echo =======================================================

%PYTHON_EXE% %SCRIPT%

echo.
echo Backup schedular completed at %time%
echo -------------------------------------------------------
echo Waiting 45 minutes before next run...
echo -------------------------------------------------------
echo.

REM ======== 2700 seconds = 45 minutes ========
timeout /t 2700 >nul

goto loop
