@echo off
cd C:\LAMP\Apache\htdocs\workflow-new

:: Run each PHP script one by one
php article_preediting_report.php
php daily_timesheet_report.php

:: Optional - add logging
echo Scripts executed successfully on %date% %time% >> C:\LAMP\Apache\htdocs\workflow-new\report-logs\ce_pe_report_log.txt
