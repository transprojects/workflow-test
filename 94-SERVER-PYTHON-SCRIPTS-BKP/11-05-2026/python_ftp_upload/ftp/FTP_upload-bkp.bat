@echo off

SET PYTHON_EXE="C:\Program Files\Python310\python.exe"
SET SCRIPT="D:\Localserver\Apache\htdocs\workflow-new\python_ftp_upload\ftp\ftp_uploads_crons.py"

:loop
cls
echo =======================================================
echo   FTP UPLOAD JOB STARTED: %date% %time%
echo =======================================================

%PYTHON_EXE% %SCRIPT%

echo.
echo Job completed at %time%
echo -------------------------------------------------------
echo Waiting 15 minutes before next run...
echo -------------------------------------------------------
echo.

REM ======== 900 seconds = 15 minutes ========
timeout /t 900 >nul

goto loop
