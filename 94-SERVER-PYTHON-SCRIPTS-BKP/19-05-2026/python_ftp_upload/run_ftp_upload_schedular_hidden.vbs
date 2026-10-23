Set fso = CreateObject("Scripting.FileSystemObject")
logFilePath = "D:\Localserver\Apache\htdocs\workflow-new\python_ftp_upload\xperttrack\ftp_upload_log.txt"

' Append log entry with date & time
Set logFile = fso.OpenTextFile(logFilePath, 8, True)
logFile.WriteLine "[" & Now & "] FTP_upload.bat started (hidden)"
logFile.Close

' Run batch file hidden
Set WshShell = CreateObject("WScript.Shell")
WshShell.Run """D:\Localserver\Apache\htdocs\workflow-new\python_ftp_upload\xperttrack\FTP_upload.bat""", 0, False
