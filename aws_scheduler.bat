
REM https://www.howtogeek.com/51236/how-to-create-modify-and-delete-scheduled-tasks-from-the-command-line/


SchTasks /Create /SC DAILY /TN "AWS_proof_download_6_AM" /TR "C:/LAMP/Apache/htdocs/workflow/AWS_Proof_schedule.bat" /ST 06:00
SchTasks /Create /SC DAILY /TN "AWS_proof_download_7_AM" /TR "C:/LAMP/Apache/htdocs/workflow/AWS_Proof_schedule.bat" /ST 07:00
SchTasks /Create /SC DAILY /TN "AWS_proof_download_8_AM" /TR "C:/LAMP/Apache/htdocs/workflow/AWS_Proof_schedule.bat" /ST 08:00
SchTasks /Create /SC DAILY /TN "AWS_proof_download_9_AM" /TR "C:/LAMP/Apache/htdocs/workflow/AWS_Proof_schedule.bat" /ST 09:00
SchTasks /Create /SC DAILY /TN "AWS_proof_download_10_AM" /TR "C:/LAMP/Apache/htdocs/workflow/AWS_Proof_schedule.bat" /ST 10:00
SchTasks /Create /SC DAILY /TN "AWS_proof_download_11_AM" /TR "C:/LAMP/Apache/htdocs/workflow/AWS_Proof_schedule.bat" /ST 11:00
SchTasks /Create /SC DAILY /TN "AWS_proof_download_06_PM" /TR "C:/LAMP/Apache/htdocs/workflow/AWS_Proof_schedule.bat" /ST 18:00
SchTasks /Create /SC DAILY /TN "AWS_proof_download_7_PM" /TR "C:/LAMP/Apache/htdocs/workflow/AWS_Proof_schedule.bat" /ST 19:00
SchTasks /Create /SC DAILY /TN "AWS_proof_download_8_PM" /TR "C:/LAMP/Apache/htdocs/workflow/AWS_Proof_schedule.bat" /ST 20:00
