Set fso = CreateObject("Scripting.FileSystemObject")
logFilePath = "D:\Localserver\Apache\htdocs\workflow-new\python_productionfiles_backup\Transaction-Files-Version\logs\version_backup_log.txt"

' Append log entry with date & time
Set logFile = fso.OpenTextFile(logFilePath, 8, True)
logFile.WriteLine "[" & Now & "] Backup_Schedular_new.bat started (hidden)"
logFile.Close

' Run batch file hidden
Set WshShell = CreateObject("WScript.Shell")
WshShell.Run """D:\Localserver\Apache\htdocs\workflow-new\python_productionfiles_backup\Transaction-Files-Version\Backup_Schedular_new.bat""", 0, False
