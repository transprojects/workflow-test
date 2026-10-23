@echo off
cd C:\LAMP\Apache\htdocs\workflow-new

:: Run each PHP script one by one
php xperttrack-workflow-mismatch-tsp.php
php xperttrack-workflow-mismatch-eos.php
php xperttrack-workflow-mismatch-iap.php

:: Optional - add logging
echo Scripts executed successfully on %date% %time% >> C:\LAMP\Apache\htdocs\workflow-new\cron_log.txt
