@echo off

SET PYTHON_EXE="C:\Program Files\Python310\python.exe"

SET SCRIPT1="D:\Localserver\Apache\htdocs\workflow-new\python_ftp_upload\ftp\ftp_uploads_crons.py"
SET SCRIPT2="D:\Localserver\Apache\htdocs\workflow-new\python_ftp_upload\ftp\ftp_uploads_crons_rev.py"
SET SCRIPT3="D:\Localserver\Apache\htdocs\workflow-new\python_ftp_upload\ftp\ftp_uploads_crons_fin.py"

:loop
cls

echo =======================================================
echo   FTP UPLOAD JOB STARTED: %date% %time%
echo =======================================================

echo Running Main Upload Script...
%PYTHON_EXE% %SCRIPT1%

echo.
echo Running Revised Upload Script...
%PYTHON_EXE% %SCRIPT2%

echo.
echo Running Final Upload Script...
%PYTHON_EXE% %SCRIPT3%

echo.
echo All jobs completed at %time%
echo -------------------------------------------------------
echo Waiting 15 minutes before next run...
echo -------------------------------------------------------
echo.

REM ======== 900 seconds = 15 minutes ========
timeout /t 900 >nul

goto loop