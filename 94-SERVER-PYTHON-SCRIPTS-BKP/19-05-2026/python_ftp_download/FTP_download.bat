@echo off
SET PYTHON_EXE="C:\Program Files\Python310\python.exe"

:loop
cls
echo -------------------------------------------------------
echo  FTP JOB STARTED: %date% %time%
echo -------------------------------------------------------

REM =======================
REM   ACG FTP PROCESS
REM =======================
echo Starting ACG FTP FP/Revises/Final Download...

%PYTHON_EXE% "D:\Localserver\Apache\htdocs\workflow-new\python_ftp_download\ACG\revises_final_download.py"
%PYTHON_EXE% "D:\Localserver\Apache\htdocs\workflow-new\python_ftp_download\ACG\mail_sent.py"

echo ACG Process Completed!
echo.

REM =======================
REM   TSP FTP PROCESS
REM =======================
echo Starting TSP FTP FP/Revises/Final Download...

%PYTHON_EXE% "D:\Localserver\Apache\htdocs\workflow-new\python_ftp_download\tsp\ftp_download.py"
%PYTHON_EXE% "D:\Localserver\Apache\htdocs\workflow-new\python_ftp_download\tsp\revises_final_download.py"
%PYTHON_EXE% "D:\Localserver\Apache\htdocs\workflow-new\python_ftp_download\tsp\mail_sent.py"

echo TSP Process Completed!
echo.

REM =======================
REM   EDP-Sci FTP PROCESS
REM =======================
echo Starting EDP-Sci FTP FP/Revises/Final Download...

%PYTHON_EXE% "D:\Localserver\Apache\htdocs\workflow-new\python_ftp_download\EDP\revises_final_download.py"

echo EDP-Sci Process Completed!
echo.

echo -------------------------------------------------------
echo  FTP JOB FINISHED: %date% %time%
echo -------------------------------------------------------
echo Waiting 15 minutes before next run...
echo.

REM *** 900 seconds = 15 minutes ***
timeout /t 900 >nul

goto loop
