@echo off
cd /d C:\LAMP\Apache\htdocs\workflow-new\python_ftp_download\ACG

echo Starting ACG FTP FP/Revises/Final Download...
python revises_final_download.py

echo Sending email notification...
python mail_sent.py

echo All tasks completed successfully!
